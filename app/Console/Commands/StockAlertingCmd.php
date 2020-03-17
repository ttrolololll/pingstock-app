<?php

namespace App\Console\Commands;

use App\Jobs\GetStockUpdateJob;
use App\Models\StockAlertRule;
use App\Models\StockExchange;
use App\Models\WatchlistItem;
use App\Services\AlphaVantageService;
use App\Services\WTDService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;


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
        $now = Carbon::now()->utc();
        Log::info(self::$cmdNameTag . ' Initiated at UTC ' . $now->format('Y-m-d H:i:s'));

        // get all exchange symbols within trading hours
        $exchanges = StockExchange::select(['symbol', 'timezone']);

        if (config('app.env') == 'staging' || config('app.env') == 'production') {
            $nowTime = $now->format('H:i');
            $exchanges = $exchanges
                ->where('trading_start_utc', '<=', $nowTime)
                ->where('trading_end_utc', '>=', $nowTime);
        }

        $exchanges = $exchanges->get();

        // check if is weekend (there should be no trade and alerts on weekends)
        if (config('app.env') == 'staging' || config('app.env') == 'production') {
            $exchanges = $exchanges->filter(function ($val, $key) use ($now) {
                return ! $now->setTimezone($val->timezone)->isWeekend();
            });
        }

        $exchangeCount = $exchanges->count();

        if ($exchangeCount == 0) {
            Log::info(self::$cmdNameTag . ' No exchange within trading hours, abort operation');
            return;
        }

        $exchangeSymbols = [];

        foreach ($exchanges as $exchange) {
            $exchangeSymbols[] = $exchange->symbol;
        }

        Log::info(self::$cmdNameTag . ' Total exchanges within trading hours: ' . $exchangeCount, $exchangeSymbols);

        // get all stock alert rules distinct by unique symbols
        $alertRules = StockAlertRule::cursor()
            ->filter(function ($value, $key) use ($exchangeSymbols) {
                return ! $value->triggered_at && in_array($value->exchange_symbol, $exchangeSymbols);
            })
            ->unique('stock_symbol');

        // separate symbols by wtd / av source
        list($wtdSymbolSets, $avSymbolSets) = LazyCollection::unwrap($alertRules->partition(function ($item) {
            return $item->source == WTDService::$sourceName;
        }));

        // get all unique symbols from watchlist items
        $watchlistItemSymbols = WatchlistItem::select('stock_symbol')
            ->whereIn('exchange_symbol', $exchangeSymbols)
            ->groupBy('stock_symbol')
            ->get()
            ->pluck('stock_symbol');

        $avSymbolSets = $avSymbolSets
            ->pluck('stock_symbol')
            ->merge($watchlistItemSymbols->all())
            ->unique();

        $wtdSymbolSets = $wtdSymbolSets->pluck('stock_symbol')->chunk(config('services.wtd.symbols_per_request'));
        $avSymbolSets = $avSymbolSets->chunk(config('services.alphavantage.api_rate_per_minute'));

        // send chunks of symbols into queue
        foreach ($wtdSymbolSets as $set) {
            GetStockUpdateJob::dispatch($set, WTDService::$sourceName)->onQueue('get_stock_update');
        }
        // av has api rate limit of
        // 5 calls / min under free plan
        // 30 calls / min under tier 1 paid plan
        $avDelay = now();
        foreach ($avSymbolSets as $set) {
            GetStockUpdateJob::dispatch($set, AlphaVantageService::$sourceName)->onQueue('get_stock_update')->delay($avDelay);
            $avDelay = $avDelay->addSeconds(60);
        }

        Log::info(self::$cmdNameTag . ' Stock info. update jobs dispatched');
        Log::info(self::$cmdNameTag . ' End');

        return;
    }
}
