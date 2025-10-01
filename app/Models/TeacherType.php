<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherType extends Model
{
    use HasFactory;
    protected $table = 'teachers_type';
    protected $fillable = [
        'type',
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_teacher_type', 'teacher_type_id', 'employee_id');
    }

    public function sections()
    {
        return $this->belongsToMany(Section::class, 'section_subject', 'subject_id', 'section_id');
    }
    public function grades()
    {
        return $this->hasMany(Grade::class, 'subject_id');
    }

}
