<?php

// app/Models/ScheduleSubstitution.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleSubstitution extends Model
{
    use HasFactory;

    protected $fillable = [
        'class_schedule_id',
        'substitute_teacher_id',
        'substitution_date',
        'reason',
    ];

    protected $casts = [
        'substitution_date' => 'date',
    ];

    public function classSchedule()
    {
        return $this->belongsTo(ClassSchedule::class);
    }

    public function substituteTeacher()
    {
        return $this->belongsTo(User::class, 'substitute_teacher_id');
    }
}
