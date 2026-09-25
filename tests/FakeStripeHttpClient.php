<?php

namespace Bazaar\Tests;

use Stripe\HttpClient\ClientInterface;

final class FakeStripeHttpClient implements ClientInterface
{
    public array $requests = [];
    private array $responses = [];

    public function respondWith(int $status, array $body): void
    {
        $this->responses[] = [$status, $body];
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1')
    {
        $this->requests[] = [
            'method' => $method,
            'url' => $absUrl,
            'headers' => $headers,
            'params' => $params,
        ];
        [$status, $body] = array_shift($this->responses) ?? [500, ['error' => ['message' => 'No canned response']]];
        return [json_encode($body), $status, []];
    }

    public function header(array $request, string $name): ?string
    {
        foreach ($request['headers'] as $line) {
            [$headerName, $value] = array_map('trim', explode(':', $line, 2));
            if (strcasecmp($headerName, $name) === 0) {
                return $value;
            }
        }
        return null;
    }
}
