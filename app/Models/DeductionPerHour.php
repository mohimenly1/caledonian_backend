<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeductionPerHour extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'deductions_per_hour';
    protected $fillable = [
        'amount',
        'description',
        'id'
    ];

    /**
     * Relationship to IssuedSalaryPerHour model for issued salaries that use this deduction
     */
    public function issuedSalaries()
    {
        return $this->hasMany(IssuedSalaryPerHour::class, 'deduction_id');
    }
}
