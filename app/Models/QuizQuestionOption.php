<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuizQuestionOption extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quiz_question_id',
        'option_text',
        'is_correct_answer',
        'order',
    ];

    protected $casts = [
        'is_correct_answer' => 'boolean',
    ];

    // Relationships
    public function quizQuestion()
    {
        return $this->belongsTo(QuizQuestion::class);
    }
}