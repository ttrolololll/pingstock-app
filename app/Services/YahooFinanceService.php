<?php

namespace App\Services;

use GuzzleHttp\Client;

class YahooFinanceService
{
    protected $baseUrl = 'https://query1.finance.yahoo.com';
    protected $http;

    public function __construct($baseUrl = '')
    {
        if ($baseUrl != '') {
            $this->baseUrl = $baseUrl;
        }

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10
        ]);
    }

    public function quoteType($symbol, $isAsync = false, $options = [])
    {
        $opt = [];

        if (is_array($options) && count($options) > 0) {
            $opt = $options;
        }

        if ($isAsync) {
            return $this->http->getAsync($this->baseUrl . '/v1/finance/quoteType/' . $symbol, [
                'query' => $opt
            ]);
        }

        return $this->http->get($this->baseUrl . '/v1/finance/quoteType/' . $symbol, [
            'query' => $opt
        ]);
    }
}
