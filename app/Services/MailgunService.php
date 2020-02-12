<?php

namespace App\Services;

use GuzzleHttp\Client;

class MailgunService
{
    protected $baseUrl;
    protected $domain;
    protected $http;
    protected $apiToken;

    public function __construct()
    {
        $this->domain = config('services.mailgun.domain');
        $this->baseUrl = config('services.mailgun.endpoint') ?? 'api.mailgun.net';
        $this->baseUrl = "https://{$this->baseUrl}/v3/{$this->domain}";

        $this->http = new Client([
            'base_uri' => $this->baseUrl,
            'timeout' => 10
        ]);

        $this->apiToken = config('services.mailgun.secret');
    }

    public function batchSendUseTemplate($from, array $to, $subject, array $recipientVar, $template)
    {
        $from = $from ?? config('mail.from.name') . ' <' . config('mail.from.address') . '>';
        return $this->http->request(
            'POST',
            "$this->baseUrl/messages",
            [
                'form_params' => [
                    'from' => $from,
                    'to' => $this->toArrToStr($to),
                    'recipient-variables' => $this->recipientVarToJsonStr($recipientVar),
                    'subject' => $subject,
                    'template' => $template
                ],
                'auth' => $this->basicAuthPayload(),
            ]
        );
    }

    protected function recipientVarToJsonStr(array $recipientVar)
    {
        try {
            return json_encode($recipientVar);
        } catch (\Exception $e) {
            return '';
        }
    }

    protected function toArrToStr(array $to)
    {
        return implode(',', $to);
    }

    protected function basicAuthPayload()
    {
        return [
            'api',
            $this->apiToken,
        ];
    }
}
