<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Invoice;
use App\Services\StripeService;
use App\Database;
use ReflectionClass;

/**
 * Stripe Webhook Feature Tests
 *
 * Tests the Stripe webhook handling functionality
 *
 * @covers \App\Services\StripeService
 */
class StripeWebhookTest extends TestCase
{
    private array $user;
    private array $client;
    private StripeService $stripeService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        $this->client = $this->createClient($this->user['id']);
        $this->stripeService = new StripeService();
    }

    public function testHandleCheckoutSessionCompleted(): void
    {
        // Create a pending invoice
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'pending',
            'total_amount' => 1200.00,
        ]);

        // Simulate webhook payload
        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_' . uniqid(),
                    'payment_intent' => 'pi_test_' . uniqid(),
                    'metadata' => [
                        'invoice_id' => $invoice['id'],
                        'user_id' => $this->user['id'],
                    ],
                ],
            ],
        ]);

        // Process webhook (without signature verification in test mode)
        $result = $this->stripeService->handleWebhook($payload, '');

        $this->assertTrue($result['handled']);
        $this->assertEquals('checkout.session.completed', $result['event']);

        // Verify invoice was marked as paid
        $updatedInvoice = Invoice::find($invoice['id']);
        $this->assertEquals('paid', $updatedInvoice['status']);
        $this->assertNotNull($updatedInvoice['paid_at']);
    }

    public function testHandlePaymentIntentSucceeded(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'pending',
        ]);

        $payload = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_' . uniqid(),
                    'metadata' => [
                        'invoice_id' => $invoice['id'],
                    ],
                ],
            ],
        ]);

        $result = $this->stripeService->handleWebhook($payload, '');

        $this->assertTrue($result['handled']);

        $updatedInvoice = Invoice::find($invoice['id']);
        $this->assertEquals('paid', $updatedInvoice['status']);
    }

    public function testHandlePaymentIntentFailed(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'pending',
        ]);

        // Store initial stripe_payment_intent_id
        Database::query(
            "UPDATE invoices SET stripe_payment_intent_id = ? WHERE id = ?",
            ['pi_test_123', $invoice['id']]
        );

        $payload = json_encode([
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_failed',
                    'metadata' => [
                        'invoice_id' => $invoice['id'],
                    ],
                    'last_payment_error' => [
                        'message' => 'Card declined',
                    ],
                ],
            ],
        ]);

        $result = $this->stripeService->handleWebhook($payload, '');

        $this->assertTrue($result['handled']);

        // Verify invoice payment status was updated to failed
        $updatedInvoice = Database::fetch(
            "SELECT stripe_payment_status FROM invoices WHERE id = ?",
            [$invoice['id']]
        );
        $this->assertEquals('failed', $updatedInvoice['stripe_payment_status']);
    }

    public function testUnhandledEventTypeReturnsNotHandled(): void
    {
        $payload = json_encode([
            'type' => 'customer.created',
            'data' => [
                'object' => [
                    'id' => 'cus_test_123',
                ],
            ],
        ]);

        $result = $this->stripeService->handleWebhook($payload, '');

        $this->assertFalse($result['handled']);
        $this->assertEquals('customer.created', $result['event']);
    }

    public function testWebhookWithMissingInvoiceIdDoesNotCrash(): void
    {
        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_' . uniqid(),
                    'payment_intent' => 'pi_test_' . uniqid(),
                    'metadata' => [], // No invoice_id
                ],
            ],
        ]);

        $result = $this->stripeService->handleWebhook($payload, '');

        // Should handle gracefully without error
        $this->assertTrue($result['handled']);
    }

    public function testVerifyWebhookSignatureWithValidSignature(): void
    {
        $reflection = new ReflectionClass($this->stripeService);
        $method = $reflection->getMethod('verifyWebhookSignature');
        $method->setAccessible(true);

        // Test with empty webhook secret (dev mode)
        $payload = json_encode(['type' => 'test']);
        $result = $method->invoke($this->stripeService, $payload, '');

        $this->assertIsArray($result);
        $this->assertEquals('test', $result['type']);
    }

    public function testVerifyWebhookSignatureRejectsOldTimestamp(): void
    {
        // Set a webhook secret
        $_ENV['STRIPE_WEBHOOK_SECRET'] = 'whsec_test_secret';

        $stripeService = new StripeService();
        $reflection = new ReflectionClass($stripeService);
        $method = $reflection->getMethod('verifyWebhookSignature');
        $method->setAccessible(true);

        $payload = '{"type": "test"}';
        $oldTimestamp = time() - 400; // More than 5 minutes ago
        $signature = "t={$oldTimestamp},v1=fake_signature";

        $result = $method->invoke($stripeService, $payload, $signature);

        $this->assertNull($result);

        unset($_ENV['STRIPE_WEBHOOK_SECRET']);
    }

    public function testInvoiceUpdateAfterSuccessfulPayment(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'pending',
            'total_amount' => 500.00,
        ]);

        $paymentIntentId = 'pi_' . bin2hex(random_bytes(12));

        $payload = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_' . uniqid(),
                    'payment_intent' => $paymentIntentId,
                    'metadata' => [
                        'invoice_id' => $invoice['id'],
                    ],
                ],
            ],
        ]);

        $this->stripeService->handleWebhook($payload, '');

        // Verify all payment fields were updated
        $updatedInvoice = Database::fetch(
            "SELECT status, paid_at, stripe_payment_id, stripe_payment_status FROM invoices WHERE id = ?",
            [$invoice['id']]
        );

        $this->assertEquals('paid', $updatedInvoice['status']);
        $this->assertNotNull($updatedInvoice['paid_at']);
        $this->assertEquals($paymentIntentId, $updatedInvoice['stripe_payment_id']);
        $this->assertEquals('paid', $updatedInvoice['stripe_payment_status']);
    }

    public function testMultipleWebhooksForSameInvoice(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'pending',
        ]);

        $paymentIntentId = 'pi_' . bin2hex(random_bytes(12));

        // First webhook: checkout.session.completed
        $payload1 = json_encode([
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_' . uniqid(),
                    'payment_intent' => $paymentIntentId,
                    'metadata' => ['invoice_id' => $invoice['id']],
                ],
            ],
        ]);

        $this->stripeService->handleWebhook($payload1, '');

        // Second webhook: payment_intent.succeeded (should not cause issues)
        $payload2 = json_encode([
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $paymentIntentId,
                    'metadata' => ['invoice_id' => $invoice['id']],
                ],
            ],
        ]);

        $this->stripeService->handleWebhook($payload2, '');

        // Invoice should still be paid
        $updatedInvoice = Invoice::find($invoice['id']);
        $this->assertEquals('paid', $updatedInvoice['status']);
    }
}
