<?php

namespace App\Notifications;

use App\Models\Announcement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class GeneralAnnouncementNotification extends Notification
{
    use Queueable;

    protected $announcement;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {
        $fcmNotification = new FcmNotification(
            title: $this->announcement->title,
            body: $this->announcement->body,
        );

        if ($this->announcement->image_path) {
            $fcmNotification->image(url('storage/' . $this->announcement->image_path));
        }

        return (new FcmMessage(notification: $fcmNotification))
            ->data([
                'type' => 'general_announcement',
                'announcement_id' => (string) $this->announcement->id,
            ]);
    }
}
