<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogCheckInProcess extends Model
{
    use HasFactory;
    protected $table = 'logs_check_in_process';

    protected $fillable = ['employee_id', 'check_in_time', 'check_out_time'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}