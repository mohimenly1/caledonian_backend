<?php

namespace App;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportCardMail extends Mailable
{
    use Queueable, SerializesModels;

    public $pdf;
    public $student;
    public $term;

    public function __construct($pdf, $student, $term)
    {
        $this->pdf = $pdf;
        $this->student = $student;
        $this->term = $term;
    }

    public function build()
    {
        return $this->subject("Report Card - {$this->student->name} - {$this->term->name}")
            ->markdown('emails.report-card')
            ->attachData($this->pdf->output(), "report-card-{$this->student->name}.pdf");
    }
}