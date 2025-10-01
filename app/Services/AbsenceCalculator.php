<?php
namespace App\Services;

use Carbon\Carbon;
use App\Models\Employee;
use App\Models\LogCheckInProcess;
use App\Models\Day;
use App\Models\Salary;
use App\Models\Treasury;
use App\Models\TreasuryTransaction;
use Illuminate\Support\Facades\DB;

class AbsenceCalculator
{
    public function calculateMonthlyAbsencesAndSalary(Employee $employee, $treasuryId)
    {
        $currentMonth = Carbon::now()->month;
        $logs = LogCheckInProcess::where('employee_id', $employee->id)
            ->whereMonth('check_in_time', $currentMonth)
            ->get()
            ->groupBy(function($date) {
                return Carbon::parse($date->check_in_time)->format('Y-m-d');
            });

        $daysInMonth = Carbon::now()->daysInMonth;
        $absentDays = 0;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = Carbon::now()->format('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT);
            if (!isset($logs[$date])) {
                Day::create([
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'absence' => true,
                ]);
                $absentDays++;
            }
        }

        $salary = Salary::firstOrCreate(
            ['employee_id' => $employee->id],
            [
                'base_salary' => 0,
                'hourly_rate' => null,
                'total_salary' => 0
            ]
        );

        if ($employee->teacher_type_id) {
            $totalHours = LogCheckInProcess::where('employee_id', $employee->id)
                ->whereMonth('check_in_time', $currentMonth)
                ->sum(DB::raw('TIMESTAMPDIFF(HOUR, check_in_time, check_out_time)'));
            $salary->total_salary = $totalHours * $salary->hourly_rate;
        } else {
            $dailyRate = $salary->base_salary / $daysInMonth;
            $salary->total_salary = $salary->base_salary - ($dailyRate * $absentDays);
        }
        $salary->save();

        // Treasury Disbursement
        $treasury = Treasury::findOrFail($treasuryId);

        if ($treasury->balance < $salary->total_salary) {
            throw new \Exception('Insufficient funds in the treasury.');
        }

        $treasury->balance -= $salary->total_salary;
        $treasury->save();

        // Create treasury transaction
        TreasuryTransaction::create([
            'treasury_id' => $treasury->id,
            'transaction_type' => 'disbursement',
            'amount' => $salary->total_salary,
            'description' => 'Salary Disbursement',
            'related_id' => $salary->id,
            'related_type' => 'salary',
        ]);
    }

    public function adjustSalary(Employee $employee, $deduction)
    {
        $salary = Salary::where('employee_id', $employee->id)->first();

        if (!$salary) {
            return response()->json(['message' => 'Salary record not found.'], 404);
        }

        $salary->total_salary -= $deduction;
        $salary->save();

        return response()->json(['message' => 'Salary adjusted successfully.']);
    }
}

