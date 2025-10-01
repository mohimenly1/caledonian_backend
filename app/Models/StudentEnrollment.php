<?php

// app/Models/StudentEnrollment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    protected $fillable = ['student_id', 'class_id', 'section_id', 'study_year_id'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function class()
    {
        return $this->belongsTo(Classroom::class);
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
