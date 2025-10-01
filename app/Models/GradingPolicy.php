<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradingPolicy extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'study_year_id',
        'grade_level_id',
        'subject_id',
        'course_offering_id',
        'is_default_for_school',
    ];

    protected $casts = [
        'is_default_for_school' => 'boolean',
    ];


    public function studyYear()
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function components()
    {
        return $this->hasMany(GradingPolicyComponent::class);
    }

    public function gradingScales() // A policy might use a specific grading scale
    {
        return $this->hasMany(GradingScale::class); // If grading scales are tied to policies
    }
}