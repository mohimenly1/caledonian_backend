<?php

namespace App\Http\Controllers;

use App\Models\CaledonianNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()
            ->caledonianNotifications()
            ->orderBy('created_at', 'desc')
            ->get();
            
        return response()->json([
            'data' => $notifications
        ]);
    }

    public function markAsRead(CaledonianNotification $notification)
    {
        if ($notification->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }
        
        $notification->update(['read' => true]);
        
        return response()->json(['message' => 'Notification marked as read']);
    }

    public function markAllAsRead()
    {
        auth()->user()
            ->caledonianNotifications()
            ->where('read', false)
            ->update(['read' => true]);
            
        return response()->json(['message' => 'All notifications marked as read']);
    }
}