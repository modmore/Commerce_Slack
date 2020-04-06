<?php

namespace modmore\Commerce_Slack\Communication;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

final class Sender
{
    /**
     * @var string
     */
    private $webhookUrl;

    public function __construct(string $webhookUrl)
    {
        $this->webhookUrl = $webhookUrl;
    }

    /**
     * @param Message $payload
     * @return ResponseInterface
     */
    public function send(Message $payload)
    {
        return $this->sendAsync($payload)->wait();
    }

    public function sendAsync(Message $payload): \GuzzleHttp\Promise\PromiseInterface
    {
        $client = $this->getClient();
        return $client->requestAsync('POST', $this->webhookUrl, [
            'http_errors' => false,
            'form_params' => [
                'payload' => json_encode($payload->getPayload()),
            ]
        ]);
    }

    private function getClient()
    {
        $client = new Client();
        return $client;
    }
}