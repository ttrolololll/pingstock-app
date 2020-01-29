<?php

namespace App\Services;

use GuzzleHttp\Client;

class WTDService extends StockInfoServiceProvider
{
    public static $sourceName = 'wtd';

    protected $baseUrl = 'https://api.worldtradingdata.com/api/v1';
    protected $http;
    protected $apiToken;

    public function __construct($baseUrl = '')
    {
        parent::__construct($baseUrl);

        if ($baseUrl != '') {
            $this->baseUrl = $baseUrl;
        }

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10
        ]);

        $this->apiToken = config('services.wtd.api_token');
    }

    /**
     * Get stock quotes
     *
     * @param array $stockSymbols
     * @param bool $isAsync
     * @param array $options
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function getStockQuote(Array $stockSymbols, $isAsync = false, $options = ['output' => 'json'])
    {
        $symbolQueryVal = implode(',', $stockSymbols);
        $opt = [];

        if (is_array($options) && count($options) > 0) {
            $opt = $options;
        }

        $opt['symbol'] = $symbolQueryVal;
        $opt['api_token'] = $this->apiToken;

        if ($isAsync) {
            return $this->http->getAsync($this->baseUrl . '/stock', [
                'query' => $opt
            ]);
        }

        return $this->http->get($this->baseUrl . '/stock', [
            'query' => $opt
        ]);
    }
}
