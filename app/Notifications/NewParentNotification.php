<?php

namespace App\Notifications;

use App\Models\ParentInfo;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewParentNotification extends Notification
{
    use Queueable;
    protected $parent;
    /**
     * Create a new notification instance.
     */
    public function __construct(ParentInfo $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast']; // Database and Broadcast for real-time notifications
    }

    // Notification structure for database storage
    public function toDatabase($notifiable)
    {
        return [
            'parent_id' => $this->parent->id,
            'first_name' => $this->parent->first_name,
            'last_name' => $this->parent->last_name,
            'message' => 'A new parent has been added.',
        ];
    }

    // Notification structure for broadcasting
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'parent_id' => $this->parent->id,
         'first_name' => $this->parent->first_name,
         'last_name' => $this->parent->last_name,
            'message' => 'A new parent has been added.',
        ]);
    }

    // Optional: structure for email notification (if needed)
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('A new parent has been added.')
                    ->action('View Parent', url('/parents/'.$this->parent->id))
                    ->line('Thank you for using our application!');
    }
}
