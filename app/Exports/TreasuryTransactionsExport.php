<?php

namespace App\Exports;

use App\Models\TreasuryTransaction;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;

class TreasuryTransactionsExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    public function collection()
    {
        return TreasuryTransaction::with('treasury')->get();
    }

    public function headings(): array
    {
        return [
            'Treasury Name',
            'Transaction Type',
            'Amount',
            'Description',
            'Related ID',
            'Related Type',
            'Date',
        ];
    }

    public function map($transaction): array
    {
        return [
            $transaction->treasury->name,
            $transaction->transaction_type,
            $transaction->amount,
            $transaction->description,
            $transaction->related_id,
            $transaction->related_type,
            $transaction->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function (BeforeExport $event) {
                $event->writer->getProperties()->setCreator('Caledonian ERP System');
            },
        ];
    }
}
