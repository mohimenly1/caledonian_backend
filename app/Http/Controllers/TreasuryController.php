<?php

namespace App\Http\Controllers;

use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Illuminate\Http\Request;

class TreasuryController extends Controller
{
    public function index()
    {
        $treasuries = Treasury::with('transactions')->get();
        return response()->json($treasuries);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:bank,manual',
            'bank_name' => 'required_if:type,bank',
            'account_number' => 'required_if:type,bank',
            'routing_number' => 'required_if:type,bank',
        ]);

        $treasury = Treasury::create($validated);
        return response()->json($treasury, 201);
    }

    public function deposit(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'string|nullable',
            'related_id' => 'required|integer',
            'related_type' => 'required|in:salary,subscription_fee',
        ]);

        $treasury = Treasury::findOrFail($id);
        $treasury->balance += $validated['amount'];
        $treasury->save();

        TreasuryTransaction::create([
            'treasury_id' => $treasury->id,
            'transaction_type' => 'deposit',
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'related_id' => $validated['related_id'],
            'related_type' => $validated['related_type'],
        ]);

        return response()->json(['message' => 'Deposit successful.']);
    }

    public function disburse(Request $request, $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'string|nullable',
            'related_id' => 'required|integer',
            'related_type' => 'required|in:salary,subscription_fee',
        ]);

        $treasury = Treasury::findOrFail($id);

        if ($treasury->balance < $validated['amount']) {
            return response()->json(['message' => 'Insufficient funds in the treasury.'], 400);
        }

        $treasury->balance -= $validated['amount'];
        $treasury->save();

        TreasuryTransaction::create([
            'treasury_id' => $treasury->id,
            'transaction_type' => 'disbursement',
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'related_id' => $validated['related_id'],
            'related_type' => $validated['related_type'],
        ]);

        return response()->json(['message' => 'Disbursement successful.']);
    }
}
