<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; // Use Model for pivot with extra attributes

class StudentParentRelationship extends Model
{
    use HasFactory;

    protected $table = 'student_parent_relationships';

    protected $fillable = [
        'student_id',
        'parent_id',
        'relationship_type',
    ];

    // No need for explicit relationships here if primarily used as pivot data,
    // but if you query this table directly:
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function parent() // Referencing ParentModel
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }
}