<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFinalYearGrade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'subject_id',
        'study_year_id',
        'class_id',
        'overall_numerical_score_percentage',
        'final_grading_scale_id',
        'final_remarks',
        'promotion_status',
        'is_finalized',
        'finalized_by_user_id',
        'finalized_timestamp',
    ];

    protected $casts = [
        'overall_numerical_score_percentage' => 'decimal:2',
        'is_finalized' => 'boolean',
        'finalized_timestamp' => 'datetime',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function studyYear()
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id'); // Assuming model 'Classes' for 'classes' table
    }

    public function finalGradingScaleEntry()
    {
        return $this->belongsTo(GradingScale::class, 'final_grading_scale_id');
    }

    public function finalizedByUser()
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }

    // Accessor to get school_id via studyYear or class for convenience
    // public function getSchoolIdAttribute()
    // {
    //     if ($this->schoolClass && $this->schoolClass->school_id) { // Assuming SchoolClass model will have school_id through studyYear
    //          return $this->schoolClass->school_id;
    //     }
    //     // Fallback or if class_id is null
    //     // Requires StudyYear model to have school_id if classes don't directly, or some other link
    //     // For now, this is a placeholder. Ensure your SchoolClass or StudyYear model can provide school_id
    //     return $this->studyYear->school_id ?? null; // This assumes study_year has a school_id or a way to get it.
    // }
}