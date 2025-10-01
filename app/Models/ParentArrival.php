<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentArrival extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'scanned_by_user_id',
        'message',
    ];

    public function parent()
    {
        return $this->belongsTo(ParentInfo::class, 'parent_id');
    }

    public function scanner()
    {
        return $this->belongsTo(User::class, 'scanned_by_user_id');
    }
}