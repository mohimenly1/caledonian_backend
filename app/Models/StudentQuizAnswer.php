<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentQuizAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_assessment_score_id',
        'quiz_question_id',
        'quiz_question_option_id',
        'answer_text',
        'is_marked_correct',
        'points_awarded',
    ];

    protected $casts = [
        'is_marked_correct' => 'boolean',
        'points_awarded' => 'decimal:2',
    ];

    // Relationships
    public function studentAssessmentScore() // The overall quiz submission/score record
    {
        return $this->belongsTo(StudentAssessmentScore::class);
    }

    public function quizQuestion()
    {
        return $this->belongsTo(QuizQuestion::class);
    }

    public function chosenOption() // If it was a multiple choice question
    {
        return $this->belongsTo(QuizQuestionOption::class, 'quiz_question_option_id');
    }

    // Convenience accessors
    public function getStudentAttribute()
    {
        return $this->studentAssessmentScore->student;
    }

    public function getAssessmentAttribute()
    {
        return $this->quizQuestion->assessment;
    }
}


/*


ملاحظة هامة: الدرجة الإجمالية للاختبار الإلكتروني للطالب (student_assessment_scores.score_obtained) سيتم حسابها عادةً من مجموع points_awarded في جدول student_quiz_answers المرتبطة بذلك student_assessment_score_id.

*/