<?php

namespace App\Jobs;

use App\Models\StockAlertRule;
use App\Services\AlphaVantageService;
use GuzzleHttp\Promise;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GetStockUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $stockSymbols;

    public function __construct(Array $stockSymbols)
    {
        $this->stockSymbols = $stockSymbols;
    }

    public function handle()
    {
        $stockService = new AlphaVantageService();
        $promises = [];
        $alerts = [];

        foreach ($this->stockSymbols as $symbol) {
            $promises[] = $stockService->globalQuote($symbol, true);
        }

        $responses = Promise\unwrap($promises);

        foreach ($responses as $response) {
            $data = $this->resolveResponse($response);
            $rules = StockAlertRule::where('stock_symbol', '=', $data['symbol'])->get();

            foreach ($rules as $rule) {
                if ($rule->operator == 'greater' && $data['price'] > $rule->target) {
                    $alert['rule'] = $rule;
                    $alert['price'] = $data['price'];
                    $alerts[] = $alert;
                }
                if ($rule->operator == 'lesser' && $data['price'] < $rule->target) {
                    $alert['rule'] = $rule;
                    $alert['price'] = $data['price'];
                    $alerts[] = $alert;
                }
            }
        }

        $alertSets = collect($alerts)->chunk(1000);

        foreach ($alertSets as $set) {
            EmailStockAlertJob::dispatch($set)->onQueue('stock_alerts');
            // then delete stock alert
        }
    }

    protected function resolveResponse($response)
    {
        $symbolData = [];
        $jsonBody = json_decode($response->getBody(), true);

        try {

            $symbolData['symbol'] = $jsonBody['Global Quote']['01. symbol'];
            $symbolData['open'] = $jsonBody['Global Quote']['02. open'];
            $symbolData['high'] = $jsonBody['Global Quote']['03. high'];
            $symbolData['low'] = $jsonBody['Global Quote']['04. low'];
            $symbolData['price'] = $jsonBody['Global Quote']['05. price'];
            $symbolData['volume'] = $jsonBody['Global Quote']['06. volume'];
            $symbolData['latest_trading_day'] = $jsonBody['Global Quote']['07. latest trading day'];
            $symbolData['previous_close'] = $jsonBody['Global Quote']['08. previous close'];
            $symbolData['change'] = $jsonBody['Global Quote']['09. change"'];
            $symbolData['change_percent'] = $jsonBody['Global Quote']['10. change percent'];

            return $symbolData;

        } catch (\Exception $e) {
            return null;
        }
    }
}
