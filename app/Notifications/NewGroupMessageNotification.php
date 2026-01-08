<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewGroupMessageNotification extends Notification
{
    use Queueable;

    protected $message;
    protected $groupName;
    protected $senderName;

    public function __construct(Message $message)
    {
        \Illuminate\Support\Facades\Log::info('[NewGroupMessageNotification] 🔧 Constructing notification', [
            'message_id' => $message->id,
        ]);
        
        $this->message = $message->loadMissing(['group', 'sender']);
        $this->groupName = $this->message->group->name ?? 'Group';
        $this->senderName = $this->message->sender->name ?? 'User';
        
        \Illuminate\Support\Facades\Log::info('[NewGroupMessageNotification] ✅ Notification constructed', [
            'message_id' => $message->id,
            'group_name' => $this->groupName,
            'sender_name' => $this->senderName,
        ]);
    }

    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    public function toDatabase($notifiable)
    {
        $content = $this->message->content ?? $this->getMediaTypeMessage() ?? 'New message';
        
        return [
            'title' => 'New message in ' . $this->groupName,
            'body' => $this->senderName . ': ' . $content,
            'data' => [
                'type' => 'group_message',
                'group_id' => $this->message->chat_group_id,
                'message_id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
            ],
        ];
    }

    public function toFcm($notifiable): FcmMessage
    {
        \Illuminate\Support\Facades\Log::info('[NewGroupMessageNotification@toFcm] 🔧 Building FCM message', [
            'message_id' => $this->message->id,
            'notifiable_id' => $notifiable->id ?? 'N/A',
            'notifiable_class' => get_class($notifiable),
            'fcm_token' => method_exists($notifiable, 'routeNotificationForFcm') ? ($notifiable->routeNotificationForFcm() ? substr($notifiable->routeNotificationForFcm(), 0, 50) . '...' : 'NULL') : 'N/A',
        ]);
        
        $content = $this->message->content ?? $this->getMediaTypeMessage() ?? 'New message';
        
        $title = 'New message in ' . $this->groupName;
        $body = $this->senderName . ': ' . $content;
        
        \Illuminate\Support\Facades\Log::info('[NewGroupMessageNotification@toFcm] 📝 Notification content', [
            'title' => $title,
            'body' => $body,
            'content' => $content,
        ]);
        
        $fcmMessage = (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body,
        )))
        ->data([
            'type' => 'group_message',
            'group_id' => (string) $this->message->chat_group_id,
            'message_id' => (string) $this->message->id,
            'sender_id' => (string) $this->message->sender_id,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ])
        ->custom([
            'android' => [
                'priority' => 'high', // ✅ إرسال إشعارات عالية الأولوية
                'notification' => [
                    'channel_id' => 'bus_tracking_channel', // ✅ يجب أن يتطابق مع channel في Flutter app
                    'color' => '#1a237e', // ✅ استخدام اللون الرئيسي للتطبيق
                    'sound' => 'default',
                    'priority' => 'high',
                    'notification_priority' => 'PRIORITY_HIGH', // ✅ إرسال إشعارات عالية الأولوية
                    'default_sound' => true,
                    'default_vibrate_timings' => true,
                ],
                'fcm_options' => [
                    'analytics_label' => 'group_message',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default', // Optional: Set iOS notification sound
                    ],
                ],
                'fcm_options' => [
                    'analytics_label' => 'analytics', // Optional: Set iOS analytics label
                ],
            ],
            // 'webpush' => [
            //     'headers' => [
            //         'Urgency' => 'high',
            //     ],
            // ],
        ]);
        
        \Illuminate\Support\Facades\Log::info('[NewGroupMessageNotification@toFcm] ✅ FCM message built successfully', [
            'message_id' => $this->message->id,
            'has_notification' => true,
            'has_data' => true,
            'has_android_config' => true,
        ]);
        
        return $fcmMessage;
    }

    protected function getMediaTypeMessage(): ?string
    {
        return match($this->message->message_type) {
            'image' => 'Sent an image',
            'video' => 'Sent a video',
            'audio' => 'Sent a voice message',
            'document' => 'Sent a document',
            default => null,
        };
    }
}