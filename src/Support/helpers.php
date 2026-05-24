<?php

use Bazaar\Core\Config;

function basePath(string $relative = ''): string
{
    $root = dirname(__DIR__, 2);
    return $relative === '' ? $root : $root . '/' . ltrim($relative, '/');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    return trim((string) $value, '-');
}

function formatMoney(int $cents, string $currency = 'USD'): string
{
    $symbol = match (strtoupper($currency)) {
        'USD' => '$',
        'EUR' => 'EUR ',
        'PLN' => 'PLN ',
        default => strtoupper($currency) . ' ',
    };
    return $symbol . number_format($cents / 100, 2);
}
