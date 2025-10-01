<?php

// StudentFeeController.php
namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\FinancialDocument;
use App\Models\SubscriptionFee;
use Illuminate\Http\Request;

class StudentFeeController extends Controller
{
// StudentFeeController.php
// StudentFeeController.php
public function getStudentFees($studentId, Request $request)
{
    $student = Student::findOrFail($studentId);
    $year = $request->input('year', date('Y'));

    // Get all subscription fees assigned to this student with payment details
    $feeStructure = $student->subscriptionFees()
        ->whereYear('financial_document_subscription_fee.created_at', $year)
        ->get()
        ->map(function ($fee) use ($student) {
            // Get all payments for this specific fee
            $payments = $student->financialDocuments()
                ->whereHas('subscriptionFees', function($query) use ($fee) {
                    $query->where('subscription_fee_id', $fee->id);
                })
                ->get();

            // Calculate payment details
            $paidAmount = $payments->sum('final_amount');
            $valueReceived = $payments->sum('value_received');
            $remainingAmount = max(0, $fee->pivot->amount - $valueReceived);

            return [
                'category' => $fee->category,
                'sub_category' => $fee->sub_category,
                'amount' => (float) $fee->pivot->amount,
                'paid_amount' => (float) $paidAmount,
                'value_received' => (float) $valueReceived,
                'remaining_amount' => (float) $remainingAmount,
                'due_date' => $fee->pivot->created_at->format('Y-m-d'),
                'is_paid' => $remainingAmount <= 0,
                'payment_status' => $remainingAmount <= 0 ? 'paid' : 
                    ($valueReceived > 0 ? 'partial' : 'unpaid'),
            ];
        });

    // Get payment history for this student
    $paymentHistory = FinancialDocument::whereHas('subscriptionFees', function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        })
        ->with(['subscriptionFees' => function ($query) use ($studentId) {
            $query->where('student_id', $studentId);
        }])
        ->whereYear('created_at', $year)
        ->get()
        ->map(function ($document) {
            return [
                'receipt_number' => $document->receipt_number,
                'amount' => (float) $document->final_amount,
                'value_received' => (float) $document->value_received,
                'remaining_amount' => (float) $document->remaining_amount,
                'payment_date' => $document->created_at->toDateString(),
                'payment_method' => $document->payment_method,
            ];
        });

    // Calculate totals based on value_received (actual payments)
    $totalFees = (float) $feeStructure->sum('amount');
    $totalPaid = (float) $paymentHistory->sum('value_received');
    $outstandingBalance = $totalFees - $totalPaid;

    return response()->json([
        'success' => true,
        'fee_structure' => $feeStructure,
        'payment_history' => $paymentHistory,
        'outstanding_balance' => (float) $outstandingBalance,
        'total_fees' => $totalFees,
        'total_paid' => $totalPaid,
    ]);
}
}