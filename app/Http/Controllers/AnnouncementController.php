<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\GeneralAnnouncementNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class AnnouncementController extends Controller
{

    public function index()
{
    $announcements = Announcement::with('sender:id,name')
                                ->orderBy('created_at', 'desc')
                                ->get();
    return response()->json($announcements);
}
    public function send(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('announcements', 'public');
        }

        $announcement = Announcement::create([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'image_path' => $imagePath,
            'sent_by_user_id' => Auth::id(),
        ]);

        // Fetch all users who have an FCM token
        $users = User::whereNotNull('fcm_token')->get();

        // Send notification to all users
        Notification::send($users, new GeneralAnnouncementNotification($announcement));

        return response()->json([
            'message' => 'Announcement sent successfully to ' . $users->count() . ' users.',
        ]);
    }
}
