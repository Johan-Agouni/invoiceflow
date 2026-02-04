<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Models\Invoice;
use App\Models\Settings;

/**
 * Stripe Payment Service
 *
 * Handles all Stripe payment operations including:
 * - Creating payment links for invoices
 * - Processing webhooks
 * - Managing payment status
 */
class StripeService
{
    private string $secretKey;
    private string $publishableKey;
    private string $webhookSecret;
    private string $apiVersion = '2023-10-16';

    public function __construct()
    {
        $this->secretKey = $_ENV['STRIPE_SECRET_KEY'] ?? '';
        $this->publishableKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';
        $this->webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';
    }

    /**
     * Check if Stripe is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->secretKey) && !empty($this->publishableKey);
    }

    /**
     * Create a payment link for an invoice
     */
    public function createPaymentLink(int $invoiceId, int $userId): array
    {
        $invoice = Invoice::findForUser($invoiceId, $userId);

        if (!$invoice) {
            throw new \Exception('Invoice not found');
        }

        if ($invoice['status'] === 'paid') {
            throw new \Exception('Invoice already paid');
        }

        $settings = Settings::getForUser($userId);

        // Create Stripe Checkout Session
        $response = $this->request('POST', '/checkout/sessions', [
            'mode' => 'payment',
            'payment_method_types' => ['card', 'sepa_debit'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => (int) ($invoice['total_amount'] * 100), // Convert to cents
                    'product_data' => [
                        'name' => "Facture {$invoice['number']}",
                        'description' => $settings['company_name'] ?? 'InvoiceFlow',
                    ],
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'invoice_id' => $invoiceId,
                'user_id' => $userId,
                'invoice_number' => $invoice['number'],
            ],
            'success_url' => $this->getBaseUrl() . "/invoices/{$invoiceId}?payment=success",
            'cancel_url' => $this->getBaseUrl() . "/invoices/{$invoiceId}?payment=cancelled",
            'customer_email' => $invoice['client_email'] ?? null,
            'expires_at' => time() + 3600, // 1 hour
        ]);

        // Store session ID in database
        Database::query(
            "UPDATE invoices SET stripe_session_id = ? WHERE id = ?",
            [$response['id'], $invoiceId]
        );

        return [
            'session_id' => $response['id'],
            'url' => $response['url'],
            'expires_at' => date('Y-m-d H:i:s', $response['expires_at']),
        ];
    }

    /**
     * Create a payment intent for custom payment flow
     */
    public function createPaymentIntent(int $invoiceId, int $userId): array
    {
        $invoice = Invoice::findForUser($invoiceId, $userId);

        if (!$invoice) {
            throw new \Exception('Invoice not found');
        }

        $response = $this->request('POST', '/payment_intents', [
            'amount' => (int) ($invoice['total_amount'] * 100),
            'currency' => 'eur',
            'metadata' => [
                'invoice_id' => $invoiceId,
                'user_id' => $userId,
                'invoice_number' => $invoice['number'],
            ],
            'automatic_payment_methods' => ['enabled' => true],
        ]);

        // Store payment intent ID
        Database::query(
            "UPDATE invoices SET stripe_payment_intent_id = ? WHERE id = ?",
            [$response['id'], $invoiceId]
        );

        return [
            'client_secret' => $response['client_secret'],
            'payment_intent_id' => $response['id'],
            'publishable_key' => $this->publishableKey,
        ];
    }

    /**
     * Handle Stripe webhook
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        // Verify webhook signature
        $event = $this->verifyWebhookSignature($payload, $signature);

        if (!$event) {
            throw new \Exception('Invalid webhook signature');
        }

        $result = ['handled' => false, 'event' => $event['type']];

        switch ($event['type']) {
            case 'checkout.session.completed':
                $session = $event['data']['object'];
                $this->handleCheckoutComplete($session);
                $result['handled'] = true;
                break;

            case 'payment_intent.succeeded':
                $paymentIntent = $event['data']['object'];
                $this->handlePaymentSucceeded($paymentIntent);
                $result['handled'] = true;
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event['data']['object'];
                $this->handlePaymentFailed($paymentIntent);
                $result['handled'] = true;
                break;
        }

        return $result;
    }

    /**
     * Handle successful checkout
     */
    private function handleCheckoutComplete(array $session): void
    {
        $invoiceId = $session['metadata']['invoice_id'] ?? null;

        if (!$invoiceId) {
            return;
        }

        // Mark invoice as paid
        Invoice::markAsPaid((int) $invoiceId);

        // Store payment details
        Database::query(
            "UPDATE invoices SET
                stripe_payment_id = ?,
                stripe_payment_status = 'paid'
             WHERE id = ?",
            [$session['payment_intent'], $invoiceId]
        );

        // Log the payment
        error_log("Payment completed for invoice #{$invoiceId} - Stripe PI: {$session['payment_intent']}");
    }

    /**
     * Handle successful payment intent
     */
    private function handlePaymentSucceeded(array $paymentIntent): void
    {
        $invoiceId = $paymentIntent['metadata']['invoice_id'] ?? null;

        if (!$invoiceId) {
            return;
        }

        Invoice::markAsPaid((int) $invoiceId);

        Database::query(
            "UPDATE invoices SET
                stripe_payment_id = ?,
                stripe_payment_status = 'paid'
             WHERE id = ?",
            [$paymentIntent['id'], $invoiceId]
        );
    }

    /**
     * Handle failed payment
     */
    private function handlePaymentFailed(array $paymentIntent): void
    {
        $invoiceId = $paymentIntent['metadata']['invoice_id'] ?? null;

        if (!$invoiceId) {
            return;
        }

        Database::query(
            "UPDATE invoices SET stripe_payment_status = 'failed' WHERE id = ?",
            [$invoiceId]
        );

        error_log("Payment failed for invoice #{$invoiceId} - Error: " . ($paymentIntent['last_payment_error']['message'] ?? 'Unknown'));
    }

    /**
     * Get payment status for an invoice
     */
    public function getPaymentStatus(int $invoiceId): ?array
    {
        $invoice = Database::fetch(
            "SELECT stripe_session_id, stripe_payment_intent_id, stripe_payment_id, stripe_payment_status
             FROM invoices WHERE id = ?",
            [$invoiceId]
        );

        if (!$invoice || !$invoice['stripe_payment_intent_id']) {
            return null;
        }

        // Fetch latest status from Stripe
        $response = $this->request('GET', "/payment_intents/{$invoice['stripe_payment_intent_id']}");

        return [
            'status' => $response['status'],
            'amount' => $response['amount'] / 100,
            'currency' => $response['currency'],
            'created' => date('Y-m-d H:i:s', $response['created']),
        ];
    }

    /**
     * Refund a payment
     */
    public function refund(int $invoiceId, ?float $amount = null): array
    {
        $invoice = Database::fetch(
            "SELECT stripe_payment_id, total_amount FROM invoices WHERE id = ?",
            [$invoiceId]
        );

        if (!$invoice || !$invoice['stripe_payment_id']) {
            throw new \Exception('No payment found for this invoice');
        }

        $refundData = [
            'payment_intent' => $invoice['stripe_payment_id'],
        ];

        if ($amount !== null) {
            $refundData['amount'] = (int) ($amount * 100);
        }

        $response = $this->request('POST', '/refunds', $refundData);

        // Update invoice status
        Database::query(
            "UPDATE invoices SET stripe_payment_status = 'refunded', status = 'cancelled' WHERE id = ?",
            [$invoiceId]
        );

        return [
            'refund_id' => $response['id'],
            'amount' => $response['amount'] / 100,
            'status' => $response['status'],
        ];
    }

    /**
     * Verify webhook signature
     */
    private function verifyWebhookSignature(string $payload, string $signature): ?array
    {
        if (empty($this->webhookSecret)) {
            // No webhook secret configured, parse without verification (dev mode)
            return json_decode($payload, true);
        }

        $elements = explode(',', $signature);
        $timestamp = null;
        $signatures = [];

        foreach ($elements as $element) {
            [$key, $value] = explode('=', $element, 2);
            if ($key === 't') {
                $timestamp = (int) $value;
            } elseif ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if (!$timestamp || empty($signatures)) {
            return null;
        }

        // Check timestamp tolerance (5 minutes)
        if (abs(time() - $timestamp) > 300) {
            return null;
        }

        $expectedSignature = hash_hmac('sha256', "{$timestamp}.{$payload}", $this->webhookSecret);

        foreach ($signatures as $sig) {
            if (hash_equals($expectedSignature, $sig)) {
                return json_decode($payload, true);
            }
        }

        return null;
    }

    /**
     * Make API request to Stripe
     */
    private function request(string $method, string $endpoint, array $data = []): array
    {
        $url = "https://api.stripe.com/v1" . $endpoint;

        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Stripe-Version: ' . $this->apiVersion,
        ];

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
                $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            }
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode >= 400) {
            $errorMessage = $result['error']['message'] ?? 'Unknown Stripe error';
            throw new \Exception("Stripe API Error: {$errorMessage}");
        }

        return $result;
    }

    /**
     * Get base URL for redirects
     */
    private function getBaseUrl(): string
    {
        return rtrim($_ENV['APP_URL'] ?? 'http://localhost:8080', '/');
    }

    /**
     * Get Stripe publishable key for frontend
     */
    public function getPublishableKey(): string
    {
        return $this->publishableKey;
    }
}
