<?php

namespace Bazaar\Mail;

final class OrderMailer
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly string $currency = 'usd',
        private readonly string $appName = 'Bazaar'
    ) {
    }

    public function orderConfirmation(string $recipient, array $order, array $items): void
    {
        $this->mailer->send(new Email(
            $recipient,
            sprintf('%s order #%d confirmed', $this->appName, (int) $order['id']),
            $this->confirmationBody($order, $items)
        ));
    }

    public function statusUpdate(string $recipient, array $order): void
    {
        $this->mailer->send(new Email(
            $recipient,
            sprintf('%s order #%d is now %s', $this->appName, (int) $order['id'], $order['status']),
            $this->statusBody($order)
        ));
    }

    private function confirmationBody(array $order, array $items): string
    {
        $lines = [
            'Thank you for your order.',
            '',
            'Order #' . (int) $order['id'],
            '',
        ];
        foreach ($items as $item) {
            $label = $item['productName'];
            if (($item['variantLabel'] ?? '') !== '') {
                $label .= ' (' . $item['variantLabel'] . ')';
            }
            $lines[] = sprintf(
                '  %d x %s - %s',
                (int) $item['quantity'],
                $label,
                formatMoney((int) $item['unitPriceCents'] * (int) $item['quantity'], $order['currency'])
            );
        }
        $lines[] = '';
        if ((int) ($order['discountCents'] ?? 0) > 0) {
            $lines[] = 'Discount (' . ($order['couponCode'] ?? '') . '): -'
                . formatMoney((int) $order['discountCents'], $order['currency']);
        }
        $lines[] = 'Total: ' . formatMoney((int) $order['totalCents'], $order['currency']);

        return implode("\n", $lines);
    }

    private function statusBody(array $order): string
    {
        return implode("\n", [
            'Your order status has changed.',
            '',
            'Order #' . (int) $order['id'],
            'New status: ' . $order['status'],
        ]);
    }
}
