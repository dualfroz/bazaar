<?php

namespace Bazaar\Support;

final class Translator
{
    public const supported = ['en', 'pl'];

    private array $catalogs = [];

    public function __construct(
        private readonly string $langPath,
        private string $locale = 'en',
        private readonly string $fallback = 'en'
    ) {
    }

    public function setLocale(string $locale): void
    {
        $this->locale = self::isSupported($locale) ? $locale : $this->fallback;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public static function isSupported(string $locale): bool
    {
        return in_array($locale, self::supported, true);
    }

    public function get(string $key, array $replacements = []): string
    {
        $line = $this->lookup($this->locale, $key)
            ?? $this->lookup($this->fallback, $key)
            ?? $key;

        foreach ($replacements as $token => $value) {
            $line = str_replace(':' . $token, (string) $value, $line);
        }
        return $line;
    }

    private function lookup(string $locale, string $key): ?string
    {
        $catalog = $this->catalog($locale);
        $value = $catalog;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }
        return is_string($value) ? $value : null;
    }

    private function catalog(string $locale): array
    {
        if (!isset($this->catalogs[$locale])) {
            $file = $this->langPath . '/' . $locale . '.php';
            $this->catalogs[$locale] = is_file($file) ? require $file : [];
        }
        return $this->catalogs[$locale];
    }
}
