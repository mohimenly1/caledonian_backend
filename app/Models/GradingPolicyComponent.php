<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GradingPolicyComponent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grading_policy_id',
        'assessment_type_id',
        'weight_percentage',
        'min_items_required',
        'max_items_counted',
        // 'drop_lowest_score',
    ];

    protected $casts = [
        'weight_percentage' => 'decimal:2',
        // 'drop_lowest_score' => 'boolean',
    ];

    // Relationships
    public function gradingPolicy()
    {
        return $this->belongsTo(GradingPolicy::class);
    }

    public function assessmentType()
    {
        return $this->belongsTo(AssessmentType::class);
    }
}