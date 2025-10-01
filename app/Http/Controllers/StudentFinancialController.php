<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Invoice;
use Illuminate\Http\Request;

class StudentFinancialController extends Controller
{
    /**
     * Get the complete financial profile for a single student.
     * GET /api/students/{student}/financial-profile
     */
    public function getProfile(Student $student)
    {
        // 1. Find all invoices that include this student in their items.
        $invoices = Invoice::whereHas('items', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        })
        ->with([
            'items' => function($query) use ($student) {
                // We only want the items for this specific student
                $query->where('student_id', $student->id);
            }, 
            'payments.treasury:id,name'
        ])
        ->orderBy('issue_date', 'desc')
        ->get();

        // 2. Calculate summary totals
        $totalDue = 0;
        $totalPaid = 0;

        $invoices->each(function ($invoice) use (&$totalDue, &$totalPaid) {
            // The amount due for this student in this invoice
            $studentInvoiceAmount = $invoice->items->sum('amount');
            $totalDue += $studentInvoiceAmount;
            
            // Note: Payments are on the invoice level, not per student.
            // For simplicity here, we will show all invoice payments.
            // A more advanced system might allocate payments per student.
            $invoice->paid_amount = $invoice->payments->sum('amount');
            $invoice->remaining_amount = $invoice->final_amount - $invoice->paid_amount;
            $totalPaid += $invoice->paid_amount; // This is a simplification
        });

        // This summary is for all invoices the student is part of.
        $balance = $invoices->sum('final_amount') - $invoices->sum('paid_amount');


        return response()->json([
            'success' => true,
            'student_info' => $student->only(['id', 'name']),
            'invoices' => $invoices,
            'summary' => [
                'total_due' => $invoices->sum('final_amount'),
                'total_paid' => $invoices->sum('paid_amount'),
                'balance' => $balance,
            ]
        ]);
    }
}
