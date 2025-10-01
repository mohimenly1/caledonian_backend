<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    // Follow/unfollow user
    public function follow(User $user)
    {
        $currentUser = Auth::user();
        
        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'You cannot follow yourself'], 400);
        }
        
        if ($currentUser->following()->where('followed_id', $user->id)->exists()) {
            $currentUser->following()->detach($user->id);
            return response()->json(['message' => 'Unfollowed successfully']);
        } else {
            $currentUser->following()->attach($user->id);
            return response()->json(['message' => 'Followed successfully']);
        }
    }

    // Block/unblock user
    public function block(User $user)
    {
        $currentUser = Auth::user();
        
        if ($currentUser->id === $user->id) {
            return response()->json(['message' => 'You cannot block yourself'], 400);
        }
        
        if ($currentUser->blockedUsers()->where('blocked_id', $user->id)->exists()) {
            $currentUser->blockedUsers()->detach($user->id);
            return response()->json(['message' => 'Unblocked successfully']);
        } else {
            $currentUser->blockedUsers()->attach($user->id);
            return response()->json(['message' => 'Blocked successfully']);
        }
    }

    // Get user profile
    public function show(User $user)
    {
        $currentUser = Auth::user();
        
        $isFollowing = $currentUser->following()->where('followed_id', $user->id)->exists();
        $isBlocked = $currentUser->blockedUsers()->where('blocked_id', $user->id)->exists();
        $isBlockedBy = $currentUser->blockedByUsers()->where('blocker_id', $user->id)->exists();
        
        return response()->json([
            'user' => $user->load('badges'),
            'is_following' => $isFollowing,
            'is_blocked' => $isBlocked,
            'is_blocked_by' => $isBlockedBy,
            'can_message' => !$isBlocked && !$isBlockedBy,
        ]);
    }

    // Search users
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        
        $users = User::where('name', 'like', '%'.$request->query.'%')
            ->orWhere('username', 'like', '%'.$request->query.'%')
            ->with('badges')
            ->paginate(10);
            
        return response()->json($users);
    }

    // Get user's followers
    public function followers(User $user)
    {
        $followers = $user->followers()
            ->with('badges')
            ->paginate(10);
            
        return response()->json($followers);
    }

    // Get user's following
    public function following(User $user)
    {
        $following = $user->following()
            ->with('badges')
            ->paginate(10);
            
        return response()->json($following);
    }

    // Update last activity (for online status)
    public function updateActivity()
    {
        $user = Auth::user();
        $user->last_activity = now();
        $user->save();
        
        return response()->json(['message' => 'Activity updated']);
    }

    public function updateFcmToken(Request $request)
{
    $validator = Validator::make($request->all(), [
        'fcm_token' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
    }

    $user = Auth::user();
    $user->fcm_token = $request->fcm_token;
    $user->save();

    Log::info('FCM token updated', [
        'user_id' => $user->id,
        'fcm_token' => $request->fcm_token
    ]);

    return response()->json(['message' => 'FCM token updated successfully']);
}

public function getNotifications()
{
    $user = Auth::user();
    $notifications = $user->notifications()->paginate(20);

    return response()->json($notifications);
}

public function markNotificationAsRead($id)
{
    $user = Auth::user();
    $notification = $user->notifications()->where('id', $id)->first();

    if ($notification) {
        $notification->markAsRead();
        return response()->json(['message' => 'Notification marked as read']);
    }

    return response()->json(['message' => 'Notification not found'], 404);
}

public function markAllNotificationsAsRead()
{
    $user = Auth::user();
    $user->unreadNotifications->markAsRead();

    return response()->json(['message' => 'All notifications marked as read']);
}
}