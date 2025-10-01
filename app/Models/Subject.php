<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\TeacherSubject;

class Subject extends Model
{
    protected $fillable = ['name', 'code', 'description','subject_category_id'];

    public function teacherSubjects()
    {
        return $this->hasMany(TeacherSubject::class);
    }

    public function teachers()
    {
        return $this->belongsToMany(Employee::class, 'employee_subject', 'subject_id', 'employee_id')
            ->withTimestamps();
    }
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    public function category()
{
    return $this->belongsTo(SubjectCategory::class, 'subject_category_id');
}

public function timetables()
{
    return $this->hasMany(Timetable::class);
}

public function classes()
{
    return $this->belongsToMany(ClassRoom::class, 'class_section_subjects', 'subject_id', 'class_id')
        ->withPivot('section_id');
}
public function sections()
{
    return $this->belongsToMany(Section::class, 'class_section_subjects', 'subject_id', 'section_id')
        ->withPivot('class_id');
}

// public function gradingPolicies()
// {
//     return $this->hasMany(GradingPolicy::class); // A policy might apply to a specific subject across grade levels
// قد تنطبق السياسة على موضوع محدد عبر مستويات الصف
// }


// public function courseOfferings()
// {
//     return $this->hasMany(CourseOffering::class);
// }
}