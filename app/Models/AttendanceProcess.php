<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceProcess extends Model
{
    use HasFactory;

    protected $fillable = ['day', 'check_in', 'check_out', 'employee_id', 'absence_id', 'entry_by'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function absence()
    {
        return $this->belongsTo(Absence::class);
    }
}
