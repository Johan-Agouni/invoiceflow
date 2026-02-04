<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CurrencyService;

class CurrencyServiceTest extends TestCase
{
    private CurrencyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CurrencyService();
    }

    public function testGetActiveCurrenciesReturnsArray(): void
    {
        $currencies = $this->service->getActiveCurrencies();

        $this->assertIsArray($currencies);
        $this->assertNotEmpty($currencies);
    }

    public function testGetCurrencyReturnsValidCurrency(): void
    {
        $currency = $this->service->getCurrency('EUR');

        $this->assertNotNull($currency);
        $this->assertEquals('EUR', $currency['code']);
        $this->assertEquals('Euro', $currency['name']);
        $this->assertEquals('€', $currency['symbol']);
    }

    public function testGetCurrencyReturnsNullForInvalidCode(): void
    {
        $currency = $this->service->getCurrency('INVALID');

        $this->assertNull($currency);
    }

    public function testGetCurrencyIsCaseInsensitive(): void
    {
        $currency1 = $this->service->getCurrency('eur');
        $currency2 = $this->service->getCurrency('EUR');

        $this->assertNotNull($currency1);
        $this->assertEquals($currency1['code'], $currency2['code']);
    }

    public function testGetExchangeRateReturnOneForSameCurrency(): void
    {
        $rate = $this->service->getExchangeRate('EUR', 'EUR');

        $this->assertEquals(1.0, $rate);
    }

    public function testSaveRateAndRetrieve(): void
    {
        $this->service->saveRate('EUR', 'USD', 1.0850, date('Y-m-d'), 'test');

        $rate = $this->service->getExchangeRate('EUR', 'USD');

        $this->assertNotNull($rate);
        $this->assertEquals(1.0850, $rate);
    }

    public function testConvertAmount(): void
    {
        $this->service->saveRate('EUR', 'USD', 1.10, date('Y-m-d'), 'test');

        $result = $this->service->convert(100.00, 'EUR', 'USD');

        $this->assertEquals(110.00, $result);
    }

    public function testConvertAmountReturnsNullWithoutRate(): void
    {
        $result = $this->service->convert(100.00, 'EUR', 'XYZ');

        $this->assertNull($result);
    }

    public function testConvertSameCurrencyReturnsSameAmount(): void
    {
        $result = $this->service->convert(100.00, 'EUR', 'EUR');

        $this->assertEquals(100.00, $result);
    }

    public function testFormatEuro(): void
    {
        $formatted = $this->service->format(1234.56, 'EUR');

        $this->assertStringContainsString('1 234,56', $formatted);
        $this->assertStringContainsString('€', $formatted);
    }

    public function testFormatUsd(): void
    {
        $formatted = $this->service->format(1234.56, 'USD');

        $this->assertStringContainsString('1 234,56', $formatted);
        $this->assertStringContainsString('$', $formatted);
    }

    public function testFormatJpyNoDecimals(): void
    {
        $formatted = $this->service->format(1234, 'JPY');

        $this->assertStringContainsString('1 234', $formatted);
        $this->assertStringContainsString('¥', $formatted);
        $this->assertStringNotContainsString(',00', $formatted);
    }

    public function testFormatUnknownCurrencyFallback(): void
    {
        $formatted = $this->service->format(100.00, 'XYZ');

        $this->assertStringContainsString('100,00', $formatted);
        $this->assertStringContainsString('XYZ', $formatted);
    }

    public function testGetRateHistory(): void
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $this->service->saveRate('EUR', 'USD', 1.08, $yesterday, 'test');
        $this->service->saveRate('EUR', 'USD', 1.09, $today, 'test');

        $history = $this->service->getRateHistory('EUR', 'USD', 7);

        $this->assertCount(2, $history);
    }

    public function testAddCurrency(): void
    {
        $result = $this->service->addCurrency('TST', 'Test Currency', 'T', 2);

        $this->assertTrue($result);

        $currency = $this->service->getCurrency('TST');
        $this->assertNotNull($currency);
        $this->assertEquals('Test Currency', $currency['name']);
    }

    public function testToggleCurrency(): void
    {
        // Désactiver une devise
        $result = $this->service->toggleCurrency('JPY', false);
        $this->assertTrue($result);

        // Vérifier qu'elle n'est plus dans les devises actives
        $activeCurrencies = $this->service->getActiveCurrencies();
        $codes = array_column($activeCurrencies, 'code');
        $this->assertNotContains('JPY', $codes);

        // Réactiver
        $this->service->toggleCurrency('JPY', true);
        $activeCurrencies = $this->service->getActiveCurrencies();
        $codes = array_column($activeCurrencies, 'code');
        $this->assertContains('JPY', $codes);
    }

    public function testInverseRateCalculation(): void
    {
        $this->service->saveRate('EUR', 'USD', 1.10, date('Y-m-d'), 'test');

        // Demander le taux inverse
        $rate = $this->service->getExchangeRate('USD', 'EUR');

        $this->assertNotNull($rate);
        $this->assertEqualsWithDelta(0.909, $rate, 0.001);
    }
}
