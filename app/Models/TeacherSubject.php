<?php

// app/Models/TeacherSubject.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherSubject extends Model
{
    protected $fillable = ['teacher_id', 'subject_id', 'class_id', 'section_id', 'study_year_id'];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }
    
    

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function studyYear()
    {
        return $this->belongsTo(StudyYear::class);
    }
}
