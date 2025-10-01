<?php

namespace App\Http\Controllers;

use App\Exports\TreasuryTransactionsExport;
use App\Models\TreasuryTransaction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class TreasuryTransactionController extends Controller
{
    public function index()
    {
        // Fetch all transactions with related treasury data
        $transactions = TreasuryTransaction::with('treasury')->get();
        return response()->json($transactions);
    }

    public function export($format)
    {
        $exportClass = new TreasuryTransactionsExport();
        $fileName = "treasury_transactions.{$format}";

        if ($format == 'csv') {
            return Excel::download($exportClass, $fileName, \Maatwebsite\Excel\Excel::CSV);
        }

        if ($format == 'xlsx') {
            return Excel::download($exportClass, $fileName, \Maatwebsite\Excel\Excel::XLSX);
        }

        return response()->json(['error' => 'Invalid format'], 400);
    }
}
