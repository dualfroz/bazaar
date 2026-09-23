<?php

namespace Bazaar\Services;

final class OrderException extends \RuntimeException
{
    public const notFound = 'notFound';
    public const notPaid = 'notPaid';
    public const notPending = 'notPending';
    public const notCancellable = 'notCancellable';
    public const changed = 'changed';
    public const refundFailed = 'refundFailed';

    public function __construct(public readonly string $reason, ?\Throwable $previous = null)
    {
        parent::__construct(match ($reason) {
            self::notFound => 'Order not found',
            self::notPaid => 'Only paid orders can be fulfilled',
            self::notPending => 'Only unpaid orders can be cancelled',
            self::notCancellable => 'Only pending or paid orders can be cancelled',
            self::changed => 'The order changed in the meantime',
            self::refundFailed => 'The payment provider did not accept the refund',
        }, 0, $previous);
    }
}
