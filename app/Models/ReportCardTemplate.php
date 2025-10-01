<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReportCardTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'type',
        'description',
        'template_view_path',
        'header_content',
        'footer_content',
        'layout_options',
        'grade_level_id',
        'is_default',
        'logo_path',
        'is_active',
    ];

    protected $casts = [
        'layout_options' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];


    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function generatedReports()
    {
        return $this->hasMany(GeneratedReportCard::class);
    }
}