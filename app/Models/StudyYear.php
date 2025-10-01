<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class StudyYear extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
    ];

    public function studyYear()
{
    return $this->belongsTo(StudyYear::class, 'study_year_id');
}
 // Relationships
 public function terms()
 {
     return $this->hasMany(Term::class);
 }

//  public function schoolClasses()
//  {
//      return $this->hasMany(SchoolClass::class); // Assuming SchoolClass is the model for classes
//  }

 public function courseOfferings()
 {
     return $this->hasMany(CourseOffering::class);
 }

}


