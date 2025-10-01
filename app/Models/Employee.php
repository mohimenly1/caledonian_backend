<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
class Employee extends Model
{
    use HasFactory,SoftDeletes;

    protected $fillable = [
        'user_id', 'department_id', 'name', 'teacher_type_id', 'national_number',
        'phone_number', 'phone_number_two', 'address', 'photos', 'years_of_experience',
        'gender', 'date_of_birth', 'passport_number', 'attached_files', 'employee_type_id', 'pin',
        'base_salary','fingerprint_id','date_of_join','is_teacher'
    
    ];

   // Define the relationship to the attendance_processes table
   public function attendanceProcesses()
   {
       return $this->hasMany(AttendanceProcess::class, 'employee_id', 'id');
   }
    public function issuedSalariesPerHour()
    {
        return $this->hasMany(IssuedSalaryPerHour::class);
    }

    // In Employee.php model
public function salariesPerHour()
{
    return $this->hasMany(SalaryPerHour::class); // Assuming SalaryPerHour is the model name
}

public function logCheckInProcesses()
{
    return $this->hasMany(LogCheckInProcess::class, 'employee_id');
}

public function absences()
{
    return $this->hasMany(Absence::class, 'employee_id');
}

 /**
     * Calculate the total salary after applying delay and absence penalties.
     *
     * @return float
     */
    public function calculateNetSalary()
    {
        $baseSalary = $this->base_salary;
        $totalDeductions = $this->calculateDelayDeductions() + $this->calculateAbsenceDeductions();

        return $baseSalary - $totalDeductions + $this->bonus + $this->allowance;
    }

    /**
     * Calculate deductions for delays based on company policy.
     *
     * @return float
     */
    public function calculateDelayDeductions()
    {
        $mandatoryStartTime = 480; // 8:00 AM in minutes
        $lateDays = 0;

        foreach ($this->logCheckInProcesses as $log) {
            $checkInTime = Carbon::parse($log->check_in_time)->hour * 60 + Carbon::parse($log->check_in_time)->minute;
            if ($checkInTime > $mandatoryStartTime) {
                $lateDays++;
            }
        }

        // Apply company policy for late deductions
        if ($lateDays >= 6) {
            return $this->base_salary * 0.4; // 40% deduction for 6 or more days late
        } elseif ($lateDays >= 3) {
            return $this->base_salary * 0.2; // 20% deduction for 3 days late
        }

        return 0; // No deduction if less than 3 days late
    }

    /**
     * Calculate deductions for absences based on company policy.
     *
     * @return float
     */
    public function calculateAbsenceDeductions()
    {
        $absenceDays = $this->absences->count();
        $dailyRate = $this->base_salary / 30; // Assuming a 30-day month

        // Deduct 3 days' worth of salary for each absence day
        return $absenceDays * $dailyRate * 3;
    }


    public function salaryPerHour()
    {
        return $this->hasOne(SalaryPerHour::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function wallet()
    {
        return $this->hasOne(EmployeeWallet::class);
    }



    public function classes()
    {
        return $this->belongsToMany(ClassRoom::class, 'employee_class', 'employee_id', 'class_id');
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'employee_subject', 'employee_id', 'subject_id')
            ->withTimestamps();
    }
    public function sections()
    {
        return $this->belongsToMany(Section::class, 'employee_section', 'employee_id', 'section_id');
    }

    public function employees()
{
    return $this->hasMany(Employee::class);
}


    public function employeeType()
    {
        return $this->belongsTo(EmployeeType::class);
    }

    public function logs()
    {
        return $this->hasMany(LogCheckInProcess::class);
    }

    public function days()
    {
        return $this->hasMany(Day::class);
    }


    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function salary()
    {
        return $this->hasOne(Salary::class);
    }

    public function vacations()
    {
        return $this->hasMany(Vacation::class);
    }

    public function permissions()
{
    return $this->hasMany(PermissionEmployee::class);
}

    public function deductions()
    {
        return $this->hasMany(Deduction::class);
    }

    // public function absences()
    // {
    //     return $this->hasMany(Absence::class);
    // }
    public function calculateMonthlyAbsences(Employee $employee)
    {
        $currentMonth = Carbon::now()->month;
        $logs = LogCheckInProcess::where('employee_id', $employee->id)
            ->whereMonth('check_in_time', $currentMonth)
            ->get()
            ->groupBy(function($date) {
                return Carbon::parse($date->check_in_time)->format('Y-m-d');
            });

        $daysInMonth = Carbon::now()->daysInMonth;

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = Carbon::now()->format('Y-m-') . str_pad($i, 2, '0', STR_PAD_LEFT);
            if (!isset($logs[$date])) {
                Day::create([
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'absence' => true,
                ]);
            }
        }
    }

    public function timetables()
{
    return $this->hasMany(Timetable::class, 'user_id');
}

// In Employee model
public function user()
{
    return $this->belongsTo(User::class);
}
}