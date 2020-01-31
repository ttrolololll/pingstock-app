<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
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
        foreach ($this->rules as $alert) {
            $price = $this->price;

            if (isset($alert->latest_price) && !empty($alert->latest_price)) {
                $price = $alert->latest_price;
            }
//            Log::debug($alert->stock_symbol);
            Log::info('Stock ' . $alert->stock_symbol . ' is ' . $alert->operator . ' than set target ' . $alert->target . ' at ' . $price);
        }

        // then delete stock alerts
        $ruleChunks = $this->rules->chunk(200)->toArray();
        $deleteGroups = [];

        foreach ($ruleChunks as $key => $chunk) {
            $delConds = [];

            foreach ($chunk as $rule) {
                $delConds[] = ['id', '=', $rule['id']];
            }

            if (count($delConds) > 0) {
//                DB::table('stock_alert_rules')->where($delConds)->delete();
            }
        }
    }
}
