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
        'system_message_type', // add this (e.g., 'member_added', 'member_removed', etc.)
        'reply_to_message_id', // تأكد من وجود هذا الحقل
        'is_edited', // إضافة هذا الحقل
        'edited_at', // إضافة هذا الحقل
        'is_deleted',
        'deleted_at',
    ];

    protected $casts = [
        'is_system_message' => 'boolean',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'edited_at' => 'datetime',
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
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

public function repliedMessage()
{
    return $this->belongsTo(Message::class, 'reply_to_message_id')->withTrashed();;
}

// العلاقة العكسية للرسائل التي تم الرد عليها
public function replies()
{
    return $this->hasMany(Message::class, 'reply_to_message_id');
}

public function getConversationAttribute()
{
    return $this->chat_group_id
        ? $this->group
        : $this->privateConversation;
}

public function canBeEdited()
{
    $editTimeLimit = now()->subMinutes(15);
    return $this->created_at >= $editTimeLimit;
}
public function getIsEditedAttribute($value)
{
    return (bool) $value; // تحويل null إلى false
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
