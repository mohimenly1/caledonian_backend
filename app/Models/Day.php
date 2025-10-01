<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Day extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'absence',
        'vacations',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
