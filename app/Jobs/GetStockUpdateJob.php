<?php

namespace App\Jobs;

use App\Models\StockAlertRule;
use App\Services\AlphaVantageService;
use App\Services\WTDService;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use GuzzleHttp\Promise;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GetStockUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $logTag = 'GetStockUpdateJob';
    protected $stockSymbols;
    protected $serviceProvider;

    public function __construct(LazyCollection $stockSymbols, $serviceProvider)
    {
        $this->stockSymbols = $stockSymbols;
        $this->serviceProvider = $serviceProvider;
    }

    public function handle()
    {
        switch ($this->serviceProvider) {
            case WTDService::$sourceName:
                $this->handleWTDSource($this->stockSymbols);
                break;
            default:
                $this->handleDefaultSource($this->stockSymbols);
        }
    }

    protected function handleWTDSource(LazyCollection $stockSymbols)
    {
        $wtdService = new WTDService();
        $symbols = [];

        foreach ($stockSymbols as $symbol) {
            $symbols[] = $symbol;
        }

        $wtdResp = $wtdService->getStockQuote($symbols);
        $statusCode = $wtdResp->getStatusCode();

        try {
            $body = json_decode((string)  $wtdResp->getBody(), true);
        } catch (\Exception $e) {
            $body['message'] = (string) $wtdResp->getBody();
        }

        if ($statusCode != 200) {
            Log::warning("[$this->logTag.handle] HTTP call to WorldTradingData received non-200 status code " . $statusCode, $body);
            return;
        }

        if (!isset($body['data'])) {
            Log::warning("[$this->logTag.handle] HTTP call to WorldTradingData received empty data", $body);
            return;
        }

        $dataCollection = collect($body['data']);
        $symbolsCollection = $dataCollection->pluck('symbol');
        $symbols = $dataCollection->keyBy('symbol')->toArray();
        $triggered = new Collection();

        $rules = StockAlertRule::cursor()
            ->filter(function ($alertRule) use ($symbolsCollection) {
                return ! $alertRule->triggered && in_array($alertRule->stock_symbol, $symbolsCollection->toArray());
            })
            ->each(function ($alertRule) use ($symbols, $triggered) {
                if ( ($alertRule->operator == 'greater' && $symbols[$alertRule->stock_symbol]['price'] > $alertRule->target ||
                    $alertRule->operator == 'lesser' && $symbols[$alertRule->stock_symbol]['price'] < $alertRule->target) ) {
                    $alertRule->latest_price = $symbols[$alertRule->stock_symbol]['price'];
                    $triggered->add($alertRule);
                }
            });

        $triggeredChunks = $triggered->chunk(1000);

        // push each chunk as notification job
        foreach ($triggeredChunks as $chunk) {
            EmailStockAlertJob::dispatch($chunk)->onQueue('stock_alerts');
        }
    }

    protected function handleDefaultSource(LazyCollection $stockSymbols)
    {
        $stockService = new AlphaVantageService();
        $promises = [];

        // make async HTTP calls to stock data provider
        foreach ($stockSymbols as $symbol) {
            $promises[] = $stockService->globalQuote($symbol, true);
        }

        // gather all responses
        $asyncResponses = Promise\settle($promises)->wait(true);

        for ($i = 0; $i < count($asyncResponses); $i++) {
            // skip if promise cannot be fulfilled
            if ($asyncResponses[$i]['state'] != 'fulfilled') {
                Log::warning("[$this->logTag.handle] HTTP call to AlphaVantage failed for symbol " . $this->stockSymbols[$i]);
                continue;
            }

            $response = $asyncResponses[$i]['value'];

            // skip if response status is not 200
            if ($response->getStatusCode() != 200) {
                Log::warning("[$this->logTag.handle] response code " . $response->getStatusCode() . " from AlphaVantage for symbol " . $this->stockSymbols[$i]);
                continue;
            }

            $data = $this->resolveResponse($response);

            if (! $data) {
                Log::warning("[$this->logTag.handle] unable to resolve AlphaVantage response for " . $this->stockSymbols[$i]);
                continue;
            }

            // get all activated rule into chunks
            $ruleChunks = StockAlertRule::cursor()
                ->filter(function ($alertRule) use ($data) {
                    return ! $alertRule->triggered &&
                        $alertRule->stock_symbol == $data['symbol'] &&
                        ($alertRule->operator == 'greater' && $data['price'] > $alertRule->target ||
                            $alertRule->operator == 'lesser' && $data['price'] < $alertRule->target);
                })
                ->chunk(1000);

            // push each chunk as notification job
            foreach ($ruleChunks as $chunk) {
                EmailStockAlertJob::dispatch($chunk->collect(), $data['price'])->onQueue('stock_alerts');
            }
        }
    }

    protected function resolveResponse($response)
    {
        $symbolData = [];

        try {

            // handle api rate limit violation

            $jsonBody = json_decode($response->getBody(), true);
            $symbolData['symbol'] = $jsonBody['Global Quote']['01. symbol'];
            $symbolData['open'] = $jsonBody['Global Quote']['02. open'];
            $symbolData['high'] = $jsonBody['Global Quote']['03. high'];
            $symbolData['low'] = $jsonBody['Global Quote']['04. low'];
            $symbolData['price'] = $jsonBody['Global Quote']['05. price'];
            $symbolData['volume'] = $jsonBody['Global Quote']['06. volume'];
            $symbolData['latest_trading_day'] = $jsonBody['Global Quote']['07. latest trading day'];
            $symbolData['previous_close'] = $jsonBody['Global Quote']['08. previous close'];
            $symbolData['change'] = $jsonBody['Global Quote']['09. change'];
            $symbolData['change_percent'] = $jsonBody['Global Quote']['10. change percent'];

            return $symbolData;

        } catch (\Exception $e) {
            Log::warning("[$this->logTag.resolveResponse] " . $e->getMessage());
            return false;
        }
    }
}
