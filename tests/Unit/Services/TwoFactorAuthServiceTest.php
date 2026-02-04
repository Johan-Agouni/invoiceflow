<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TwoFactorAuthService;

class TwoFactorAuthServiceTest extends TestCase
{
    private TwoFactorAuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TwoFactorAuthService();
    }

    public function testGenerateSecretReturnsValidBase32String(): void
    {
        $secret = $this->service->generateSecret();

        $this->assertNotEmpty($secret);
        $this->assertMatchesRegularExpression('/^[A-Z2-7]+$/', $secret);
        $this->assertGreaterThanOrEqual(32, strlen($secret));
    }

    public function testGenerateRecoveryCodesReturnsCorrectCount(): void
    {
        $codes = $this->service->generateRecoveryCodes(8);

        $this->assertCount(8, $codes);
    }

    public function testGenerateRecoveryCodesReturnsUniqueValues(): void
    {
        $codes = $this->service->generateRecoveryCodes(8);

        $uniqueCodes = array_unique($codes);
        $this->assertCount(8, $uniqueCodes);
    }

    public function testGenerateRecoveryCodesMatchesExpectedFormat(): void
    {
        $codes = $this->service->generateRecoveryCodes(1);

        $this->assertMatchesRegularExpression('/^[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}$/', $codes[0]);
    }

    public function testVerifyCodeWithValidCode(): void
    {
        $secret = $this->service->generateSecret();

        // Générer le code actuel
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $validCode = $google2fa->getCurrentOtp($secret);

        $result = $this->service->verifyCode($secret, $validCode);

        $this->assertTrue($result);
    }

    public function testVerifyCodeWithInvalidCode(): void
    {
        $secret = $this->service->generateSecret();

        $result = $this->service->verifyCode($secret, '000000');

        $this->assertFalse($result);
    }

    public function testVerifyCodeWithEmptyCode(): void
    {
        $secret = $this->service->generateSecret();

        $result = $this->service->verifyCode($secret, '');

        $this->assertFalse($result);
    }

    public function testEnableAndDisable2FA(): void
    {
        $user = $this->createUser();
        $secret = $this->service->generateSecret();

        // Activer le 2FA
        $enabled = $this->service->enable($user['id'], $secret);
        $this->assertTrue($enabled);
        $this->assertTrue($this->service->isEnabled($user['id']));

        // Vérifier que le secret est récupérable
        $storedSecret = $this->service->getSecret($user['id']);
        $this->assertEquals($secret, $storedSecret);

        // Vérifier les codes de récupération
        $codes = $this->service->getRecoveryCodes($user['id']);
        $this->assertCount(8, $codes);

        // Désactiver le 2FA
        $disabled = $this->service->disable($user['id']);
        $this->assertTrue($disabled);
        $this->assertFalse($this->service->isEnabled($user['id']));
    }

    public function testUseRecoveryCode(): void
    {
        $user = $this->createUser();
        $secret = $this->service->generateSecret();
        $this->service->enable($user['id'], $secret);

        $codes = $this->service->getRecoveryCodes($user['id']);
        $codeToUse = $codes[0];

        // Utiliser le code
        $result = $this->service->useRecoveryCode($user['id'], $codeToUse);
        $this->assertTrue($result);

        // Le code ne devrait plus être disponible
        $remainingCodes = $this->service->getRecoveryCodes($user['id']);
        $this->assertCount(7, $remainingCodes);
        $this->assertNotContains($codeToUse, $remainingCodes);

        // Réutiliser le même code devrait échouer
        $result = $this->service->useRecoveryCode($user['id'], $codeToUse);
        $this->assertFalse($result);
    }

    public function testRegenerateRecoveryCodes(): void
    {
        $user = $this->createUser();
        $secret = $this->service->generateSecret();
        $this->service->enable($user['id'], $secret);

        $originalCodes = $this->service->getRecoveryCodes($user['id']);
        $newCodes = $this->service->regenerateRecoveryCodes($user['id']);

        $this->assertCount(8, $newCodes);
        $this->assertNotEquals($originalCodes, $newCodes);
    }

    public function testIsEnabledReturnsFalseForNewUser(): void
    {
        $user = $this->createUser();

        $this->assertFalse($this->service->isEnabled($user['id']));
    }

    public function testGetSecretReturnsNullWhenNotEnabled(): void
    {
        $user = $this->createUser();

        $this->assertNull($this->service->getSecret($user['id']));
    }

    public function testUseRecoveryCodeIsCaseInsensitive(): void
    {
        $user = $this->createUser();
        $secret = $this->service->generateSecret();
        $this->service->enable($user['id'], $secret);

        $codes = $this->service->getRecoveryCodes($user['id']);
        $codeToUse = strtolower($codes[0]);

        $result = $this->service->useRecoveryCode($user['id'], $codeToUse);
        $this->assertTrue($result);
    }
}
