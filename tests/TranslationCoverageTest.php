<?php

namespace Bazaar\Tests;

use Bazaar\Services\CouponResult;
use Bazaar\Services\OrderException;
use Bazaar\Support\Role;
use PHPUnit\Framework\TestCase;

final class TranslationCoverageTest extends TestCase
{
    private const orderStatuses = ['pending', 'paid', 'fulfilled', 'cancelled', 'failed'];

    private static function root(): string
    {
        return dirname(__DIR__);
    }

    private static function catalogKeys(string $locale): array
    {
        $flatten = static function (array $catalog, string $prefix) use (&$flatten): array {
            $keys = [];
            foreach ($catalog as $segment => $value) {
                $key = $prefix . $segment;
                $keys = is_array($value) ? array_merge($keys, $flatten($value, $key . '.')) : [...$keys, $key];
            }
            return $keys;
        };
        $keys = $flatten(require self::root() . '/resources/lang/' . $locale . '.php', '');
        sort($keys);
        return $keys;
    }

    private static function sourceFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::root() . '/' . $directory));
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        sort($files);
        return $files;
    }

    private static function referencedKeys(): array
    {
        $keys = [];
        foreach ([...self::sourceFiles('views'), ...self::sourceFiles('src')] as $file) {
            preg_match_all('/(?:\$t|->t|translator->get)\(\s*\'([A-Za-z.]+)\'/', file_get_contents($file), $matches);
            foreach ($matches[1] as $key) {
                $keys[$key][] = substr($file, strlen(self::root()) + 1);
            }
        }
        ksort($keys);
        return $keys;
    }

    private static function dynamicKeys(): array
    {
        $constants = static fn (string $class): array => array_values((new \ReflectionClass($class))->getConstants());
        $keys = [];
        foreach (self::orderStatuses as $status) {
            $keys[] = 'status.' . $status;
        }
        foreach (Role::all() as $role) {
            $keys[] = 'roles.' . $role;
        }
        foreach ($constants(OrderException::class) as $reason) {
            $keys[] = 'orderErrors.' . $reason;
        }
        foreach ($constants(CouponResult::class) as $status) {
            if ($status !== CouponResult::valid) {
                $keys[] = 'coupon.' . $status;
            }
        }
        $keys[] = 'orderErrors.paymentStarted';
        return $keys;
    }

    public function testBothLanguageFilesDefineExactlyTheSameKeys(): void
    {
        $english = self::catalogKeys('en');
        $polish = self::catalogKeys('pl');

        $this->assertSame([], array_values(array_diff($english, $polish)), 'Keys missing from pl.php');
        $this->assertSame([], array_values(array_diff($polish, $english)), 'Keys missing from en.php');
    }

    public function testEveryKeyUsedInViewsAndCodeExistsInBothLanguages(): void
    {
        $referenced = self::referencedKeys();
        $this->assertGreaterThan(200, count($referenced));

        foreach (['en', 'pl'] as $locale) {
            $available = array_flip(self::catalogKeys($locale));
            foreach ($referenced as $key => $files) {
                if (str_ends_with($key, '.')) {
                    $this->assertNotEmpty(
                        array_filter(array_keys($available), static fn (string $candidate): bool => str_starts_with($candidate, $key)),
                        "Key group {$key} has no entries in {$locale}.php"
                    );
                    continue;
                }
                $this->assertArrayHasKey($key, $available, "Key {$key} used in " . implode(', ', array_unique($files)) . " is missing from {$locale}.php");
            }
            foreach (self::dynamicKeys() as $key) {
                $this->assertArrayHasKey($key, $available, "Dynamic key {$key} is missing from {$locale}.php");
            }
        }
    }

    public function testEveryViewIsTranslated(): void
    {
        $views = self::sourceFiles('views');
        $this->assertGreaterThan(30, count($views));

        foreach ($views as $file) {
            $name = substr($file, strlen(self::root()) + 1);
            $source = file_get_contents($file);
            $markup = preg_replace('/<\?(?:php|=).*?\?>/s', '', $source);

            preg_match_all('/>([^<]*)</', $markup, $textNodes);
            foreach ($textNodes[1] as $text) {
                $text = trim(html_entity_decode($text));
                $this->assertDoesNotMatchRegularExpression('/[A-Za-z]{2,}/', $text, "Untranslated text \"{$text}\" in {$name}");
            }

            preg_match_all('/\s(?:placeholder|title|aria-label|alt)="([^"]*)"/', $markup, $attributes);
            foreach ($attributes[1] as $value) {
                if (preg_match('/^(?:[A-Z0-9-]+|https?:.*)$/', $value) === 1) {
                    continue;
                }
                $this->assertDoesNotMatchRegularExpression('/[A-Za-z]{2,}/', $value, "Untranslated attribute \"{$value}\" in {$name}");
            }

            $this->assertStringNotContainsString("confirm('", $markup, "Untranslated confirm dialog in {$name}");
        }
    }

    public function testTranslationsAreAscii(): void
    {
        foreach (['en', 'pl'] as $locale) {
            $source = file_get_contents(self::root() . '/resources/lang/' . $locale . '.php');
            $this->assertSame(1, preg_match('/^[\x00-\x7F]*$/', $source), "{$locale}.php contains non-ASCII characters");
        }
    }
}
