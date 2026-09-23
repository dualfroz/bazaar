<?php

namespace Bazaar\Services;

use Bazaar\Core\Config;
use Bazaar\Support\Translator;
use Stripe\StripeClient;
use Stripe\Webhook;

final class StripeGateway
{
    private StripeClient $client;
    private Translator $translator;

    public function __construct(
        private readonly string $secretKey,
        private readonly string $webhookSecret
    ) {
        $this->client = new StripeClient($this->secretKey);
        $this->translator = new Translator(basePath('resources/lang'));
    }

    public static function fromConfig(): self
    {
        return new self(
            (string) Config::get('STRIPE_SECRET_KEY'),
            (string) Config::get('STRIPE_WEBHOOK_SECRET')
        );
    }

    public function createCheckoutSession(array $order, array $orderItems, string $successUrl, string $cancelUrl): array
    {
        $session = $this->client->checkout->sessions->create(
            $this->checkoutPayload($order, $orderItems, $successUrl, $cancelUrl)
        );

        return ['id' => $session->id, 'url' => $session->url];
    }

    public function checkoutPayload(array $order, array $orderItems, string $successUrl, string $cancelUrl): array
    {
        $orderId = (string) $order['id'];
        $this->translator->setLocale($order['locale']);

        return [
            'mode' => 'payment',
            'locale' => $order['locale'],
            'line_items' => $this->lineItems($order, $orderItems),
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => $orderId,
            'metadata' => ['orderId' => $orderId],
        ];
    }

    public function refund(array $order): array
    {
        $paymentIntentId = $order['paymentIntentId']
            ?? $this->client->checkout->sessions->retrieve($order['stripeSessionId'])->payment_intent;
        $request = $this->refundRequest($order, $paymentIntentId);
        $refund = $this->client->refunds->create($request['params'], $request['options']);

        return ['id' => $refund->id, 'amountCents' => (int) $refund->amount];
    }

    public function refundRequest(array $order, string $paymentIntentId): array
    {
        return [
            'params' => [
                'payment_intent' => $paymentIntentId,
                'amount' => (int) $order['totalCents'],
                'metadata' => ['orderId' => (string) $order['id']],
            ],
            'options' => ['idempotency_key' => 'refund-' . $paymentIntentId],
        ];
    }

    public function closeCheckoutSession(string $sessionId): bool
    {
        $session = $this->client->checkout->sessions->retrieve($sessionId);
        if ($session->status === 'open') {
            $this->client->checkout->sessions->expire($sessionId);
            return true;
        }
        return $session->status === 'expired';
    }

    public function retrieveSession(string $sessionId): array
    {
        $session = $this->client->checkout->sessions->retrieve($sessionId);
        return [
            'paymentStatus' => $session->payment_status,
            'paymentIntentId' => $session->payment_intent,
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

    private function lineItems(array $order, array $orderItems): array
    {
        $lineTotals = array_map(
            static fn (array $orderItem): int => (int) $orderItem['unitPriceCents'] * (int) $orderItem['quantity'],
            $orderItems
        );
        $discountShares = $this->allocateDiscount($lineTotals, (int) $order['discountCents']);

        $lineItems = [];
        foreach ($orderItems as $index => $orderItem) {
            $quantity = (int) $orderItem['quantity'];
            $netCents = $lineTotals[$index] - $discountShares[$index];
            $unitCents = intdiv($netCents, $quantity);
            $roundedUpUnits = $netCents - $unitCents * $quantity;
            $productData = $this->productData($orderItem, $discountShares[$index] > 0 ? $order['couponCode'] : null);

            if ($roundedUpUnits > 0) {
                $lineItems[] = $this->lineItem($order['currency'], $productData, $unitCents + 1, $roundedUpUnits);
            }
            if ($quantity > $roundedUpUnits) {
                $lineItems[] = $this->lineItem($order['currency'], $productData, $unitCents, $quantity - $roundedUpUnits);
            }
        }
        return $lineItems;
    }

    private function allocateDiscount(array $lineTotals, int $discountCents): array
    {
        $shares = array_fill_keys(array_keys($lineTotals), 0);
        if ($discountCents === 0) {
            return $shares;
        }

        $subtotalCents = array_sum($lineTotals);
        foreach ($lineTotals as $index => $lineTotal) {
            $shares[$index] = intdiv($lineTotal * $discountCents, $subtotalCents);
        }

        $remainingCents = $discountCents - array_sum($shares);
        foreach ($lineTotals as $index => $lineTotal) {
            $extraCents = min($remainingCents, $lineTotal - $shares[$index]);
            $shares[$index] += $extraCents;
            $remainingCents -= $extraCents;
        }
        return $shares;
    }

    private function productData(array $orderItem, ?string $couponCode): array
    {
        $name = $orderItem['productName'];
        if ($orderItem['variantLabel'] !== '') {
            $name .= ' (' . $orderItem['variantLabel'] . ')';
        }

        $productData = ['name' => $name];
        if ($couponCode !== null) {
            $productData['description'] = $this->translator->get('payment.couponDescription', ['code' => $couponCode]);
        }
        return $productData;
    }

    private function lineItem(string $currency, array $productData, int $unitCents, int $quantity): array
    {
        return [
            'quantity' => $quantity,
            'price_data' => [
                'currency' => $currency,
                'unit_amount' => $unitCents,
                'product_data' => $productData,
            ],
        ];
    }
}
