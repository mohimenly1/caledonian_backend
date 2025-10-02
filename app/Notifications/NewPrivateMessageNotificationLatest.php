<?php

namespace App\Notifications;

use App\Models\Message;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class NewPrivateMessageNotificationLatest extends Notification
{
    use Queueable;

    protected $message;
    protected $sender;

    /**
     * Create a new notification instance.
     *
     * @param Message $message
     * @param User $sender
     */
    public function __construct(Message $message, User $sender)
    {
        $this->message = $message;
        $this->sender = $sender;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [FcmChannel::class];
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \NotificationChannels\Fcm\FcmMessage
     */
    public function toFcm($notifiable): FcmMessage
    {
        // تحديد محتوى نص الإشعار بناءً على نوع الرسالة
        $body = $this->message->message_type === 'text' ? $this->message->content : 'أرسل لك ملفًا';

        $fcmNotification = new FcmNotification(
            title: $this->sender->name, // اسم المرسل كعنوان للإشعار
            body: $body
        );
        
        // إضافة صورة المرسل إذا كانت متاحة
        if ($this->sender->photo_url) {
            $fcmNotification->image($this->sender->photo_url);
        }

        return (new FcmMessage(notification: $fcmNotification))
            ->data([
                'type' => 'private_message',
                'conversation_id' => (string) $this->message->private_conversation_id,
                'sender_id' => (string) $this->sender->id,
            ]);
    }
}
