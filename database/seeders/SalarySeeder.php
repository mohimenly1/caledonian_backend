<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Salary;
use App\Models\Employee;

class SalarySeeder extends Seeder
{
    public function run()
    {
        $teacher = Employee::whereNotNull('teacher_type_id')->first();
        $employee = Employee::whereNull('teacher_type_id')->first();

        // Teacher Salary
        Salary::create([
            'employee_id' => $teacher->id,
            'base_salary' => 0,
            'hourly_rate' => 100,
            'total_salary' => 0,
        ]);

        // Regular Employee Salary
        Salary::create([
            'employee_id' => $employee->id,
            'base_salary' => 3000,
            'hourly_rate' => null,
            'total_salary' => 3000,
        ]);
    }
}
