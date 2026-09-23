<?php

namespace Bazaar\Mail;

use Bazaar\Support\Translator;

final class OrderMailer
{
    private Translator $translator;

    public function __construct(
        private readonly Mailer $mailer,
        private readonly string $currency = 'usd',
        private readonly string $appName = 'Bazaar',
        private readonly string $from = 'orders@bazaar.test'
    ) {
        $this->translator = new Translator(basePath('resources/lang'));
    }

    public function orderConfirmation(string $recipient, array $order, array $items): void
    {
        $this->translator->setLocale($order['locale']);
        $this->mailer->send(new Email(
            $recipient,
            $this->t('mail.confirmationSubject', ['app' => $this->appName, 'id' => (int) $order['id']]),
            $this->confirmationBody($order, $items),
            $this->from
        ));
    }

    public function statusUpdate(string $recipient, array $order): void
    {
        $this->translator->setLocale($order['locale']);
        $this->mailer->send(new Email(
            $recipient,
            $this->t('mail.statusSubject', [
                'app' => $this->appName,
                'id' => (int) $order['id'],
                'status' => $this->t('status.' . $order['status']),
            ]),
            $this->statusBody($order),
            $this->from
        ));
    }

    private function t(string $key, array $replacements = []): string
    {
        return $this->translator->get($key, $replacements);
    }

    private function confirmationBody(array $order, array $items): string
    {
        $lines = [
            $this->t('mail.thanks'),
            '',
            $this->t('mail.orderNumber', ['id' => (int) $order['id']]),
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
            $lines[] = $this->t('mail.discount', [
                'code' => $order['couponCode'] ?? '',
                'amount' => formatMoney((int) $order['discountCents'], $order['currency']),
            ]);
        }
        $lines[] = $this->t('mail.total', ['amount' => formatMoney((int) $order['totalCents'], $order['currency'])]);

        return implode("\n", $lines);
    }

    private function statusBody(array $order): string
    {
        $lines = [
            $this->t('mail.statusChanged'),
            '',
            $this->t('mail.orderNumber', ['id' => (int) $order['id']]),
            $this->t('mail.newStatus', ['status' => $this->t('status.' . $order['status'])]),
        ];
        if (!empty($order['refundedAt'])) {
            $lines[] = '';
            $lines[] = $this->t('mail.refunded', [
                'amount' => formatMoney((int) $order['refundedCents'], $order['currency']),
            ]);
        }
        return implode("\n", $lines);
    }
}
