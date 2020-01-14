<?php

namespace App\Jobs;

use App\Models\StockAlertRule;
use App\Services\AlphaVantageService;
use function Aws\filter;
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

    protected $stockSymbols;
    protected $logTag = 'GetStockUpdateJob';

    public function __construct(Array $stockSymbols)
    {
        $this->stockSymbols = $stockSymbols;
    }

    public function handle()
    {
        $stockService = new AlphaVantageService();
        $promises = [];
        $alerts = [];

        // make async HTTP calls to stock data provider
        foreach ($this->stockSymbols as $symbol) {
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

            // get all activated rule into chunks
            $ruleChunks = StockAlertRule::cursor()
                ->filter(function ($alertRule) use ($data) {
                    return $alertRule->stock_symbol == $data['symbol'] &&
                        ($alertRule->operator == 'greater' && $data['price'] > $alertRule->target ||
                            $alertRule->operator == 'lesser' && $data['price'] < $alertRule->target);
                })
                ->chunk(1000);

            // push each chunk as notification job
            foreach ($ruleChunks as $chunk) {
                EmailStockAlertJob::dispatch($chunk, $data['price'])->onQueue('stock_alerts');
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
            return null;
        }
    }
}
