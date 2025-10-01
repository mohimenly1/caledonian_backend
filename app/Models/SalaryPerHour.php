<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalaryPerHour extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'salaries_per_hours';
    protected $fillable = [
        'employee_id', 'hourly_rate', 'mandatory_attendance_time', 'num_classes', 'class_rate'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
