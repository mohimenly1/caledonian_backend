<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentEvaluation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'student_id',
        'study_year_id',
        'term_id',
        'evaluation_item_id',
        'evaluated_by_user_id',
        'evaluation_date',
        'evaluation_value',
        'comment_text',
    ];
    protected $casts = ['evaluation_date' => 'date'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function studyYear()
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function evaluationItem()
    {
        return $this->belongsTo(EvaluationItem::class);
    }

    public function evaluatedByUser()
    {
        return $this->belongsTo(User::class, 'evaluated_by_user_id');
    }

    // public function getSchoolIdAttribute()
    // {
    //     return $this->evaluationItem->school_id ?? ($this->student->school_id ?? null); // Infer school ID
    // }
}