<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StockAlertEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $to = ['jonathan.yxy@outlook.com', 'scordaive@gmail.com'];

        return $this->to($to)->withSwiftMessage(function (\Swift_Message $message) {
            $message->getHeaders()->addTextHeader('X-Mailgun-Recipient-Variables', '{"jonathan.yxy@outlook.com": {"first":"Bob", "id":1},
            "scordaive@gmail.com": {"first":"Alice", "id": 2}}');
        })
            ->subject('Stock Alert PingStock.io')
            ->view('emails.stockalert');
    }
}
