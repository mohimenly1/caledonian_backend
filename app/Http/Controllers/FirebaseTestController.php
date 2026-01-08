<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class FirebaseTestController extends Controller
{
    public function testFirebase(Request $request)
    {
        try {
            $credentialsPath = config('firebase.credentials');

            Log::info('[FirebaseTest] Testing Firebase configuration', [
                'credentials_path' => $credentialsPath,
                'file_exists' => file_exists($credentialsPath),
                'project_id' => config('firebase.project_id'),
            ]);

            if (!file_exists($credentialsPath)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Firebase credentials file not found at: ' . $credentialsPath
                ], 404);
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $messaging = $factory->createMessaging();

            // Test with a specific FCM token if provided
            $fcmToken = $request->input('fcm_token');

            if (!$fcmToken) {
            // Try to get a user's FCM token for testing
            $user = User::where('fcm_token', '<>', '')->first();
                if ($user) {
                    $fcmToken = $user->fcm_token;
                    Log::info('[FirebaseTest] Using FCM token from user', [
                        'user_id' => $user->id,
                        'fcm_token_preview' => substr($fcmToken, 0, 50) . '...',
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'error' => 'No FCM token provided and no user with FCM token found'
                    ], 400);
                }
            }

            // ✅ بناء رسالة FCM مع notification و data payload
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(Notification::create(
                    'Firebase Test',
                    'This is a test notification from Firebase'
                ))
                ->withData([
                    'type' => 'test',
                    'timestamp' => now()->toIso8601String(),
                ])
                ->withAndroidConfig(\Kreait\Firebase\Messaging\AndroidConfig::fromArray([
                    'priority' => 'high',
                    'notification' => [
                        'sound' => 'default',
                        'channel_id' => 'bus_tracking_channel',
                    ],
                ]))
                ->withApnsConfig(\Kreait\Firebase\Messaging\ApnsConfig::fromArray([
                    'payload' => [
                        'aps' => [
                            'sound' => 'default',
                            'alert' => [
                                'title' => 'Firebase Test',
                                'body' => 'This is a test notification from Firebase',
                            ],
                            'badge' => 1,
                        ],
                    ],
                ]));

            Log::info('[FirebaseTest] Sending notification', [
                'fcm_token_preview' => substr($fcmToken, 0, 50) . '...',
                'project_id' => config('firebase.project_id'),
            ]);

            $result = $messaging->send($message);

            Log::info('[FirebaseTest] Notification sent successfully', [
                'message_id' => $result,
                'fcm_token_preview' => substr($fcmToken, 0, 50) . '...',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Firebase notification sent successfully',
                'fcm_token_preview' => substr($fcmToken, 0, 50) . '...',
            ]);

        } catch (\Exception $e) {
            Log::error('[FirebaseTest] Failed to send Firebase notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }
}

