<?php

namespace App\Exports;

use App\Models\LogCheckInProcess;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\BeforeExport;

class LogsExport implements FromCollection, WithHeadings, WithMapping, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return LogCheckInProcess::with('employee')->get();
    }

    public function headings(): array
    {
        return [
            'Employee Name',
            'Check-In Time',
            'Check-Out Time',
        ];
    }

    public function map($log): array
    {
        return [
            $log->employee->name,
            $log->check_in_time,
            $log->check_out_time,
        ];
    }

    public function registerEvents(): array
    {
        return [
            BeforeExport::class => function(BeforeExport $event) {
                $event->writer->getProperties()->setCreator('Your App Name');
                if ($event->writer->getDelegate() instanceof \PhpOffice\PhpSpreadsheet\Writer\Csv) {
                    $event->writer->getDelegate()->setUseBOM(true);
                }
            },
        ];
    }
}



