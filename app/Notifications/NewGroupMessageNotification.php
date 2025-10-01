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
        $this->message = $message->loadMissing(['group', 'sender']);
        $this->groupName = $this->message->group->name ?? 'Group';
        $this->senderName = $this->message->sender->name ?? 'User';
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
        $content = $this->message->content ?? $this->getMediaTypeMessage() ?? 'New message';
        
        return (new FcmMessage(notification: new FcmNotification(
            title: 'New message in ' . $this->groupName,
            body: $this->senderName . ': ' . $content,
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
                'notification' => [
                    'color' => '#0A0A0A', // Optional: Set Android notification color
                    'sound' => 'default', // Optional: Set Android notification sound
                ],
                'fcm_options' => [
                    'analytics_label' => 'analytics', // Optional: Set Android analytics label
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