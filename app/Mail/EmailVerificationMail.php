<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailVerificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->to($this->user->email)
            ->subject('Please verify your email for PingStock.io')
            ->view('emails.verify-registration')
            ->with([
                'name' => $this->user->first_name,
                'verification_link' => $this->user->verificationLink()
            ]);
    }
}
