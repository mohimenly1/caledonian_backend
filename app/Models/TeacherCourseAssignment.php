<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherCourseAssignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'course_offering_id',
        'role',
        'section_id'
    ];

    // Relationships
    public function teacher() // User model for the teacher
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }
    public function section()
{
    return $this->belongsTo(Section::class);
}
}