<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absence extends Model
{
    use HasFactory;

    protected $fillable = ['employee_id', 'date', 'absence_type_id'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function absenceType()
    {
        return $this->belongsTo(AbsenceType::class);
    }
}
