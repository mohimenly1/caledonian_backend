<?php

// app/Http/Controllers/PrivateConversationController.php
namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\PrivateConversation;
use App\Models\User;
use App\Notifications\NewPrivateMessageNotificationLatest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PrivateConversationController extends Controller
{

    // أضف هذه الدوال إلى PrivateConversationController

public function updateMessage(Request $request, $messageId)
{
    try {
        $message = Message::findOrFail($messageId);
        $user = Auth::user();

        // التحقق من أن المستخدم هو مرسل الرسالة
        if ($message->sender_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - You can only edit your own messages'
            ], 403);
        }

        // التحقق من أن الرسالة نصية وليست وسائط
        if ($message->message_type != 'text') {
            return response()->json([
                'success' => false,
                'message' => 'Only text messages can be edited'
            ], 400);
        }

        // التحقق من أن الوقت المسموح للتعديل لم ينتهي (15 دقيقة مثلاً)
        // $editTimeLimit = now()->subMinutes(15);
        // if ($message->created_at < $editTimeLimit) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Message can only be edited within 15 minutes of sending'
        //     ], 400);
        // }

        $request->validate([
            'content' => 'required|string|min:1'
        ]);

        $message->update([
            'content' => $request->input('content'),
            'is_edited' => true,
            'edited_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => $message->load('sender')
        ]);

    } catch (\Exception $e) {
        Log::error('Error updating message', [
            'message_id' => $messageId,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to update message: ' . $e->getMessage()
        ], 500);
    }
}

public function deleteMessage($messageId)
{
    try {
        $message = Message::withTrashed()->find($messageId);
        
        if (!$message) {
            Log::warning("Message not found for deletion", ['message_id' => $messageId]);
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        $user = Auth::user();

        // التحقق من أن المستخدم هو مرسل الرسالة
        if ($message->sender_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized - You can only delete your own messages'
            ], 403);
        }

        // ✅ احتفظ بالمحتوى الأصلي - لا تغيير في المحتوى
        // فقط احذف الوسائط إذا كانت موجودة
        if ($message->media_path) {
            $message->update([
                'media_path' => null, // إزالة الوسائط فقط
            ]);
        }
        
        // ✅ الحذف باستخدام SoftDeletes فقط
        $message->delete();

        Log::info("Message deleted successfully using SoftDeletes - Content preserved", [
            'message_id' => $messageId,
            'user_id' => $user->id,
            'deleted_at' => $message->deleted_at,
            'original_content_preserved' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);

    } catch (\Exception $e) {
        Log::error('Error deleting message', [
            'message_id' => $messageId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Failed to delete message: ' . $e->getMessage()
        ], 500);
    }
}
public function getMessageForEdit($messageId)
{
    try {
        $message = Message::findOrFail($messageId);
        $user = Auth::user();

        // التحقق من أن المستخدم هو مرسل الرسالة
        if ($message->sender_id != $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // التحقق من إمكانية التعديل
        $editTimeLimit = now()->subMinutes(15);
        $canEdit = $message->created_at >= $editTimeLimit && $message->message_type == 'text';

        return response()->json([
            'success' => true,
            'message' => $message->load('sender'),
            'can_edit' => $canEdit,
            'edit_time_limit' => 15 // دقائق
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch message: ' . $e->getMessage()
        ], 500);
    }
}
    public function getOrCreateConversation($userId)
    {
        $otherUser = User::findOrFail($userId);
        $currentUser = Auth::user();

        // Check if conversation already exists
        $conversation = PrivateConversation::betweenUsers($currentUser->id, $otherUser->id)->first();

        if (!$conversation) {
            // Create new conversation
            $conversation = PrivateConversation::create([
                'user1_id' => $currentUser->id,
                'user2_id' => $otherUser->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'conversation' => $conversation->load(['user1', 'user2']),
        ]);
    }

    public function index()
    {
        $user = Auth::user();
        
        $conversations = PrivateConversation::where('user1_id', $user->id)
            ->orWhere('user2_id', $user->id)
            ->with(['user1', 'user2', 'messages' => function($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('last_message_at', 'desc')
            ->get();
    
        return response()->json([
            'success' => true,
            'conversations' => $conversations->map(function($conv) use ($user) {
                $lastMessage = $conv->messages->first();
                
                return [
                    'id' => $conv->id,
                    'other_user' => $conv->otherUser($user),
                    'last_message' => $lastMessage ? [
                        'id' => $lastMessage->id,
                        'content' => $lastMessage->content,
                        'sender' => [
                            'id' => $lastMessage->sender_id,
                            'name' => $lastMessage->sender->name
                        ],
                        'created_at' => $lastMessage->created_at->toDateTimeString()
                    ] : null,
                    'unread_count' => $conv->messages()
                        ->where('sender_id', '!=', $user->id)
                        ->where('is_read', false)
                        ->count(),
                    'last_message_at' => $conv->last_message_at,
                ];
            }),
        ]);
    }

// In PrivateConversationController.php
// في PrivateConversationController - تحديث دالة messages:
public function messages($conversationId)
{
    Log::info('Fetching messages for conversation', ['conversation_id' => $conversationId]);
    
    $conversation = PrivateConversation::findOrFail($conversationId);
    $user = Auth::user();

    if ($conversation->user1_id != $user->id && $conversation->user2_id != $user->id) {
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $otherUser = $conversation->otherUser($user);
    
    // ✅ جلب الرسائل مع الردود والرسائل المحذوفة
    $messages = $conversation->messages()
        ->with([
            'sender:id,name,photo,last_activity',
            'repliedMessage.sender:id,name,photo',
            'repliedMessage' => function($query) {
                $query->withTrashed(); // ✅ تضمين الرسائل المحذوفة
            }
        ])
        ->orderBy('created_at', 'asc')
        ->get()
        ->map(function ($message) {
            $messageData = [
                'id' => $message->id,
                'content' => $message->content,
                'message_type' => $message->message_type,
                'sender' => $message->sender,
                'is_read' => $message->is_read,
                'is_edited' => $message->is_edited,
                'created_at' => $message->created_at,
                'reply_to_message_id' => $message->reply_to_message_id,
                'replied_message' => $message->repliedMessage,
                'media_path' => $message->media_path,
            ];

            // ✅ إذا كانت الرسالة محذوفة
            if ($message->trashed()) {
                $messageData['is_deleted'] = true;
                $messageData['deleted_at'] = $message->deleted_at;
                // ✅ الحفاظ على البيانات الأساسية فقط
                $messageData['content'] = 'تم حذف الرسالة';
                $messageData['message_type'] = 'text';
                $messageData['media_path'] = null;
                $messageData['reply_to_message_id'] = null;
                $messageData['replied_message'] = null;
            } else {
                $messageData['is_deleted'] = false;
                $messageData['deleted_at'] = null;
            }

            return $messageData;
        });

    $otherUserData = $otherUser->toArray();
    $otherUserData['is_online'] = $otherUser->isOnline();
    $otherUserData['last_seen'] = $otherUser->lastSeen();

    return response()->json([
        'success' => true,
        'messages' => $messages,
        'other_user' => $otherUserData,
    ]);
}

public function sendMessage(Request $request, $conversationId)
{
    Log::info('Starting message send process', [
        'conversation_id' => $conversationId,
        'request_data' => $request->all(),
        'files' => $request->hasFile('media') ? 'Yes' : 'No'
    ]);

    $request->validate([
        'content' => 'required_without:media|string|nullable',
        'message_type' => 'required|in:text,image,video,audio,document',
        'media' => 'required_if:message_type,image,video,audio,document|file|nullable',
        'reply_to_message_id' => 'nullable|exists:messages,id', // ✅ إضافة التحقق من صحة الرد
    ]);

    try {
        $conversation = PrivateConversation::findOrFail($conversationId);
        $user = Auth::user();

        Log::info('User and conversation verified', [
            'user_id' => $user->id,
            'conversation_users' => [$conversation->user1_id, $conversation->user2_id]
        ]);

        if ($conversation->user1_id != $user->id && $conversation->user2_id != $user->id) {
            Log::warning('Unauthorized message attempt', [
                'user_id' => $user->id,
                'conversation_id' => $conversationId
            ]);
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $otherUserId = $conversation->user1_id == $user->id ? $conversation->user2_id : $conversation->user1_id;

        $mediaPath = null;
        if ($request->hasFile('media')) {
            Log::debug('Processing media file upload');
            $mediaPath = $request->file('media')->store('private_messages', 'public');
            Log::debug('Media stored at public path: ' . $mediaPath);
        }

        // ✅ إضافة reply_to_message_id إلى بيانات الرسالة
        $messageData = [
            'sender_id' => $user->id,
            'recipient_id' => $otherUserId,
            'private_conversation_id' => $conversation->id,
            'content' => $request->input('content'),
            'message_type' => $request->input('message_type'),
            'media_path' => $mediaPath,
            'reply_to_message_id' => $request->input('reply_to_message_id'), // ✅ حفظ الرد
        ];

        Log::debug('Creating message with data:', $messageData);

        $message = Message::create($messageData);

        $conversation->update(['last_message_at' => now()]);

        Log::info('Message created successfully', [
            'message_id' => $message->id,
            'conversation_updated' => $conversation->last_message_at,
            'reply_to_message_id' => $request->input('reply_to_message_id') // ✅ تسجيل الرد
        ]);
        
        // ✨ --- بداية إضافة منطق الإشعارات --- ✨
        $recipient = User::find($otherUserId);

        if ($recipient && $recipient->fcm_token) {
            try {
                $recipient->notify(new NewPrivateMessageNotificationLatest($message, $user));
                Log::info('Push notification sent successfully.', ['recipient_id' => $recipient->id]);
            } catch (\Exception $e) {
                Log::error('Failed to send push notification.', [
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::warning('Recipient not found or does not have FCM token.', ['recipient_id' => $otherUserId]);
        }
        // --- نهاية إضافة منطق الإشعارات --- ✨

        // ✅ تحميل العلاقات بما في ذلك الرسالة المردودة
        $message->load(['sender', 'repliedMessage.sender']);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);

    } catch (\Exception $e) {
        Log::error('Message sending failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to send message: ' . $e->getMessage()
        ], 500);
    }
}

public function markAsRead($conversationId)
{
    $conversation = PrivateConversation::findOrFail($conversationId);
    $user = Auth::user();

    // Verify user is part of this conversation
    if ($conversation->user1_id != $user->id && $conversation->user2_id != $user->id) {
        Log::error("User {$user->id} unauthorized to mark messages as read in conversation {$conversationId}");
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    // Get the count of unread messages before updating
    $unreadCount = $conversation->messages()
        ->where('recipient_id', $user->id)
        ->where('is_read', false)
        ->count();

    Log::info("Marking {$unreadCount} messages as read for user {$user->id} in conversation {$conversationId}");

    // Mark all unread messages as read
    $updated = $conversation->messages()
        ->where('recipient_id', $user->id)
        ->where('is_read', false)
        ->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

    // Update the conversation's last activity if messages were marked as read
    if ($updated > 0) {
        $conversation->touch(); // Updates the updated_at timestamp
    }

    Log::info("Successfully marked {$updated} messages as read for user {$user->id}");

    return response()->json([
        'success' => true,
        'unread_count' => $unreadCount,
        'updated_count' => $updated,
    ]);
}

    public function unreadCount()
{
    $user = Auth::user();
    
    $totalUnread = PrivateConversation::where(function($q) use ($user) {
            $q->where('user1_id', $user->id)
              ->orWhere('user2_id', $user->id);
        })
        ->withCount(['messages as unread_messages_count' => function($q) use ($user) {
            $q->where('recipient_id', $user->id)
              ->where('is_read', false);
        }])
        ->get()
        ->sum('unread_messages_count');

    return response()->json([
        'success' => true,
        'total_unread' => $totalUnread
    ]);
}
}