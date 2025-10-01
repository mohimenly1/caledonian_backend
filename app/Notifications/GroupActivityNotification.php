<?php

namespace App\Notifications;

use App\Models\ChatGroup;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class GroupActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $group;
    protected $activity;
    protected $actor;

    public function __construct(ChatGroup $group, string $activity, $actor)
    {
        $this->group = $group;
        $this->activity = $activity;
        $this->actor = $actor;
    }

    public function via($notifiable)
    {
        return [FcmChannel::class, 'database'];
    }

    public function toFcm($notifiable): FcmMessage
    {
        $title = 'Group Update: ' . $this->group->name;
        $body = $this->actor->name . ' ' . $this->activity;

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $body,
        )))
        ->data([
            'type' => 'group_activity',
            'group_id' => (string) $this->group->id,
            'activity' => $this->activity,
            'actor_id' => (string) $this->actor->id,
        ])
        ->custom([
            'android' => [
                'notification' => [
                    'channel_id' => 'group_activities',
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
            'type' => 'group_activity',
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'activity' => $this->activity,
            'actor_id' => $this->actor->id,
            'actor_name' => $this->actor->name,
        ];
    }
}