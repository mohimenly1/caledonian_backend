<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationArea extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [ 'name', 'description', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    // public function school()
    // {
    //     return $this->belongsTo(School::class);
    // }

    public function evaluationItems()
    {
        return $this->hasMany(EvaluationItem::class);
    }
}