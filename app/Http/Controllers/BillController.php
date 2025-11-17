<?php

namespace App\Http\Controllers;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;

class BillController extends Controller
{
    public function index(Request $request)
    {
        $query = Bill::with('vendor:id,name')
                     ->withSum('payments as paid_amount', 'amount')
                     ->orderBy('issue_date', 'desc');

        $bills = $query->paginate($request->input('per_page', 15));

        $bills->getCollection()->transform(function ($bill) {
            $bill->paid_amount = $bill->paid_amount ?? 0;
            $bill->remaining_amount = $bill->total_amount - $bill->paid_amount;
            return $bill;
        });

        return response()->json($bills);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'bill_number' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.expense_account_id' => 'required|exists:accounts,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
        ]);

        $totalAmount = collect($validated['items'])->sum('amount');

        try {
            DB::beginTransaction();

            $bill = Bill::create([
                'vendor_id' => $validated['vendor_id'],
                'bill_number' => $validated['bill_number'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'total_amount' => $totalAmount,
                'status' => 'unpaid',
            ]);

            $bill->items()->createMany($validated['items']);

            DB::commit();
            return response()->json($bill->load('items'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create bill.', 'error' => $e->getMessage()], 500);
        }
    }

    public function show(Bill $bill)
    {
        return response()->json($bill->load(['vendor', 'items.expenseAccount', 'payments.treasury']));
    }

    public function update(Request $request, Bill $bill)
    {
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'bill_number' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'items' => 'required|array|min:1',
            'items.*.expense_account_id' => 'required|exists:accounts,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|numeric|min:0.01',
        ]);

        $totalAmount = collect($validated['items'])->sum('amount');

        try {
            DB::beginTransaction();

            // Update bill
            $bill->update([
                'vendor_id' => $validated['vendor_id'],
                'bill_number' => $validated['bill_number'],
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'total_amount' => $totalAmount,
            ]);

            // Delete existing items and create new ones
            $bill->items()->delete();
            $bill->items()->createMany($validated['items']);

            DB::commit();
            return response()->json($bill->load(['vendor', 'items.expenseAccount', 'payments.treasury']));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to update bill.', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy(Bill $bill)
    {
        if ($bill->payments()->exists()) {
            return response()->json(['message' => 'Cannot delete a bill that has payments.'], 422);
        }
        $bill->delete();
        return response()->json(['message' => 'Bill deleted successfully.']);
    }
}
