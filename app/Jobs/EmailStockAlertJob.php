<?php

namespace App\Jobs;

use App\Services\MailgunService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        foreach ($this->rules as $alert) {
            $price = $this->price;

            if (isset($alert->latest_price) && !empty($alert->latest_price)) {
                $price = $alert->latest_price;
            }

            Log::info('Stock ' . $alert->stock_symbol . ' is ' . $alert->operator . ' than set target ' . $alert->target . ' at ' . $price);

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

        foreach ($ruleChunks as $key => $chunk) {
            $conds = [];

            foreach ($chunk as $rule) {
                $conds[] = ['id', '=', $rule['id']];
            }

            if (count($conds) > 0) {
                DB::table('stock_alert_rules')->where($conds)->update(['triggered' => 1]);
            }
        }
    }
}
