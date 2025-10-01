<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseOffering extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'subject_id',
        'class_id',
        'section_id',
        'study_year_id',
     
    ];

    // Relationships


    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass() // Using 'schoolClass' to avoid conflict if user later uses 'Class'
    {
        return $this->belongsTo(ClassRoom::class, 'class_id'); // Assuming model name is Classes for 'classes' table
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function studyYear()
    {
        return $this->belongsTo(StudyYear::class);
    }

    // public function teacherAssignments()
    // {
    //     return $this->hasMany(TeacherCourseAssignment::class);
    // }

    public function teachers()
    {
        return $this->belongsToMany(User::class, 'teacher_course_assignments', 'course_offering_id', 'teacher_id')
                    ->wherePivot('role', 'Primary Teacher'); // Example to get primary teachers
                    // You can add other conditions or retrieve all assigned teachers
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function courseMaterials()
    {
        return $this->hasMany(CourseMaterial::class);
    }

    public function studentTermSubjectGrades()
    {
        return $this->hasMany(StudentTermSubjectGrade::class);
    }

    public function studentAttendances()
    {
        return $this->hasMany(AttendanceProcess::class);
    }
}