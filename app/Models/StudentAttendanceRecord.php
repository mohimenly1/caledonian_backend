<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
// use PhpParser\Builder\Class_;

class StudentAttendanceRecord extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'student_id',
        'record_state',
        'user_id',
        'class_id',
        'section_id',
    ];

    public function student()
    {
        return $this->BelongsTo(Student::class);
    }
    public function user()
    {
        return $this->BelongsTo(User::class);
    }
    public function class()
    {
        return $this->BelongsTo(ClassRoom::class);
    }
    public function section()
    {
        return $this->BelongsTo(Section::class);
    }
}
