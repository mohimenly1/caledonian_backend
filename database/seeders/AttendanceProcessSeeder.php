<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AttendanceProcess;
use Carbon\Carbon;

class AttendanceProcessSeeder extends Seeder
{
    public function run()
    {
        $employeeId = 28; // ID of the employee
        $month = 11; // November (you can adjust this)
        $year = 2024; // 2024 (you can adjust this)
        $totalDaysInMonth = Carbon::createFromDate($year, $month, 1)->daysInMonth;

        $lateDays = [1, 3, 5]; // Days the employee will be late
        $absenceDays = [7, 10]; // Days the employee will be absent

        for ($day = 1; $day <= $totalDaysInMonth; $day++) {
            $date = Carbon::create($year, $month, $day)->toDateString();

            if (in_array($day, $lateDays)) {
                // Late day: Check-in after 8:00 AM (e.g., 8:30 AM)
                AttendanceProcess::create([
                    'day' => $date,
                    'check_in' => '08:30:00',
                    'check_out' => '17:00:00',
                    'employee_id' => $employeeId,
        
                    'entry_by' => 'manually'
                ]);
            } elseif (in_array($day, $absenceDays)) {
                // Absence day: No check-in or check-out, just an absence record
                AttendanceProcess::create([
                    'day' => $date,
                    'check_in' => null,
                    'check_out' => null,
                    'employee_id' => $employeeId,
                 
                    'entry_by' => 'manually'
                ]);
            } else {
                // Regular attendance day: Check-in on time at 8:00 AM
                AttendanceProcess::create([
                    'day' => $date,
                    'check_in' => '08:00:00',
                    'check_out' => '17:00:00',
                    'employee_id' => $employeeId,
           
                    'entry_by' => 'manually'
                ]);
            }
        }
    }
}
