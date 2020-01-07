<?php

namespace App\Services;

use GuzzleHttp\Client;

class AlphaVantageService
{
    protected $baseUrl = 'https://www.alphavantage.co/query';
    protected $http;
    protected $apiToken;

    public function __construct($baseUrl = '')
    {
        if ($baseUrl != '') {
            $this->baseUrl = $baseUrl;
        }

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10
        ]);

        $this->apiToken = config('services.alphavantage.api_token');
    }

    /**
     * globalQuote
     *
     * CSV format: symbol,open,high,low,price,volume,latestDay,previousClose,change,changePercent
     *
     * @param $stockSymbol
     * @param bool $isAsync
     * @param array $options
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function globalQuote($stockSymbol, $isAsync = false, $options = ['datatype' => 'csv'])
    {
        $opt = [];

        if (is_array($options) && count($options) > 0) {
            $opt = $options;
        }

        $opt['function'] = 'GLOBAL_QUOTE';
        $opt['symbol'] = $stockSymbol;
        $opt['apikey'] = $this->apiToken;

        if ($isAsync) {
            return $this->http->getAsync($this->baseUrl, [
                'query' => $opt
            ]);
        }

        return $this->http->get($this->baseUrl, [
            'query' => $opt
        ]);
    }
}
