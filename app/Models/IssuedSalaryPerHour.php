<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IssuedSalaryPerHour extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'issued_salaries_per_hour';
    protected $fillable = [
        'employee_id',
        'deduction_id',
        'issued_date',
        'bonus',
        'currency',
        'custom_deduction',
        'net_salary',
        'note',
        'base_salary',
        'delay_message'
    ];

    /**
     * Relationship to the Employee model
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Relationship to the DeductionPerHour model for fixed deductions
     */
    public function deduction()
    {
        return $this->belongsTo(DeductionPerHour::class, 'deduction_id');
    }
}
