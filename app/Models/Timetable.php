<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    protected $fillable = [
        'class_id', 'section_id', 'subject_id', 'teacher_id',
        'day_of_week', 'start_time', 'end_time', 'duration',
        'is_recurring', 'specific_date', 'is_holiday', 'notes'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'specific_date' => 'date',
    ];

    public function class()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function scopeForClass($query, $classId)
    {
        return $query->where('class_id', $classId);
    }

    public function scopeForSection($query, $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    public function scopeForStudent($query, $studentId)
    {
        $student = Student::findOrFail($studentId);
        return $query->where(function($q) use ($student) {
            $q->where('class_id', $student->class_id)
              ->where('section_id', $student->section_id);
        });
    }

    public function scopeForDay($query, $day)
    {
        return $query->where('day_of_week', strtolower($day));
    }

    public function scopeForDate($query, $date)
    {
        $dayOfWeek = strtolower($date->format('l'));
        $dateStr = $date->format('Y-m-d');
    
        return $query->where(function($q) use ($dayOfWeek, $dateStr) {
            $q->where(function($q) use ($dayOfWeek) {
                $q->where('day_of_week', $dayOfWeek)
                  ->where('is_recurring', true);
            })->orWhere('specific_date', $dateStr);
        })->where('is_holiday', false);
    }
}