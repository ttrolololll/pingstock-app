<?php

namespace App\Services;

use GuzzleHttp\Client;

class WTDService
{
    protected $baseUrl = 'https://api.worldtradingdata.com/api/v1';
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

        $this->apiToken = config('services.wtd.api_token');
    }

    public function getStockQuote($stockSymbols, $options = ['output' => 'json'])
    {
        $symbolQueryVal = implode(',', $stockSymbols);
        $opt = [];

        if (is_array($options) && count($options) > 0) {
            $opt = $options;
        }

        $opt['symbol'] = $symbolQueryVal;
        $opt['api_token'] = $this->apiToken;

        return $this->http->get($this->baseUrl . '/stock', [
            'query' => $opt
        ]);
    }
}
