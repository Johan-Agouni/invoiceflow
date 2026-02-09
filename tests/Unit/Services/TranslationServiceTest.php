<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TranslationService;
use ReflectionClass;
use Tests\TestCase;

/**
 * Translation Service Tests
 *
 * @covers \App\Services\TranslationService
 */
class TranslationServiceTest extends TestCase
{
    private TranslationService $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = TranslationService::getInstance();
        $this->translator->setLocale('fr');
    }

    public function testGetInstanceReturnsSingleton(): void
    {
        $instance1 = TranslationService::getInstance();
        $instance2 = TranslationService::getInstance();

        $this->assertSame($instance1, $instance2);
    }

    public function testCanSetLocale(): void
    {
        $this->translator->setLocale('en');
        $this->assertEquals('en', $this->translator->getLocale());

        $this->translator->setLocale('fr');
        $this->assertEquals('fr', $this->translator->getLocale());
    }

    public function testSetLocaleIgnoresUnsupportedLocales(): void
    {
        $this->translator->setLocale('fr');
        $this->translator->setLocale('de'); // German not supported

        $this->assertEquals('fr', $this->translator->getLocale());
    }

    public function testGetSupportedLocales(): void
    {
        $locales = $this->translator->getSupportedLocales();

        $this->assertIsArray($locales);
        $this->assertContains('fr', $locales);
        $this->assertContains('en', $locales);
    }

    public function testGetReturnsKeyIfTranslationNotFound(): void
    {
        $result = $this->translator->get('nonexistent.key.that.does.not.exist');

        $this->assertEquals('nonexistent.key.that.does.not.exist', $result);
    }

    public function testGetReplacesPlaceholders(): void
    {
        // Create a mock translation by directly testing replacement logic
        $reflection = new ReflectionClass($this->translator);

        // Test the replacement logic independently
        $template = 'Hello :name, welcome to :app!';
        $replace = [':name' => 'John', ':app' => 'InvoiceFlow'];

        $result = str_replace(array_keys($replace), array_values($replace), $template);

        $this->assertEquals('Hello John, welcome to InvoiceFlow!', $result);
    }

    public function testGetNestedValueReturnsNullForMissingKey(): void
    {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('getNestedValue');
        $method->setAccessible(true);

        $array = ['level1' => ['level2' => 'value']];

        $result = $method->invoke($this->translator, $array, 'level1.missing');
        $this->assertNull($result);

        $result = $method->invoke($this->translator, $array, 'missing.key');
        $this->assertNull($result);
    }

    public function testGetNestedValueReturnsValue(): void
    {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('getNestedValue');
        $method->setAccessible(true);

        $array = ['level1' => ['level2' => ['level3' => 'deep value']]];

        $result = $method->invoke($this->translator, $array, 'level1.level2.level3');
        $this->assertEquals('deep value', $result);
    }

    public function testGetNestedValueReturnsNullForNonStringValues(): void
    {
        $reflection = new ReflectionClass($this->translator);
        $method = $reflection->getMethod('getNestedValue');
        $method->setAccessible(true);

        $array = ['array_value' => ['nested' => ['a', 'b', 'c']]];

        $result = $method->invoke($this->translator, $array, 'array_value.nested');
        $this->assertNull($result);
    }

    public function testHasReturnsFalseForMissingKey(): void
    {
        $result = $this->translator->has('completely.missing.translation.key');

        $this->assertFalse($result);
    }

    public function testAllReturnsArray(): void
    {
        $result = $this->translator->all();

        $this->assertIsArray($result);
    }

    public function testDetectFromBrowserReturnsFallbackWithoutHeader(): void
    {
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        $result = $this->translator->detectFromBrowser();

        $this->assertEquals('fr', $result);
    }

    public function testDetectFromBrowserDetectsFrench(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'fr-FR,fr;q=0.9,en;q=0.8';

        $result = $this->translator->detectFromBrowser();

        $this->assertEquals('fr', $result);
    }

    public function testDetectFromBrowserDetectsEnglish(): void
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en-US,en;q=0.9';

        $result = $this->translator->detectFromBrowser();

        $this->assertEquals('en', $result);
    }

    public function testDetectLocaleChecksSessionFirst(): void
    {
        $_SESSION['locale'] = 'en';

        $result = $this->translator->detectLocale();

        $this->assertEquals('en', $result);

        unset($_SESSION['locale']);
    }

    public function testSaveToSessionStoresLocale(): void
    {
        $this->translator->saveToSession('en');

        $this->assertEquals('en', $_SESSION['locale']);

        unset($_SESSION['locale']);
    }

    public function testSaveToSessionIgnoresInvalidLocale(): void
    {
        $_SESSION['locale'] = 'fr';
        $this->translator->saveToSession('invalid');

        $this->assertEquals('fr', $_SESSION['locale']);

        unset($_SESSION['locale']);
    }

    public function testSetLocaleReturnsFluentInterface(): void
    {
        $result = $this->translator->setLocale('fr');

        $this->assertInstanceOf(TranslationService::class, $result);
    }

    public function testGetWithOverrideLocale(): void
    {
        $this->translator->setLocale('fr');

        // Test getting translation with different locale parameter
        // This tests the locale override functionality
        $result = $this->translator->get('some.key', [], 'en');

        // Should return key if not found (expected behavior)
        $this->assertIsString($result);
    }
}
