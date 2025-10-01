<?php

namespace App\Notifications;

use App\Models\ParentArrival; // ✨
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class ParentArrivalNotification extends Notification
{
    use Queueable;

    protected $arrival;

    public function __construct(ParentArrival $arrival) // ✨
    {
        $this->arrival = $arrival;
    }

    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {
        // بناء الإشعار الذي سيظهر في الهاتف
        $notification = new FcmNotification(
            title: 'وصول ولي أمر', // عنوان الإشعار
            body: $this->arrival->message // نص الإشعار
        );

        // إرسال البيانات الإضافية مع الإشعار
        return (new FcmMessage(notification: $notification))
            ->data([
                'type' => 'parent_arrival',
                'arrival_id' => (string) $this->arrival->id,
            ]);
    }
}