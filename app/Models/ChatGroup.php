<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ChatGroup extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'description', 'creator_id', 'is_public', 'class_id', 'section_id','image_path'
    ];

    public function getImageUrlAttribute()
    {
        if (!$this->image_path) {
            return asset('/cis_group.png');
        }
        
        return Storage::url($this->image_path);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members', 'group_id', 'user_id')
            ->withPivot(['is_admin', 'is_blocked', 'blocked_by', 'blocked_at']);
    }

    public function class()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
// In app/Models/ChatGroup.php
public function messages()
{
    return $this->hasMany(Message::class, 'chat_group_id'); // Explicitly specify the foreign key
}

protected $casts = [
    'is_public' => 'boolean',
    // Add other casts if needed
];
}