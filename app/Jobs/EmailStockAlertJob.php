<?php

namespace App\Jobs;

use App\Models\StockAlertRule;
use App\Notifications\StockPriceAlert;
use App\Services\MailgunService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use NotificationChannels\Telegram\TelegramChannel;

class EmailStockAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $rules;
    protected $price;

    public function __construct(Collection $rules, $price = null)
    {
        $this->rules = $rules;
        $this->price = $price;
    }

    public function handle()
    {
        $to = [];
        $recipientVar = [];

        foreach ($this->rules as $row => $alert) {
            $price = $this->price;

            if (isset($alert->latest_price) && !empty($alert->latest_price)) {
                $price = $alert->latest_price;
            }

            $msg = 'Stock ' . $alert->stock_symbol . ' is ' . $alert->operator . ' than set target ' . $alert->target . ' at ' . $price;

            if ($alert->alert_telegram) {
                $notification = new StockPriceAlert($alert, $msg);
                $notification->delay($row);
                Notification::route(TelegramChannel::class, $alert->alert_telegram)
                    ->notify($notification);
            }

            Log::info($msg);

            $to[] = $alert->alert_email;
            $recipientVar[$alert->alert_email] = [
                'stock_symbol' => $alert->stock_symbol,
                'operator' => $alert->operator,
                'target' => $alert->target,
                'current' => $price,
            ];
        }

        $mailgunService = new MailgunService();
        $resp = $mailgunService->batchSendUseTemplate(null, $to, '%recipient.stock_symbol% Alert Triggered', $recipientVar, 'stockalert');

        // then set stock alerts as triggered
        $ruleChunks = $this->rules->chunk(200)->toArray();
        $now = Carbon::now();

        foreach ($ruleChunks as $key => $chunk) {
            $ids = [];

            foreach ($chunk as $rule) {
                $conds[] = $rule['id'];
            }

            if (count($conds) > 0) {
                StockAlertRule::whereIn('id', $conds)->update([
                    'triggered_at' => $now
                ]);
            }
        }
    }
}
