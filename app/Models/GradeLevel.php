<?php

// app/Models/GradeLevel.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeLevel extends Model
{
    protected $fillable = ['name', 'description'];

    public function classes()
    {
        return $this->hasMany(ClassRoom::class);
    }
    public function gradingPolicies()
    {
        return $this->hasMany(GradingPolicy::class); // A policy might apply to a grade level
    }
}
