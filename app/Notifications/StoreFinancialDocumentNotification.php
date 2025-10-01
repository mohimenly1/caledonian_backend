<?php

namespace App\Notifications;

use App\Models\FinancialDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StoreFinancialDocumentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $financialDocument;

    public function __construct(FinancialDocument $financialDocument)
    {
        $this->financialDocument = $financialDocument;
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
            'financial_document_id' => $this->financialDocument->id,
            'total_amount' => $this->financialDocument->total_amount,
            'final_amount' => $this->financialDocument->final_amount,
            'message' => 'A financial document has been issued.',
        ];
    }

    // Notification structure for broadcasting
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'financial_document_id' => $this->financialDocument->id,
            'total_amount' => $this->financialDocument->total_amount,
            'final_amount' => $this->financialDocument->final_amount,
            'message' => 'A financial document has been issued.',
        ]);
    }
}