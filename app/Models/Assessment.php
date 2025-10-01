<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assessment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_offering_id',
        'term_id',
        'assessment_type_id',
        'created_by_teacher_id',
        'title',
        'section_id', // --- الإضافة هنا ---
        'description',
        'max_score',
        'publish_date_time',
        'due_date_time',
        'grading_due_date_time',
        'is_online_quiz',
        'is_visible_to_students',
        'are_grades_published',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'publish_date_time' => 'datetime',
        'due_date_time' => 'datetime',
        'grading_due_date_time' => 'datetime',
        'is_online_quiz' => 'boolean',
        'is_visible_to_students' => 'boolean',
        'are_grades_published' => 'boolean',
    ];

    // Relationships
    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function assessmentType()
    {
        return $this->belongsTo(AssessmentType::class);
    }

    public function createdByTeacher()
    {
        return $this->belongsTo(User::class, 'created_by_teacher_id');
    }

    public function studentScores()
    {
        return $this->hasMany(StudentAssessmentScore::class);
    }



    // For online quizzes, you might have:
    public function quizQuestions()
    {
        return $this->hasMany(QuizQuestion::class);
    }
    public function section()
    {
        return $this->belongsTo(Section::class);
    }

}