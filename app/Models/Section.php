<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'class_id'];

    public function class()
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'class_section_subjects', 'section_id', 'subject_id')
            ->withPivot('class_id');
    }


    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_section', 'section_id', 'employee_id');
    }

    // public function courseOfferings()
    // {
    //     return $this->hasMany(CourseOffering::class);
    // }



    public function timetables()
    {
        return $this->hasMany(Timetable::class);
    }
    
}
