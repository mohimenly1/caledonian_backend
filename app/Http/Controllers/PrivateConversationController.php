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
public function messages($conversationId)
{
    Log::info('Fetching messages for conversation', ['conversation_id' => $conversationId]);
    
    $conversation = PrivateConversation::findOrFail($conversationId);
    $user = Auth::user();

    if ($conversation->user1_id != $user->id && $conversation->user2_id != $user->id) {
        Log::warning('Unauthorized access attempt to conversation', [
            'user_id' => $user->id,
            'conversation_id' => $conversationId
        ]);
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
    }

    $otherUser = $conversation->otherUser($user);
    
    Log::debug('Fetching messages with pagination', [
        'conversation_id' => $conversationId,
        'user_id' => $user->id,
        'other_user_id' => $otherUser->id
    ]);

  $messages = $conversation->messages()
    ->with(['sender:id,name,photo,last_activity'])
    ->orderBy('created_at', 'asc')
    ->get();


    Log::info('Messages fetched successfully', [
        'conversation_id' => $conversationId,
        'message_count' => $messages->count()
    ]);

    // *** START OF FIX ***
    // 1. قم بتحويل كائن المستخدم الآخر إلى مصفوفة لجلب كل بياناته تلقائياً
    // هذا سيجلب (id, name, photo, gender, photo_url, etc.)
    $otherUserData = $otherUser->toArray();

    // 2. أضف الحقول المحسوبة ديناميكياً
    $otherUserData['is_online'] = $otherUser->isOnline();
    $otherUserData['last_seen'] = $otherUser->lastSeen();
    // *** END OF FIX ***

    return response()->json([
        'success' => true,
        'messages' => $messages,
        'other_user' => $otherUserData, // 3. أرسل المصفوفة الكاملة
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
    ]);

    try {
        $conversation = PrivateConversation::findOrFail($conversationId);
        $user = Auth::user(); // هذا هو المرسل

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

        $messageData = [
            'sender_id' => $user->id,
            'recipient_id' => $otherUserId,
            'private_conversation_id' => $conversation->id,
            'content' => $request->input('content'),
            'message_type' => $request->input('message_type'),
            'media_path' => $mediaPath,
        ];

        Log::debug('Creating message with data:', $messageData);

        $message = Message::create($messageData);

        $conversation->update(['last_message_at' => now()]);

        Log::info('Message created successfully', [
            'message_id' => $message->id,
            'conversation_updated' => $conversation->last_message_at
        ]);
        
        // ✨ --- بداية إضافة منطق الإشعارات --- ✨

        // 1. العثور على المستخدم المستلم
        $recipient = User::find($otherUserId);

        // 2. التحقق من وجود المستلم وأن لديه fcm_token
        if ($recipient && $recipient->fcm_token) {
            try {
                // 3. إرسال الإشعار
                $recipient->notify(new NewPrivateMessageNotificationLatest($message, $user));
                Log::info('Push notification sent successfully.', ['recipient_id' => $recipient->id]);
            } catch (\Exception $e) {
                // تسجيل أي خطأ يحدث أثناء إرسال الإشعار دون إيقاف العملية
                Log::error('Failed to send push notification.', [
                    'recipient_id' => $recipient->id,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            Log::warning('Recipient not found or does not have FCM token.', ['recipient_id' => $otherUserId]);
        }
        
        // --- نهاية إضافة منطق الإشعارات --- ✨


        $message->load('sender');

        // broadcast(new NewPrivateMessage($message))->toOthers();

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
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
    
        // Get the count of unread messages before updating
        $unreadCount = $conversation->messages()
            ->where('recipient_id', $user->id)
            ->where('is_read', false)
            ->count();
    
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