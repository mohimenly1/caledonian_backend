<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DeleteStudentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $studentId; // Store only the ID
    protected $studentName; // Store the name as well

    public function __construct($student)
    {

  
        if ($student instanceof Student) {
            $this->studentId = $student->id;
            $this->studentName = $student->name;
        } else {
            // If it's an ID, we should still get the student name here
            $student = Student::find($student);
            if ($student) {
                $this->studentId = $student->id;
                $this->studentName = $student->name;
            } else {
                $this->studentId = $student; // just the ID
                $this->studentName = 'Unknown'; // handle unknown case
            }
        }
    }
    

    // Notification will be sent via these channels
    public function via($notifiable)
    {
        Log::info('Notification channels:', ['notifiable_id' => $notifiable->id]);
        return ['database', 'broadcast'];

    }

    // Notification structure for database storage
    public function toDatabase($notifiable)
    {
        return [
            'student_id' => $this->studentId, // Use the stored ID directly
            'student_name' => $this->studentName, // Use the stored name directly
            'message' => 'A student has been deleted.',
        ];
    }

    public function toBroadcast($notifiable)
    {
        Log::info('Broadcasting delete notification for student:', ['student_id' => $this->studentId]);
        return new BroadcastMessage([
            'student_id' => $this->studentId,
            'student_name' => $this->studentName,
            'message' => 'A student has been deleted.',
        ]);
    }
    
    // Optional: structure for email notification (if needed)
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('A student has been deleted.')
                    ->action('View Details', url('/students/'.$this->studentId))
                    ->line('Thank you for using our application!');
    }

}