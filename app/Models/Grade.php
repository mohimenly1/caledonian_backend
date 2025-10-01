<?php

// app/Models/Grade.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = [
        'student_id', 'subject_id', 'term_id', 'teacher_id', 'exam_id', 'score', 'remarks'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class)->with(['class', 'section']);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
    
}
