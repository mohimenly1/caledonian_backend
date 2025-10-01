<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneratedReportCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'report_card_template_id',
        'report_type',
        'study_year_id',
        'term_id',
        'generation_date',
        'generated_by_user_id',
        'file_path',
        'data_snapshot_json',
        'version',
        'status',
        'notes',
    ];

    protected $casts = [
        'generation_date' => 'datetime',
        'data_snapshot_json' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function template()
    {
        return $this->belongsTo(ReportCardTemplate::class, 'report_card_template_id');
    }

    public function studyYear()
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function generatedByUser()
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }
}