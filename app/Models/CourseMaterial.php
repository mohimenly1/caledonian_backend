<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'course_offering_id',
        'term_id',
        'uploader_teacher_id',
        'title',
        'description',
        'material_type',
        'content_path_or_url_or_text',
        'publish_date',
        'is_visible_to_students',
    ];

    protected $casts = [
        'publish_date' => 'datetime',
        'is_visible_to_students' => 'boolean',
    ];

    // Relationships
    public function courseOffering()
    {
        return $this->belongsTo(CourseOffering::class);
    }

    public function term()
    {
        return $this->belongsTo(Term::class);
    }

    public function uploaderTeacher()
    {
        return $this->belongsTo(User::class, 'uploader_teacher_id');
    }

    // // Accessor for school_id via courseOffering
    // public function getSchoolIdAttribute()
    // {
    //     return $this->courseOffering->school_id ?? null;
    // }
}