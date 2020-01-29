<?php

namespace App\Services;

use GuzzleHttp\Client;

/**
 * Class StockInfoServiceProvider
 * @package App\Services
 */
class StockInfoServiceProvider
{
    protected $baseUrl;
    protected $http;
    protected $apiToken;

    /**
     * StockInfoServiceProvider constructor.
     * @param $baseUrl
     */
    public function __construct($baseUrl) {}
}
