<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewPrivateMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return [FcmChannel::class, 'database'];
    }

    public function toFcm($notifiable): FcmMessage
    {
        return (new FcmMessage(notification: new FcmNotification(
            title: 'New message from ' . $this->message->sender->name,
            body: $this->message->content ?? 'Sent a media message',
        )))
        ->data([
            'type' => 'private_message',
            'message_id' => (string) $this->message->id,
            'sender_id' => (string) $this->message->sender_id,
        ])
        ->custom([
            'android' => [
                'notification' => [
                    'channel_id' => 'private_messages',
                    'sound' => 'default',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                    ],
                ],
            ],
        ]);
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'private_message',
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'sender_name' => $this->message->sender->name,
            'content' => $this->message->content,
            'message_type' => $this->message->message_type,
        ];
    }
}