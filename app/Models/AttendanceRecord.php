<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AttendanceRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id', 'arrival_time', 'departure_time', 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
