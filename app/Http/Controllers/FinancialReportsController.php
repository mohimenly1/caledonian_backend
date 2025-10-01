<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\Account;
use App\Models\Treasury;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialReportsController extends Controller
{
    /**
     * Generate a Treasury Movement Report.
     * GET /api/reports/treasury-movement
     */
    public function treasuryMovement(Request $request)
    {
        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $treasury = Treasury::findOrFail($validated['treasury_id']);

        // 1. Get the opening balance
        $openingBalance = Transaction::where('treasury_id', $treasury->id)
            ->where('payment_date', '<', $validated['start_date'])
            ->select(DB::raw('SUM(CASE WHEN type = "income" THEN amount ELSE -amount END) as balance'))
            ->value('balance') ?? 0;

        // 2. Get transactions within the date range
        $transactions = Transaction::where('treasury_id', $treasury->id)
            ->whereBetween('payment_date', [$validated['start_date'], $validated['end_date']])
            ->orderBy('payment_date')
            ->get();

        return response()->json([
            'treasury_name' => $treasury->name,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'opening_balance' => $openingBalance,
            'transactions' => $transactions,
        ]);
    }

    /**
     * Generate an Income Statement (Profit & Loss) Report.
     * GET /api/reports/income-statement
     */
    public function incomeStatement(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        // 1. Calculate total income
        $totalIncome = DB::table('transactions')
            ->join('invoices', 'transactions.related_id', '=', 'invoices.id')
            ->join('invoice_items', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('fee_structures', 'invoice_items.fee_structure_id', '=', 'fee_structures.id')
            ->join('fee_types', 'fee_structures.fee_type_id', '=', 'fee_types.id')
            ->join('accounts', 'fee_types.income_account_id', '=', 'accounts.id')
            ->where('transactions.type', 'income')
            ->whereBetween('transactions.payment_date', [$validated['start_date'], $validated['end_date']])
            ->select('accounts.name', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('accounts.name')
            ->get();

        // 2. Calculate total expenses
        $totalExpenses = DB::table('transactions')
            ->join('bills', 'transactions.related_id', '=', 'bills.id')
            ->join('bill_items', 'bills.id', '=', 'bill_items.bill_id')
            ->join('accounts', 'bill_items.expense_account_id', '=', 'accounts.id')
            ->where('transactions.type', 'expense')
            ->whereBetween('transactions.payment_date', [$validated['start_date'], $validated['end_date']])
            ->select('accounts.name', DB::raw('SUM(transactions.amount) as total'))
            ->groupBy('accounts.name')
            ->get();
            
        // 3. Calculate Net Profit/Loss
        $netProfit = $totalIncome->sum('total') - $totalExpenses->sum('total');

        return response()->json([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'income_summary' => $totalIncome,
            'expense_summary' => $totalExpenses,
            'total_income' => $totalIncome->sum('total'),
            'total_expenses' => $totalExpenses->sum('total'),
            'net_profit' => $netProfit,
        ]);
    }
}
