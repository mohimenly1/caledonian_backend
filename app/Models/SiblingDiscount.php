<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiblingDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_year_id', 'number_of_siblings', 'discount_percentage'
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
    ];

    public function studyYear()
    {
        return $this->belongsTo(StudyYear::class);
    }
}

