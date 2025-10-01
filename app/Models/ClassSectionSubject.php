<?php

// app/Models/ClassSectionSubject.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassSectionSubject extends Model
{
    use SoftDeletes;
    protected $table = 'class_section_subjects';

    protected $fillable = ['class_id', 'section_id', 'subject_id'];

    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}