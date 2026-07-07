<?php

namespace Bazaar\Tests;

use Bazaar\Support\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    private function translator(string $locale = 'en'): Translator
    {
        return new Translator(dirname(__DIR__) . '/resources/lang', $locale);
    }

    public function testEnglishIsTheDefault(): void
    {
        $this->assertSame('Shop', $this->translator()->get('nav.shop'));
    }

    public function testPolishCatalogResolves(): void
    {
        $this->assertSame('Sklep', $this->translator('pl')->get('nav.shop'));
    }

    public function testUnknownLocaleFallsBackToDefault(): void
    {
        $translator = $this->translator();
        $translator->setLocale('de');
        $this->assertSame('en', $translator->locale());
        $this->assertSame('Shop', $translator->get('nav.shop'));
    }

    public function testMissingKeyReturnsTheKeyItself(): void
    {
        $this->assertSame('nav.doesNotExist', $this->translator()->get('nav.doesNotExist'));
    }

    public function testReplacementTokensAreSubstituted(): void
    {
        $this->assertSame('5 results', $this->translator()->get('catalog.resultsMany', ['count' => 5]));
        $this->assertSame('5 wynikow', $this->translator('pl')->get('catalog.resultsMany', ['count' => 5]));
    }

    public function testSwitchingLocaleAtRuntime(): void
    {
        $translator = $this->translator('en');
        $this->assertSame('Cart', $translator->get('nav.cart'));

        $translator->setLocale('pl');
        $this->assertSame('Koszyk', $translator->get('nav.cart'));
    }

    public function testSupportedLocales(): void
    {
        $this->assertTrue(Translator::isSupported('pl'));
        $this->assertTrue(Translator::isSupported('en'));
        $this->assertFalse(Translator::isSupported('fr'));
    }
}
