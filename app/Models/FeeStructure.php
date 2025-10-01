<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = ['fee_type_id', 'study_year_id', 'grade_level_id', 'amount'];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function feeType()
    {
        return $this->belongsTo(FeeType::class);
    }

    public function studyYear()
    {
        return $this->belongsTo(StudyYear::class);
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }
}
