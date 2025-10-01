<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'evaluation_area_id',
        'name',
        'description',
        'grading_type',
        // 'grading_scale_id',
        'is_active',
        'sort_order',
    ];
    protected $casts = ['is_active' => 'boolean'];

    public function evaluationArea()
    {
        return $this->belongsTo(EvaluationArea::class);
    }

    // public function gradingScale()
    // {
    //     return $this->belongsTo(GradingScale::class); // If you link to the main academic grading_scales
    // }

    public function studentEvaluations()
    {
        return $this->hasMany(StudentEvaluation::class);
    }

    public function getSchoolIdAttribute()
    {
        return $this->evaluationArea->school_id ?? null;
    }
}