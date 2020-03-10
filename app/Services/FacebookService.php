<?php

namespace App\Services;

use GuzzleHttp\Client;

class FacebookService
{
    protected $baseUrl;
    protected $http;

    public function __construct()
    {
        $this->baseUrl = 'https://graph.facebook.com/v6.0';

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10
        ]);
    }

    public function me($oauthToken, array $fields = ['id', 'name', 'email'])
    {
        $fieldQuery = implode(',', $fields);
        return $this->http->request(
            'GET',
            "$this->baseUrl/me?access_token=$oauthToken&fields=$fieldQuery"
        );
    }
}
