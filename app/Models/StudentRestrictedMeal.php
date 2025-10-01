<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentRestrictedMeal extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'meal_id'
    ];


    public function meal()
    {
        return $this->belongsTo(Meal::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
