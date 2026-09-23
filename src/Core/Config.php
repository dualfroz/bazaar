<?php

namespace Bazaar\Core;

final class Config
{
    private static array $values = [];
    private static bool $loaded = false;

    public static function load(string $envFile): void
    {
        self::$loaded = true;
        if (!is_file($envFile)) {
            self::hydrateDefaults();
            return;
        }

        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            [$name, $value] = array_pad(explode('=', $line, 2), 2, '');
            $name = trim($name);
            $value = trim($value);
            if (strlen($value) >= 2 && ($value[0] === '"' || $value[0] === "'")) {
                $value = substr($value, 1, -1);
            }
            self::$values[$name] = $value;
        }
        self::hydrateDefaults();
    }

    private static function hydrateDefaults(): void
    {
        self::$values += [
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => 'http://127.0.0.1:8000',
            'DB_DRIVER' => 'sqlite',
            'DB_DATABASE' => basePath('database/bazaar.sqlite'),
            'DB_HOST' => '127.0.0.1',
            'DB_PORT' => '3306',
            'DB_USERNAME' => 'root',
            'DB_PASSWORD' => '',
            'STRIPE_SECRET_KEY' => 'sk_test_placeholder',
            'STRIPE_PUBLISHABLE_KEY' => 'pk_test_placeholder',
            'STRIPE_WEBHOOK_SECRET' => 'whsec_placeholder',
            'CURRENCY' => 'usd',
            'MAIL_DRIVER' => 'log',
            'MAIL_HOST' => '',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => '',
            'MAIL_PASSWORD' => '',
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_FROM' => 'orders@bazaar.test',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        if (!self::$loaded) {
            self::hydrateDefaults();
            self::$loaded = true;
        }
        return self::$values[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::$values[$key] = $value;
    }

    public static function stripeConfigured(): bool
    {
        $key = (string) self::get('STRIPE_SECRET_KEY');
        return str_starts_with($key, 'sk_') && !str_contains($key, 'placeholder');
    }
}
