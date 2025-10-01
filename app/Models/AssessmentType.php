<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AssessmentType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'default_max_score',
        'is_summative',
        'requires_submission_file',
    ];

    protected $casts = [
        'is_summative' => 'boolean',
        'requires_submission_file' => 'boolean',
    ];



    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function gradingPolicyComponents()
    {
        return $this->hasMany(GradingPolicyComponent::class);
    }
}