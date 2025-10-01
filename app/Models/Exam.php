<?php

// app/Models/Exam.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'name', 'term_id', 'exam_type_id', 'teacher_id', 
        'class_id', 'subject_id', 'start_time', 'end_time',
        'duration_minutes', 'total_score', 'instructions', 'is_published'
    ];

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function type()
    {
        return $this->belongsTo(ExamType::class, 'exam_type_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function class()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}