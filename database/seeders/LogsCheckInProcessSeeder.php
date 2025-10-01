<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;
use App\Models\LogCheckInProcess;
use Carbon\Carbon;

class LogsCheckInProcessSeeder extends Seeder
{
    public function run()
    {
        $teacher = Employee::whereNotNull('teacher_type_id')->first();
        $employee = Employee::whereNull('teacher_type_id')->first();

        $currentMonth = Carbon::now()->month;

        // Generate logs for the whole month
        for ($day = 1; $day <= Carbon::now()->daysInMonth; $day++) {
            $date = Carbon::now()->format('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);

            // Logs for teacher (8 hours each day)
            // LogCheckInProcess::create([
            //     'employee_id' => $teacher->id,
            //     'check_in_time' => "$date 09:00:00",
            //     'check_out_time' => "$date 17:00:00",
            // ]);

            // Logs for regular employee (8 hours each day)
            LogCheckInProcess::create([
                'employee_id' => '1',
                'check_in_time' => "$date 09:00:00",
                'check_out_time' => "$date 17:00:00",
            ]);
        }
    }
}
