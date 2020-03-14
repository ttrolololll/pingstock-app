<?php

namespace App\Jobs;

use App\Models\WatchlistItem;
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

class CircuitBreakerTriggeredJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $watchlist_items;
    protected $price;

    public function __construct(Collection $watchlist_items, $price)
    {
        $this->watchlist_items = $watchlist_items;
        $this->price = $price;
    }

    public function handle()
    {
        $to = [];
        $recipientVar = [];
        $triggeredItems = [];
        $triggered = [];

        foreach ($this->watchlist_items as $row => $item) {
            $cbs = json_decode($item->circuit_breakers, true);
            $cbs = collect($cbs)
                ->filter(function ($value, $key) {
                    return $value['is_active'] &&
                        ( !$value['mute_till'] ||
                            ( $value['mute_till'] && Carbon::parse($value['mute_till'])->lte(now()->utc()) )
                        );
                })
                ->sortByDesc('threshold');

            foreach ($cbs as $key => $cb) {
                $lesserTriggerVal = $item->reference_target - ( $item->reference_target * ( $cb['threshold'] / 100 ) );
                $greaterTriggerVal = $item->reference_target + ( $item->reference_target * ( $cb['threshold'] / 100 ) );

                if ($this->price <= $lesserTriggerVal) {
                    $triggered = [
                        'key' => $key,
                        'symbol' => $item->stock_symbol,
                        'type' => 'lower',
                        'threshold' => $cb['threshold'],
                        'reference_price' => $item->reference_target,
                        'trigger_val' => $lesserTriggerVal,
                        'price' => $this->price,
                    ];
                    break;
                }
                if ($this->price >= $greaterTriggerVal) {
                    $triggered = [
                        'key' => $key,
                        'symbol' => $item->stock_symbol,
                        'type' => 'upper',
                        'threshold' => $cb['threshold'],
                        'reference_price' => $item->reference_target,
                        'trigger_val' => $lesserTriggerVal,
                        'price' => $this->price,
                    ];
                }
            }

            if (empty($triggered)) {
                continue;
            }

            $triggeredItems[] = [
                'item_id' => $item->id,
                'cb_key' => $triggered['key'],
            ];

            $msg = 'Circuit Breaker Triggered: Stock ' . $triggered['symbol'] . ' has breached ' . $triggered['type'] . ' bound of reference price at ' . $triggered['reference_price'] . ' by at least ' . $triggered['threshold'] . '% at ' . $triggered['price'];

            if ($item->alert_telegram) {
                $notification = new StockPriceAlert(null, $msg);
                $notification->delay($row);
                Notification::route(TelegramChannel::class, $item->alert_telegram)
                    ->notify($notification);
            }

            $to[] = $item->alert_email;
            $recipientVar[$item->alert_email] = [
                'item_id' => $item->id,
                'key' => $triggered['key'],
                'symbol' => $triggered['symbol'],
                'type' => $triggered['type'],
                'threshold' => $triggered['threshold'],
                'reference_price' => $triggered['reference_price'],
                'price' => $triggered['price'],
            ];
        }

        $mailgunService = new MailgunService();
        $resp = $mailgunService->batchSendUseTemplate(null, $to, '%recipient.symbol% Circuit Breaker Triggered', $recipientVar, 'circuitbreakeralert');

        // save watchlist item
        $now = Carbon::now();

        foreach ($triggeredItems as $key => $item) {
            $cbKey = $item['cb_key'];
            try {
                WatchlistItem::where('id', $item['item_id'])->update([
                    "circuit_breakers->{$cbKey}->last_triggered" => $now
                ]);
            } catch (\Exception $e) {
                Log::error('Unable to update watchlist item circuit breaker: ' .$e->getMessage(), $e->getTrace());
            }
        }
    }
}
