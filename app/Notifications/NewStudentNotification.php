<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Student;

class NewStudentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $student;

    public function __construct(Student $student)
    {
        $this->student = $student;
    }

    // Notification will be sent via these channels
    public function via($notifiable)
    {
        return ['database', 'broadcast']; // Database and Broadcast for real-time notifications
    }

    // Notification structure for database storage
    public function toDatabase($notifiable)
    {
        return [
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'message' => 'A new student has been added.',
        ];
    }

    // Notification structure for broadcasting
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'student_id' => $this->student->id,
            'student_name' => $this->student->name,
            'message' => 'A new student has been added.',
        ]);
    }

    // Optional: structure for email notification (if needed)
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('A new student has been added.')
                    ->action('View Student', url('/students/'.$this->student->id))
                    ->line('Thank you for using our application!');
    }
}
