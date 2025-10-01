<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HealthFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'age',
        'weight',
        'height',
        'blood_type',
        'medical_history',
        'hearing',
        'sight',
        'diabetes_mellitus',
        'food_allergies',
        'chronic_disease',
        'clinical_examination',
        'result_clinical_examination',
        'vaccinations',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
    
}
