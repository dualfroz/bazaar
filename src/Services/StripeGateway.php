<?php

namespace Bazaar\Services;

use Stripe\StripeClient;
use Stripe\Webhook;

final class StripeGateway
{
    private StripeClient $client;

    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookSecret,
        private readonly string $currency
    ) {
        $this->client = new StripeClient($this->secretKey);
    }

    public function createCheckoutSession(int $orderId, array $lines, string $successUrl, string $cancelUrl): array
    {
        $lineItems = [];
        foreach ($lines as $line) {
            $lineItems[] = [
                'quantity' => $line['quantity'],
                'price_data' => [
                    'currency' => $this->currency,
                    'unit_amount' => (int) $line['product']['priceCents'],
                    'product_data' => [
                        'name' => $line['product']['name'],
                    ],
                ],
            ];
        }

        $session = $this->client->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $orderId,
            'metadata' => ['orderId' => (string) $orderId],
        ]);

        return ['id' => $session->id, 'url' => $session->url];
    }

    public function retrieveSession(string $sessionId): array
    {
        $session = $this->client->checkout->sessions->retrieve($sessionId);
        return [
            'paymentStatus' => $session->payment_status,
            'orderId' => isset($session->metadata->orderId) ? (int) $session->metadata->orderId : null,
        ];
    }

    public function parseWebhook(string $payload, string $signature): array
    {
        $event = Webhook::constructEvent($payload, $signature, $this->webhookSecret);
        return [
            'type' => $event->type,
            'session' => $event->data->object,
        ];
    }
}
