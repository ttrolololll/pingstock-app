<?php

namespace App\Services;

use GuzzleHttp\Client;

class MailgunService
{
    protected $baseUrl;
    protected $domain;
    protected $secret;
    protected $http;

    public function __construct()
    {
        $this->baseUrl = config('services.mailgun.endpoint');
        $this->domain = config('services.mailgun.domain');
        $this->secret = config('services.mailgun.secret');

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10
        ]);
    }

    public function batchSend()
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

    /**
     * Returns basic auth option
     *
     * @return array
     */
    protected function getAuthOption()
    {
        return ['api', $this->secret];
    }
}
