<?php

namespace App\Console\Commands;

use App\Jobs\GetStockUpdateJob;
use App\Models\StockAlertRule;
use App\Services\AlphaVantageService;
use App\Services\WTDService;
use GuzzleHttp\Promise;
use Illuminate\Console\Command;
use Illuminate\Queue\Jobs\Job;

class StockAlertingCmd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:alert';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initiate stock alert rule examinations and alert users if condition meets';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // get all stock alert rules
        $alertRules = StockAlertRule::cursor();

        // get unique symbols
        $symbolSets = $alertRules->unique('stock_symbol')
            ->pluck('stock_symbol')
            ->chunk(config('services.alphavantage.api_rate_per_minute'))
            ->toArray();

        // send chunks of symbols into queue
        foreach ($symbolSets as $set) {
            GetStockUpdateJob::dispatch($set)->onQueue('get_stock_update');
        }


//        $stockService = new AlphaVantageService();
//        $promises = [];
//        $stockData = [];
//
//        foreach ($symbolSets as $set) {
//            foreach ($set as $symbol) {
//                $promises[] = $stockService->globalQuote($symbol, true);
//            }
//        }
//
//        $responses = Promise\unwrap($promises);
//
//        foreach ($responses as $response) {
//            $this->info($response->getBody());
//        }
    }
}
