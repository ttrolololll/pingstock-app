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
        $triggered = [];

        foreach ($this->watchlist_items as $row => $item) {
            $triggeredCbs = [];
            $cbs = json_decode($item->circuit_breakers, true);
            $cbs = collect($cbs)
                ->filter(function ($value, $key) {
                    // filter to get circuit breakers that are active, non-muted, and not triggered within the day
                    return $value['is_active'] &&
                        ( !$value['mute_till'] ||
                            ( $value['mute_till'] && Carbon::parse($value['mute_till'])->lte(now()->utc()) )
                        ) &&
                        ( !$value['last_triggered'] ||
                            ( $value['last_triggered'] && ! Carbon::parse($value['last_triggered'])->isCurrentDay() )
                        );
                })
                ->sortByDesc('threshold') // largest threshold first
                ->toArray();

            // loop to find the triggered circuit breakers
            foreach ($cbs as $key => $cb) {
                $lesserTriggerVal = $item->reference_target - ( $item->reference_target * ( $cb['threshold'] / 100 ) );
                $greaterTriggerVal = $item->reference_target + ( $item->reference_target * ( $cb['threshold'] / 100 ) );

                if ($this->price <= $lesserTriggerVal) {
                    if (empty($triggered)) {
                        $triggered = [
                            'key' => $key,
                            'symbol' => $item->stock_symbol,
                            'type' => 'lower',
                            'threshold' => $cb['threshold'],
                            'reference_price' => $item->reference_target,
                            'trigger_val' => $lesserTriggerVal,
                            'price' => $this->price,
                        ];
                    }
                    $triggeredCbs[] = $key;
                    continue;
                }
                if ($this->price >= $greaterTriggerVal) {
                    if (empty($triggered)) {
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
                    $triggeredCbs[] = $key;
                }
            }

            // if none triggered, continue to next watchlist item
            if (empty($triggered)) {
                continue;
            }

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

            // update watchlist item
            $updates = [];
            $now = now();
            foreach ($triggeredCbs as $cb) {
                $updates["circuit_breakers->{$cb}->last_triggered"] = $now;
            }
            try {
                WatchlistItem::where('id', $item->id)->update($updates);
            } catch (\Exception $e) {
                Log::error('Unable to update watchlist item circuit breaker: ' .$e->getMessage(), $e->getTrace());
            }
        }

        if (!empty($triggered)) {
            $mailgunService = new MailgunService();
            $resp = $mailgunService->batchSendUseTemplate(null, $to, '%recipient.symbol% Circuit Breaker Triggered', $recipientVar, 'circuitbreakeralert');
        }
    }
}
