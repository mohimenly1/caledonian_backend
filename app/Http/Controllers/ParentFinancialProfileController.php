<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ParentInfo;
use Illuminate\Http\Request;

class ParentFinancialProfileController extends Controller
{
    /**
     * Display the financial profile for a specific parent.
     * GET /api/financial-profile/parent/{parent}
     */
    public function show(ParentInfo $parent)
    {
        // 1. Load the parent with their invoices and all related payments
        $parent->load([
            'invoices' => function ($query) {
                $query->withSum('payments as paid_amount', 'amount')
                      ->orderBy('issue_date', 'desc');
            },
            'invoices.payments.treasury:id,name'
        ]);

        // 2. Calculate overall totals
        $totalInvoiced = $parent->invoices->sum('final_amount');
        $totalPaid = $parent->invoices->sum('paid_amount');
        $balance = $totalInvoiced - $totalPaid;

        // 3. Add remaining amount to each invoice for detailed view
        $parent->invoices->transform(function ($invoice) {
            $invoice->paid_amount = $invoice->paid_amount ?? 0;
            $invoice->remaining_amount = $invoice->final_amount - $invoice->paid_amount;
            return $invoice;
        });

        return response()->json([
            'parent' => $parent,
            'summary' => [
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'balance' => $balance,
            ],
        ]);
    }
}
