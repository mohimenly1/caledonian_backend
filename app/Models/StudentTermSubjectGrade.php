<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentTermSubjectGrade extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'course_offering_id',
        'term_id',
        'weighted_average_score_percentage',
        'grading_scale_id',
        'teacher_overall_remarks',
        'rank_in_class',
        'rank_in_section',
        'is_finalized',
        'finalized_by_user_id',
        'finalized_timestamp',
    ];

    protected $casts = [
        'weighted_average_score_percentage' => 'decimal:2',
        'is_finalized' => 'boolean',
        'finalized_timestamp' => 'datetime',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function gradingScaleEntry() // The specific letter grade, e.g., "A+"
    {
        return $this->belongsTo(GradingScale::class, 'grading_scale_id');
    }

    public function finalizedByUser()
    {
        return $this->belongsTo(User::class, 'finalized_by_user_id');
    }

    // Accessor to get school_id via courseOffering for convenience
    // public function getSchoolIdAttribute()
    // {
    //     return $this->courseOffering->school_id ?? null;
    // }
}