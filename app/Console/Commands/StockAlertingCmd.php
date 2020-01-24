<?php

namespace App\Console\Commands;

use App\Jobs\GetStockUpdateJob;
use App\Models\StockAlertRule;
use App\Models\StockExchange;
use Carbon\Carbon;
use Illuminate\Console\Command;
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

    protected static $cmdNameTag = '[Stock Alerting]';

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
        Log::info(self::$cmdNameTag . ' Initiated at UTC ' . Carbon::now()->utc()->format('Y-m-d H:i:s'));

        // get all exchange symbols within trading hours
        $now = Carbon::now()->utc();
        $nowTime = $now->format('H:i');
        $exchanges = StockExchange::select(['symbol'])
            ->where('trading_start_utc', '<=', $nowTime)
            ->where('trading_end_utc', '>=', $nowTime)
            ->get();
        $exchangeCount = $exchanges->count();

        if ($exchangeCount == 0) {
            Log::info(self::$cmdNameTag . 'No exchange within trading hours, abort operation');
            return;
        }

        $exchangeSymbols = [];

        foreach ($exchanges as $exchange) {
            $exchangeSymbols[] = $exchange->symbol;
        }

        Log::info(self::$cmdNameTag . ' Total exchanges within trading hours: ' . $exchangeCount, $exchangeSymbols);

        // get all stock alert rules
        $alertRules = StockAlertRule::cursor()->filter(function ($value, $key) use ($exchangeSymbols) {
            return in_array($value->exchange_symbol, $exchangeSymbols);
        });

        // get unique symbols
        $symbolSets = $alertRules->unique('stock_symbol')
            ->pluck('stock_symbol')
            ->chunk(config('services.alphavantage.api_rate_per_minute'));

        // send chunks of symbols into queue
        foreach ($symbolSets as $set) {
            GetStockUpdateJob::dispatch($set)->onQueue('get_stock_update');
        }

        Log::info(self::$cmdNameTag . ' Stock info. update jobs dispatched');
        Log::info(self::$cmdNameTag . ' End');

        return;
    }
}
