<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Term extends Model
{
    protected $fillable = ['name', 'study_year_id', 'start_date', 'end_date'];

    public function studyYear()
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function grades()
    {
        return $this->hasMany(Grade::class);
    }
    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function studentTermSubjectGrades()
    {
        return $this->hasMany(StudentTermSubjectGrade::class);
    }

    public function courseMaterials()
    {
        return $this->hasMany(CourseMaterial::class); // If materials can be term-specific
    }
}