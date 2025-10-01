<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sender_id', 'recipient_id', 'chat_group_id', 'content', 
        'message_type', 'media_path', 'is_read', 'read_at',
        'private_conversation_id', // Add this
        'is_system_message', // add this
        'system_message_type' // add this (e.g., 'member_added', 'member_removed', etc.)
    ];

    protected $casts = [
        'is_system_message' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime'
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function group()
    {
        return $this->belongsTo(ChatGroup::class, 'chat_group_id');
    }

    public function statuses()
    {
        return $this->hasMany(MessageStatus::class);
    }
    public function privateConversation()
{
    return $this->belongsTo(PrivateConversation::class);
}

public function getConversationAttribute()
{
    return $this->chat_group_id 
        ? $this->group 
        : $this->privateConversation;
}

public function getRecipientAttribute()
{
    if ($this->chat_group_id) {
        return $this->group;
    }
    
    // For private conversations, the recipient is the other user
    if ($this->private_conversation_id) {
        return $this->privateConversation->otherUser($this->sender);
    }
    
    return null;
}
}