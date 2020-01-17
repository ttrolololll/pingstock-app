<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $token;
    protected $user;

    public function __construct($token, CanResetPassword $user)
    {
        $this->token = $token;
        $this->user = $user;
    }

    public function build()
    {
        return $this->to($this->user->email)
            ->subject('PingStock.io Password Reset')
            ->view('emails.passwordreset')
            ->with([
                'reset_link' => $this->user->resetPasswordLink($this->token),
                'name' => $this->user->first_name,
            ]);
    }
}
