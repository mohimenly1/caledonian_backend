<?php

// app/Models/PrivateConversation.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrivateConversation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['user1_id', 'user2_id', 'last_message_at'];

    public function user1()
    {
        return $this->belongsTo(User::class, 'user1_id');
    }

    public function user2()
    {
        return $this->belongsTo(User::class, 'user2_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'private_conversation_id');
    }

    public function otherUser(User $user)
    {
        return $user->id === $this->user1_id ? $this->user2 : $this->user1;
    }

    public function scopeBetweenUsers($query, $user1Id, $user2Id)
    {
        return $query->where(function($q) use ($user1Id, $user2Id) {
            $q->where('user1_id', $user1Id)
              ->where('user2_id', $user2Id);
        })->orWhere(function($q) use ($user1Id, $user2Id) {
            $q->where('user1_id', $user2Id)
              ->where('user2_id', $user1Id);
        });
    }
}