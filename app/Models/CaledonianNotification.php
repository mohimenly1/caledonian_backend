<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaledonianNotification extends Model
{
    use HasFactory;

    protected $table = 'caledonian_notifications';

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'data',
        'read',
    ];

    protected $casts = [
        'data' => 'array',
        'read' => 'boolean', // This will convert 0/1 to false/true
 
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    
}