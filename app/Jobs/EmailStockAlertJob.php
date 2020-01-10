<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class EmailStockAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $alertSet;

    public function __construct($set)
    {
        $this->alertSet = $set;
    }

    public function handle()
    {
        Log::info('Stock ' . $this->alertSet['rule']->stock_symbol . ' is ' . $this->alertSet['rule']->operator . ' than set target ' . $this->alertSet['rule']->target . ' at ' . $this->alertSet['price']);
    }
}
