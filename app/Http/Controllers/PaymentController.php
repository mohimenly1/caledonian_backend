<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\Treasury;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Bill;

class PaymentController extends Controller
{
    /**
     * Store a new payment for a given invoice.
     * POST /api/payments
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'treasury_id' => 'required|exists:treasuries,id',
            'amount_paid' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($validatedData['invoice_id']);
        $treasury = Treasury::findOrFail($validatedData['treasury_id']);
        
        // Check if the invoice is already fully paid
        if ($invoice->status === 'paid') {
            return response()->json(['message' => 'This invoice has already been fully paid.'], 422);
        }
        
        // Check if the payment exceeds the remaining amount
        $totalPaid = $invoice->payments()->sum('amount');
        $remainingAmount = $invoice->final_amount - $totalPaid;

        if ($validatedData['amount_paid'] > $remainingAmount) {
            return response()->json([
                'message' => 'Payment amount cannot be greater than the remaining amount.',
                'remaining_amount' => $remainingAmount
            ], 422);
        }

        try {
            DB::beginTransaction();

            // 1. Create a new transaction record
            Transaction::create([
                'treasury_id' => $treasury->id,
                'payment_date' => $validatedData['payment_date'],
                'amount' => $validatedData['amount_paid'],
                'type' => 'income',
                'payment_method' => $validatedData['payment_method'],
                'description' => $validatedData['description'] ?? 'Payment for invoice #' . $invoice->invoice_number,
                'related_id' => $invoice->id,
                'related_type' => Invoice::class,
            ]);

            // 2. Update the treasury balance
            $treasury->balance += $validatedData['amount_paid'];
            $treasury->save();

            // 3. Update the invoice status
            $newTotalPaid = $totalPaid + $validatedData['amount_paid'];
            if ($newTotalPaid >= $invoice->final_amount) {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'partially_paid';
            }
            $invoice->save();

            DB::commit();

            return response()->json([
                'message' => 'Payment recorded successfully.',
                'invoice_status' => $invoice->status,
                'new_treasury_balance' => $treasury->balance,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred while processing the payment.', 'error' => $e->getMessage()], 500);
        }
    }

    public function storeBillPayment(Request $request)
{
    $validatedData = $request->validate([
        'bill_id' => 'required|exists:bills,id',
        'treasury_id' => 'required|exists:treasuries,id',
        'amount_paid' => 'required|numeric|min:0.01',
        'payment_date' => 'required|date',
        'payment_method' => 'required|string',
        'description' => 'nullable|string',
    ]);

    $bill = Bill::findOrFail($validatedData['bill_id']);
    $treasury = Treasury::findOrFail($validatedData['treasury_id']);
    
    // Check if the bill is already fully paid
    if ($bill->status === 'paid') {
        return response()->json(['message' => 'This bill has already been fully paid.'], 422);
    }
    
    $totalPaid = $bill->payments()->sum('amount');
    $remainingAmount = $bill->total_amount - $totalPaid;

    if ($validatedData['amount_paid'] > $remainingAmount) {
        return response()->json(['message' => 'Payment amount cannot be greater than the remaining amount.'], 422);
    }

    try {
        DB::beginTransaction();

        // 1. Create a new transaction record for the expense
        Transaction::create([
            'treasury_id' => $treasury->id,
            'payment_date' => $validatedData['payment_date'],
            'amount' => $validatedData['amount_paid'],
            'type' => 'expense', // This is an expense
            'payment_method' => $validatedData['payment_method'],
            'description' => $validatedData['description'] ?? 'Payment for bill #' . $bill->bill_number,
            'related_id' => $bill->id,
            'related_type' => Bill::class,
        ]);

        // 2. Update the treasury balance
        $treasury->balance -= $validatedData['amount_paid'];
        $treasury->save();

        // 3. Update the bill status
        $newTotalPaid = $totalPaid + $validatedData['amount_paid'];
        if ($newTotalPaid >= $bill->total_amount) {
            $bill->status = 'paid';
        } else {
            $bill->status = 'partially_paid';
        }
        $bill->save();

        DB::commit();

        return response()->json(['message' => 'Payment recorded successfully.'], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'An error occurred.', 'error' => $e->getMessage()], 500);
    }
}
}
