<?php

namespace App\Console\Commands;

use App\Jobs\GetStockUpdateJob;
use App\Models\StockAlertRule;
use App\Services\AlphaVantageService;
use App\Services\WTDService;
use App\Services\YahooFinanceService;
use GuzzleHttp\Promise;
use Illuminate\Console\Command;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Facades\Log;

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
        $yahooService = new YahooFinanceService();
        $data = $yahooService->quoteType('HMN.SI');
        Log::debug('asd', json_decode($data->getBody(), true));
        return $data;
        // get all stock alert rules
        $alertRules = StockAlertRule::cursor();

        // get unique symbols
        $symbolSets = $alertRules->unique('stock_symbol')
            ->pluck('stock_symbol')
            ->chunk(config('services.alphavantage.api_rate_per_minute'))
            ->toArray();

        // filter out symbols outside of trading hours

        // send chunks of symbols into queue
        foreach ($symbolSets as $set) {
            GetStockUpdateJob::dispatch($set)->onQueue('get_stock_update');
        }
    }
}
