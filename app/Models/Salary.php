<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Import SoftDeletes
class Salary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['employee_id', 'base_salary', 'bonus', 'allowance', 'currency','effective_from','month','year','net_salary','total_deductions','report_operations'];

    public function employee()
    {
        return $this->belongsTo(Employee::class,);
    }

    public function deductions()
    {
        return $this->hasMany(Deduction::class, 'employee_id', 'employee_id')
                    ->whereYear('date', $this->year)
                    ->whereMonth('date', $this->month);
    }


    public function deductionsShow()
    {
        return $this->belongsToMany(Deduction::class, 'salary_deductions_absences', 'salary_id', 'deduction_id');
    }
    

    public function absences()
    {
        return $this->hasMany(Absence::class, 'employee_id', 'employee_id')
                    ->whereYear('date', $this->year)
                    ->whereMonth('date', $this->month);
    }

    // Calculate the net salary dynamically
// Calculate the net salary dynamically
// public function getNetSalaryAttribute()
// {
//     $totalDeductions = $this->deductions->sum('amount');
//     return $this->base_salary + ($this->bonus ?? 0) - $totalDeductions;
// }

}
