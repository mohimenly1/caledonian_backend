<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class WalletChargedNotification extends Notification
{
    use Queueable;

    protected $amount;
    protected $parentName;

    public function __construct($amount, $parentName)
    {
        $this->amount = $amount;
        $this->parentName = $parentName;
    }

    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {
        $body = "السيد / {$this->parentName}، تم شحن محفظتك بقيمة {$this->amount} دينار.";

        $notification = new FcmNotification(
            title: 'تم شحن المحفظة',
            body: $body
        );

        return (new FcmMessage(notification: $notification))
            ->data([
                'type' => 'wallet_charge', // نوع مخصص للتعامل معه في Flutter
            ]);
    }
}