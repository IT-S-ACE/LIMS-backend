<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResultReadyMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $messageText;

    public function __construct(
        string $message
    ) {
        $this->messageText = $message;
    }

    public function build()
    {
        return $this
            ->subject(
                'Medical Result Ready'
            )
            ->view(
                'emails.result-ready'
            );
    }
}