<?php

namespace App\Services;

use GuzzleHttp\Client;

class YahooFinanceService extends StockInfoServiceProvider
{
    public static $sourceName = 'yahoo';

    protected $baseUrl = 'https://query1.finance.yahoo.com';

    public function __construct($baseUrl = '')
    {
        parent::__construct($baseUrl);

        if ($baseUrl != '') {
            $this->baseUrl = $baseUrl;
        }
    }

    /**
     * Returns general information about a stock symbol
     *
     * @param $symbol
     * @param bool $isAsync
     * @param array $options
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
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

    /**
     * Returns additional metadata and chart values for a given symbol
     *
     * @param $symbol
     * @param bool $isAsync
     * @param array $options
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function chart($symbol, $isAsync = false, $options = [])
    {
        $opt = [];

        if (is_array($options) && count($options) > 0) {
            $opt = $options;
        }

        $opt['region'] = 'SG';
        $opt['lang'] = 'en-SG';
        $opt['includePrePost'] = false;
        $opt['interval'] = '2m';
        $opt['range'] = '1d';

        if ($isAsync) {
            return $this->http->getAsync($this->baseUrl . '/v8/finance/chart/' . $symbol, [
                'query' => $opt
            ]);
        }

        return $this->http->get($this->baseUrl . '/v8/finance/chart/' . $symbol, [
            'query' => $opt
        ]);
    }
}
