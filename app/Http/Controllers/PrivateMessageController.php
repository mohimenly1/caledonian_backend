<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\PrivateConversation;
use App\Models\User;
use App\Models\MessageStatus;
use App\Notifications\NewPrivateMessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class PrivateMessageController extends Controller
{
    // Get all messages for a private conversation
    public function getMessages($conversationId)
    {
        $user = Auth::user();
        
        // Check if user is part of the conversation
        $conversation = PrivateConversation::where('id', $conversationId)
            ->where(function($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->first();
            
        if (!$conversation) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Determine the other user
        $otherUserId = ($conversation->user1_id == $user->id) 
            ? $conversation->user2_id 
            : $conversation->user1_id;
        
        $otherUser = User::find($otherUserId);

        // Get messages with relationships
        $messages = Message::where('private_conversation_id', $conversationId)
            ->with([
                'sender:id,name,avatar_url,last_activity',
                'repliedMessage.sender:id,name'
            ])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'messages' => $messages,
            'other_user' => $otherUser,
            'conversation' => $conversation
        ]);
    }

    // Send private message
    public function sendMessage(Request $request, $conversationId)
    {
        $user = Auth::user();
        
        // Check if user is part of the conversation
        $conversation = PrivateConversation::where('id', $conversationId)
            ->where(function($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->first();
            
        if (!$conversation) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Determine the other user in the conversation
        $otherUserId = ($conversation->user1_id == $user->id) ? $conversation->user2_id : $conversation->user1_id;
        
        // Check if users have blocked each other
        if ($user->blockedUsers()->where('blocked_id', $otherUserId)->exists() ||
            $user->blockedByUsers()->where('blocker_id', $otherUserId)->exists()) {
            return response()->json(['message' => 'You cannot message this user'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'content' => 'required_without:media|string',
            'media' => 'required_without:content|file|mimes:jpg,jpeg,png,gif,mp4,mov,avi,pdf,doc,docx,mp3,wav,xlsx,webm,aac,adts,m4a',
            'message_type' => 'sometimes|in:text,image,video,audio,document',
            'reply_to_message_id' => 'nullable|exists:messages,id',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $messageData = [
            'sender_id' => $user->id,
            'private_conversation_id' => $conversationId,
            'content' => $request->content,
            'message_type' => $request->message_type ?? 'text',
            'reply_to_message_id' => $request->reply_to_message_id,
        ];
        
        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $originalName = $file->getClientOriginalName();
            $path = $file->storeAs('private_chat_media', $originalName, 'public');
            $extension = strtolower($file->getClientOriginalExtension());
            $mimeType = $file->getMimeType();
        
            $messageData['media_path'] = $path;
        
            // Determine message type
            if (in_array($extension, ['aac', 'm4a', 'mp3', 'wav'])) {
                $messageData['message_type'] = 'audio';
            } elseif (str_starts_with($mimeType, 'image/')) {
                $messageData['message_type'] = 'image';
            } elseif (str_starts_with($mimeType, 'video/')) {
                $messageData['message_type'] = 'video';
            } else {
                $messageData['message_type'] = 'document';
            }
        }
        
        $message = Message::create($messageData);
        
        // تحميل معلومات الرسالة المردودة مع الاستجابة
        $message->load(['sender:id,name,avatar_url,last_activity', 'repliedMessage.sender:id,name']);
        
        // Create message status for the recipient
        MessageStatus::create([
            'message_id' => $message->id,
            'user_id' => $otherUserId,
            'is_read' => false,
        ]);
        
        // Notify the recipient
        $recipient = User::find($otherUserId);
        $content = $message->content ?? $this->getMediaTypeForContent($message->message_type) ?? 'New message';
        
        if ($recipient) {
            // Store notification in database
            $recipient->caledonianNotifications()->create([
                'title' => 'New message from ' . $user->name,
                'body' => $content,
                'data' => [
                    'type' => 'private_message',
                    'conversation_id' => $conversationId,
                    'message_id' => $message->id,
                    'sender_id' => $user->id,
                ],
            ]);
            
            // Send FCM notification if token exists
            if ($recipient->fcm_token) {
                $recipient->notify(new NewPrivateMessageNotification($message, $user));
            }
        }
        
        // Update conversation last_message_at
        $conversation->update([
            'last_message_at' => now(),
            'last_message_id' => $message->id,
        ]);
        
        return response()->json([
            'success' => true,
            'message' => $message
        ], 201);
    }
    
    protected function getMediaTypeForContent(string $messageType): ?string
    {
        return match($messageType) {
            'image' => 'Sent an image',
            'video' => 'Sent a video',
            'audio' => 'Sent a voice message',
            'document' => 'Sent a document',
            default => null,
        };
    }

    // Get specific message for private conversation
    public function getMessage($conversationId, $messageId)
    {
        $user = Auth::user();
        
        // Check if user is part of the conversation
        $conversation = PrivateConversation::where('id', $conversationId)
            ->where(function($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->first();
            
        if (!$conversation) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Find the message by ID and ensure it belongs to the conversation
        $message = Message::where('id', $messageId)
            ->where('private_conversation_id', $conversationId)
            ->with([
                'sender:id,name,avatar_url,last_activity',
                'repliedMessage.sender:id,name'
            ])
            ->first();
        
        if (!$message) {
            Log::warning("Private message not found", [
                'message_id' => $messageId,
                'conversation_id' => $conversationId,
                'user_id' => $user->id
            ]);
            
            return response()->json([
                'error' => 'Message not found or deleted',
                'is_deleted' => true,
                'content' => 'تم حذف الرسالة',
                'sender' => ['name' => 'مستخدم'],
                'message_type' => 'text'
            ], 404);
        }
        
        Log::info("Private message found", [
            'message_id' => $message->id,
            'conversation_id' => $conversationId
        ]);
        
        return response()->json($message);
    }

    // Update private message
    public function updateMessage(Request $request, $messageId)
    {
        $user = Auth::user();
        
        // Find the message
        $message = Message::where('id', $messageId)
            ->where('sender_id', $user->id)
            ->whereNotNull('private_conversation_id')
            ->first();
            
        if (!$message) {
            return response()->json(['message' => 'Message not found or unauthorized'], 404);
        }
        
        // Check if message is not too old (e.g., 15 minutes)
        $editTimeLimit = now()->subMinutes(15);
        if ($message->created_at < $editTimeLimit) {
            return response()->json(['message' => 'Message can only be edited within 15 minutes'], 403);
        }
        
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        // Update message content
        $message->update([
            'content' => $request->content,
            'edited_at' => now(),
            'is_edited' => true,
        ]);
        
        // Reload relationships
        $message->load(['sender:id,name,avatar_url,last_activity', 'repliedMessage.sender:id,name']);
        
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    // Delete private message (soft delete)
    public function deleteMessage($messageId)
    {
        $user = Auth::user();
        
        // Find the message
        $message = Message::where('id', $messageId)
            ->where('sender_id', $user->id)
            ->whereNotNull('private_conversation_id')
            ->first();
            
        if (!$message) {
            return response()->json(['message' => 'Message not found or unauthorized'], 404);
        }
        
        // Soft delete - تحديث الرسالة بدلاً من الحذف الكامل
        $message->update([
            'content' => 'This message was deleted',
            'is_deleted' => true,
            'deleted_at' => now(),
            'media_path' => null, // إزالة الوسائط إذا كانت موجودة
            'message_type' => 'text', // تغيير النوع إلى نص
        ]);
        
        // إعادة تحميل العلاقات
        $message->load(['sender:id,name,avatar_url,last_activity']);
        
        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully',
            'deleted_message' => $message
        ]);
    }

    // Get message for editing
    public function getMessageForEdit($messageId)
    {
        $user = Auth::user();
        
        // Find the message
        $message = Message::where('id', $messageId)
            ->where('sender_id', $user->id)
            ->whereNotNull('private_conversation_id')
            ->first();
            
        if (!$message) {
            return response()->json(['message' => 'Message not found or unauthorized'], 404);
        }
        
        // Check edit time limit
        $editTimeLimit = now()->subMinutes(15);
        if ($message->created_at < $editTimeLimit) {
            return response()->json(['message' => 'Edit time limit expired'], 403);
        }
        
        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    // Mark private messages as read
    public function markAsRead($conversationId)
    {
        $user = Auth::user();
        
        // Check if user is part of the conversation
        $conversation = PrivateConversation::where('id', $conversationId)
            ->where(function($query) use ($user) {
                $query->where('user1_id', $user->id)
                      ->orWhere('user2_id', $user->id);
            })
            ->first();
            
        if (!$conversation) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Mark all received messages in this conversation as read
        $unreadMessages = Message::where('private_conversation_id', $conversationId)
            ->where('sender_id', '!=', $user->id)
            ->whereDoesntHave('statuses', function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->where('is_read', true);
            })
            ->get();
        
        foreach ($unreadMessages as $message) {
            MessageStatus::updateOrCreate(
                [
                    'message_id' => $message->id,
                    'user_id' => $user->id,
                ],
                [
                    'is_read' => true,
                    'read_at' => now(),
                ]
            );
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Messages marked as read',
            'count' => $unreadMessages->count()
        ]);
    }
}