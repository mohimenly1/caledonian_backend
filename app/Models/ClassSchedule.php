<?php

// app/Models/ClassSchedule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_course_assignment_id',
        'schedule_period_id',
        'term_id',
        'day_of_week',
        'room_number',
    ];

    protected $casts = [
        'day_of_week' => 'string',
    ];

    public function teacherCourseAssignment()
    {
        return $this->belongsTo(TeacherCourseAssignment::class);
    }

    public function schedulePeriod()
    {
        return $this->belongsTo(SchedulePeriod::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function substitutions()
    {
        return $this->hasMany(ScheduleSubstitution::class);
    }
}
