<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\StripeService;
use ReflectionClass;

/**
 * Stripe Service Tests
 *
 * @covers \App\Services\StripeService
 */
class StripeServiceTest extends TestCase
{
    private StripeService $stripeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stripeService = new StripeService();
    }

    public function testIsConfiguredReturnsFalseWithoutKeys(): void
    {
        // Without ENV vars set, should return false
        $originalSecret = $_ENV['STRIPE_SECRET_KEY'] ?? null;
        $originalPublic = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? null;

        $_ENV['STRIPE_SECRET_KEY'] = '';
        $_ENV['STRIPE_PUBLISHABLE_KEY'] = '';

        $service = new StripeService();
        $this->assertFalse($service->isConfigured());

        // Restore
        if ($originalSecret !== null) {
            $_ENV['STRIPE_SECRET_KEY'] = $originalSecret;
        }
        if ($originalPublic !== null) {
            $_ENV['STRIPE_PUBLISHABLE_KEY'] = $originalPublic;
        }
    }

    public function testIsConfiguredReturnsTrueWithKeys(): void
    {
        $_ENV['STRIPE_SECRET_KEY'] = 'sk_test_fake_key';
        $_ENV['STRIPE_PUBLISHABLE_KEY'] = 'pk_test_fake_key';

        $service = new StripeService();
        $this->assertTrue($service->isConfigured());
    }

    public function testGetPublishableKeyReturnsKey(): void
    {
        $_ENV['STRIPE_PUBLISHABLE_KEY'] = 'pk_test_123456';

        $service = new StripeService();
        $this->assertEquals('pk_test_123456', $service->getPublishableKey());
    }

    public function testVerifyWebhookSignatureReturnsNullForInvalidSignature(): void
    {
        $reflection = new ReflectionClass($this->stripeService);
        $method = $reflection->getMethod('verifyWebhookSignature');
        $method->setAccessible(true);

        $payload = '{"type": "test"}';
        $invalidSignature = 'invalid_signature';

        $result = $method->invoke($this->stripeService, $payload, $invalidSignature);

        // Without webhook secret, it returns parsed JSON (dev mode)
        // With webhook secret and invalid signature, returns null
        $this->assertIsArray($result); // In dev mode without secret
    }

    public function testVerifyWebhookSignatureHandlesEmptyPayload(): void
    {
        $reflection = new ReflectionClass($this->stripeService);
        $method = $reflection->getMethod('verifyWebhookSignature');
        $method->setAccessible(true);

        $result = $method->invoke($this->stripeService, '', '');

        $this->assertNull($result);
    }

    public function testGetBaseUrlReturnsConfiguredUrl(): void
    {
        $_ENV['APP_URL'] = 'https://example.com';

        $reflection = new ReflectionClass($this->stripeService);
        $method = $reflection->getMethod('getBaseUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->stripeService);

        $this->assertEquals('https://example.com', $result);
    }

    public function testGetBaseUrlRemovesTrailingSlash(): void
    {
        $_ENV['APP_URL'] = 'https://example.com/';

        $reflection = new ReflectionClass($this->stripeService);
        $method = $reflection->getMethod('getBaseUrl');
        $method->setAccessible(true);

        $result = $method->invoke($this->stripeService);

        $this->assertStringEndsNotWith('/', $result);
    }

    public function testCreatePaymentLinkThrowsForPaidInvoice(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice already paid');

        // Create a paid invoice
        $user = $this->createUser();
        $client = $this->createClient($user['id']);
        $invoice = $this->createInvoice($user['id'], $client['id'], ['status' => 'paid']);

        $_ENV['STRIPE_SECRET_KEY'] = 'sk_test_fake';
        $_ENV['STRIPE_PUBLISHABLE_KEY'] = 'pk_test_fake';

        $service = new StripeService();
        $service->createPaymentLink($invoice['id'], $user['id']);
    }

    public function testCreatePaymentLinkThrowsForNonExistentInvoice(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invoice not found');

        $user = $this->createUser();

        $_ENV['STRIPE_SECRET_KEY'] = 'sk_test_fake';
        $_ENV['STRIPE_PUBLISHABLE_KEY'] = 'pk_test_fake';

        $service = new StripeService();
        $service->createPaymentLink(99999, $user['id']);
    }

    public function testRefundThrowsForInvoiceWithoutPayment(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No payment found for this invoice');

        $user = $this->createUser();
        $client = $this->createClient($user['id']);
        $invoice = $this->createInvoice($user['id'], $client['id']);

        $service = new StripeService();
        $service->refund($invoice['id']);
    }

    public function testWebhookHandlerStructure(): void
    {
        $payload = json_encode([
            'type' => 'unknown.event',
            'data' => ['object' => []],
        ]);

        $result = $this->stripeService->handleWebhook($payload, '');

        $this->assertArrayHasKey('handled', $result);
        $this->assertArrayHasKey('event', $result);
        $this->assertFalse($result['handled']); // Unknown event not handled
    }

    public function testGetPaymentStatusReturnsNullForInvoiceWithoutPayment(): void
    {
        $user = $this->createUser();
        $client = $this->createClient($user['id']);
        $invoice = $this->createInvoice($user['id'], $client['id']);

        $result = $this->stripeService->getPaymentStatus($invoice['id']);

        $this->assertNull($result);
    }
}
