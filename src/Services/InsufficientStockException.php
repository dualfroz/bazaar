<?php

namespace Bazaar\Services;

final class InsufficientStockException extends \RuntimeException
{
    public function __construct(public readonly string $productName)
    {
        parent::__construct('Insufficient stock for ' . $productName);
    }
}
