<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $otp;

    public string $type;

    public function __construct(
        User $user,
        string $otp,
        string $type
    ) {
        $this->user = $user;
        $this->otp = $otp;
        $this->type = $type;
    }

    public function build()
    {
        return $this->subject('Verification Code')
            ->view('emails.send-otp');
    }
}