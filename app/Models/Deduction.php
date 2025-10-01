<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deduction extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'amount', 'reason', 'date','deduction_type_id'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    public function deductionType()
    {
        return $this->belongsTo(DeductionType::class);
    }
}
