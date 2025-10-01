<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizQuestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assessment_id',
        'question_text',
        'question_type',
        'points',
        'order',
        'hint',
    ];

    protected $casts = [
        'points' => 'decimal:2',
    ];

    // Relationships
    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function options() // For multiple_choice or true_false
    {
        return $this->hasMany(QuizQuestionOption::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentQuizAnswer::class);
    }

    // Accessor for school_id via assessment
    public function getSchoolIdAttribute()
    {
        return $this->assessment->school_id ?? null;
    }
}