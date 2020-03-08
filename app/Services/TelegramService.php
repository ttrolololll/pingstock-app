<?php

namespace App\Services;

use GuzzleHttp\Client;

class TelegramService
{

    protected $baseUrl = 'https://api.telegram.org/bot';
    protected $http;

    public function __construct($botToken, $baseUrl = '')
    {
        if ($baseUrl != '') {
            $this->baseUrl = $baseUrl;
        }

        $this->baseUrl = $this->baseUrl . $botToken;
        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10,
        ]);
    }

    /**
     * Get bot webhook information
     *
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function getWebhookInfo()
    {
        return $this->http->get($this->baseUrl . '/getWebhookInfo');
    }

    /**
     * Set bot webhook
     *
     * @param $url
     * @param int $max_connections
     * @param array $allowed_updates
     * @return \GuzzleHttp\Promise\PromiseInterface|\Psr\Http\Message\ResponseInterface
     */
    public function setWebhook($url, $max_connections = 10, $allowed_updates = [])
    {
        return $this->http->request('POST',$this->baseUrl . '/setWebhook', [
            'json' => [
                'url' => $url,
                'max_connections' => $max_connections,
                'allowed_updates' => $allowed_updates,
            ]
        ]);
    }

}
