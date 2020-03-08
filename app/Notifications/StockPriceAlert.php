<?php

namespace App\Notifications;

use App\Models\StockAlertRule;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class StockPriceAlert extends Notification implements ShouldQueue
{
    use Queueable;

    protected $stockAlertRule;
    protected $simpleMsg;

    /**
     * Create a new notification instance.
     * @param StockAlertRule|null $stockAlertRule
     * @param string $simpleMsg
     */
    public function __construct(StockAlertRule $stockAlertRule = null, $simpleMsg = '')
    {
        $this->stockAlertRule = $stockAlertRule;
        $this->simpleMsg = $simpleMsg;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [TelegramChannel::class];
    }

    /**
     * Get the Telegram representation of the notification.
     *
     * @param  Notifiable $notifiable
     * @return TelegramMessage
     */
    public function toTelegram($notifiable)
    {
        $msg = "Howdy! You can now link your Telegram with PingStock.io account by using */link your-email your-otp* command here.\n\nYou can generate your OTP in your PingStock.io account notification settings.\n\nEg. /link pingstock.io@gmail.com 12345";
        if ($this->simpleMsg) {
            $msg = $this->simpleMsg;
        }
        if ($notifiable instanceof AnonymousNotifiable) {
            return TelegramMessage::create()
                ->to($notifiable->routeNotificationFor(TelegramChannel::class))
                ->content($msg);
        }
        return TelegramMessage::create()
            ->to($notifiable->telegram_id)
            ->content($msg);
    }
}
