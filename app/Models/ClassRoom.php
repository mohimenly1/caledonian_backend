<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    use HasFactory;
    protected $table = 'classes';
    protected $fillable = ['name', 'description',
    'grade_level_id',
        'study_year_id',
        'is_active'
];

public function students()
{
    // --- التعديل الرئيسي هنا ---
    // تم تحديد المفتاح الأجنبي الصحيح 'class_id' بشكل صريح
    return $this->hasMany(Student::class, 'class_id');
}

    public function sections()
    {
        return $this->hasMany(Section::class,'class_id');
    }

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_class', 'class_id', 'employee_id');
    }

    public function gradeLevel()
{
    return $this->belongsTo(GradeLevel::class);
}

public function subjects()
{
    return $this->belongsToMany(Subject::class, 'class_section_subjects', 'class_id', 'subject_id')
        ->withPivot('section_id');
}


public function studyYear()
{
    return $this->belongsTo(StudyYear::class);
}

public function courseOfferings()
{
    return $this->hasMany(CourseOffering::class, 'class_id');
}

}
