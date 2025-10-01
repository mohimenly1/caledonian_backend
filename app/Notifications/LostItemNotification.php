<?php

namespace App\Notifications;

use App\Models\LostItemTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class LostItemNotification extends Notification
{
    use Queueable;

    protected $ticket;
    protected $message;

    public function __construct(LostItemTicket $ticket, string $message)
    {
        $this->ticket = $ticket;
        $this->message = $message;
    }

    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {
        return (new FcmMessage(notification: new FcmNotification(
            title: 'Lost Item Ticket Update',
            body: $this->message,
        )))
        ->data([
            'type' => 'lost_item_ticket',
            'ticket_id' => (string) $this->ticket->id,
        ]);
    }
}