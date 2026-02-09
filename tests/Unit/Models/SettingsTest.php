<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Settings;
use Tests\TestCase;

/**
 * Settings Model Tests
 *
 * @covers \App\Models\Settings
 */
class SettingsTest extends TestCase
{
    private array $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
    }

    public function testGetDefaultsReturnsExpectedStructure(): void
    {
        $defaults = Settings::getDefaults($this->user['id']);

        $this->assertArrayHasKey('user_id', $defaults);
        $this->assertArrayHasKey('company_name', $defaults);
        $this->assertArrayHasKey('company_address', $defaults);
        $this->assertArrayHasKey('company_postal_code', $defaults);
        $this->assertArrayHasKey('company_city', $defaults);
        $this->assertArrayHasKey('company_country', $defaults);
        $this->assertArrayHasKey('company_email', $defaults);
        $this->assertArrayHasKey('company_phone', $defaults);
        $this->assertArrayHasKey('company_siret', $defaults);
        $this->assertArrayHasKey('company_vat_number', $defaults);
        $this->assertArrayHasKey('default_vat_rate', $defaults);
        $this->assertArrayHasKey('payment_terms', $defaults);
        $this->assertArrayHasKey('bank_name', $defaults);
        $this->assertArrayHasKey('bank_iban', $defaults);
        $this->assertArrayHasKey('bank_bic', $defaults);
    }

    public function testGetDefaultsHasCorrectDefaultValues(): void
    {
        $defaults = Settings::getDefaults($this->user['id']);

        $this->assertEquals($this->user['id'], $defaults['user_id']);
        $this->assertEquals('France', $defaults['company_country']);
        $this->assertEquals(20.00, $defaults['default_vat_rate']);
        $this->assertEquals(30, $defaults['payment_terms']);
    }

    public function testGetForUserReturnsDefaultsIfNoSettings(): void
    {
        $settings = Settings::getForUser($this->user['id']);

        $this->assertIsArray($settings);
        $this->assertEquals($this->user['id'], $settings['user_id']);
    }

    public function testUpdateForUserCreatesSettingsIfNotExist(): void
    {
        $data = [
            'company_name' => 'Test Company SARL',
            'company_address' => '123 Test Street',
            'company_city' => 'Paris',
        ];

        Settings::updateForUser($this->user['id'], $data);

        $settings = Settings::getForUser($this->user['id']);

        $this->assertEquals('Test Company SARL', $settings['company_name']);
        $this->assertEquals('123 Test Street', $settings['company_address']);
        $this->assertEquals('Paris', $settings['company_city']);
    }

    public function testUpdateForUserUpdatesExistingSettings(): void
    {
        // First, create settings
        Settings::updateForUser($this->user['id'], ['company_name' => 'Initial Name']);

        // Then update
        Settings::updateForUser($this->user['id'], ['company_name' => 'Updated Name']);

        $settings = Settings::getForUser($this->user['id']);

        $this->assertEquals('Updated Name', $settings['company_name']);
    }

    public function testDefaultInvoiceFooterContainsPenaltyInfo(): void
    {
        $defaults = Settings::getDefaults($this->user['id']);

        $this->assertStringContainsString('retard de paiement', $defaults['invoice_footer']);
        $this->assertStringContainsString('40', $defaults['invoice_footer']);
    }

    public function testCanStoreAllSettingsFields(): void
    {
        $fullSettings = [
            'company_name' => 'Full Test Company',
            'company_address' => '456 Business Ave',
            'company_postal_code' => '75001',
            'company_city' => 'Paris',
            'company_country' => 'France',
            'company_email' => 'contact@test.com',
            'company_phone' => '+33 1 23 45 67 89',
            'company_siret' => '12345678901234',
            'company_vat_number' => 'FR12345678901',
            'default_vat_rate' => 20.00,
            'payment_terms' => 45,
            'invoice_footer' => 'Custom footer text',
            'bank_name' => 'Test Bank',
            'bank_iban' => 'FR7612345678901234567890123',
            'bank_bic' => 'TESTFRPP',
        ];

        Settings::updateForUser($this->user['id'], $fullSettings);

        $settings = Settings::getForUser($this->user['id']);

        $this->assertEquals('Full Test Company', $settings['company_name']);
        $this->assertEquals('12345678901234', $settings['company_siret']);
        $this->assertEquals('FR12345678901', $settings['company_vat_number']);
        $this->assertEquals(45, $settings['payment_terms']);
        $this->assertEquals('Test Bank', $settings['bank_name']);
    }

    public function testDifferentUsersHaveSeparateSettings(): void
    {
        $user2 = $this->createUser();

        Settings::updateForUser($this->user['id'], ['company_name' => 'User 1 Company']);
        Settings::updateForUser($user2['id'], ['company_name' => 'User 2 Company']);

        $settings1 = Settings::getForUser($this->user['id']);
        $settings2 = Settings::getForUser($user2['id']);

        $this->assertEquals('User 1 Company', $settings1['company_name']);
        $this->assertEquals('User 2 Company', $settings2['company_name']);
    }
}
