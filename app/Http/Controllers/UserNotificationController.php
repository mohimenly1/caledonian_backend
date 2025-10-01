<?php
namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CaledonianNotification;
use App\Notifications\PersonalNotification;
use Illuminate\Http\Request;

class UserNotificationController extends Controller
{
    public function sendToUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $user = User::find($validated['user_id']);

        // 1. حفظ الإشعار في قاعدة البيانات
        CaledonianNotification::create([
            'user_id' => $user->id,
            'title'   => $validated['title'],
            'body'    => $validated['body'],
            'data'    => json_encode(['type' => 'personal_notification']),
        ]);

        // 2. إرسال الإشعار اللحظي إذا كان لدى المستخدم token
        if ($user->fcm_token) {
            $user->notify(new PersonalNotification($validated['title'], $validated['body']));
        }

        return response()->json(['message' => "Notification sent successfully to {$user->name}."]);
    }
}