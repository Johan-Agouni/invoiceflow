<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Models\Invoice;
use App\Services\StripeService;

/**
 * Payment Controller
 *
 * Handles payment-related actions for invoices
 */
class PaymentController extends Controller
{
    private StripeService $stripe;

    public function __construct()
    {
        $this->stripe = new StripeService();
    }

    /**
     * Create a payment link and redirect to Stripe Checkout
     */
    public function checkout(int $id): void
    {
        $invoice = Invoice::findForUser($id, $this->userId());

        if (!$invoice) {
            $this->flash('error', 'Facture introuvable.');
            $this->redirect('/invoices');
        }

        if ($invoice['status'] === 'paid') {
            $this->flash('info', 'Cette facture a déjà été payée.');
            $this->redirect("/invoices/{$id}");
        }

        if (!$this->stripe->isConfigured()) {
            $this->flash('error', 'Les paiements en ligne ne sont pas configurés.');
            $this->redirect("/invoices/{$id}");
        }

        try {
            $result = $this->stripe->createPaymentLink($id, $this->userId());
            $this->redirect($result['url']);
        } catch (\Exception $e) {
            $this->flash('error', 'Erreur lors de la création du paiement: ' . $e->getMessage());
            $this->redirect("/invoices/{$id}");
        }
    }

    /**
     * Handle Stripe webhook
     */
    public function webhook(): void
    {
        // Get raw POST data
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

        try {
            $result = $this->stripe->handleWebhook($payload, $signature);
            $this->json(['received' => true, 'handled' => $result['handled']]);
        } catch (\Exception $e) {
            http_response_code(400);
            $this->json(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get payment intent for custom payment form
     */
    public function createIntent(int $id): void
    {
        $invoice = Invoice::findForUser($id, $this->userId());

        if (!$invoice) {
            $this->json(['error' => 'Invoice not found'], 404);
        }

        if (!$this->stripe->isConfigured()) {
            $this->json(['error' => 'Payments not configured'], 400);
        }

        try {
            $result = $this->stripe->createPaymentIntent($id, $this->userId());
            $this->json($result);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get payment status
     */
    public function status(int $id): void
    {
        $invoice = Invoice::findForUser($id, $this->userId());

        if (!$invoice) {
            $this->json(['error' => 'Invoice not found'], 404);
        }

        try {
            $status = $this->stripe->getPaymentStatus($id);
            $this->json(['status' => $status]);
        } catch (\Exception $e) {
            $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Process refund
     */
    public function refund(int $id): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée.');
            $this->redirect("/invoices/{$id}");
        }

        $invoice = Invoice::findForUser($id, $this->userId());

        if (!$invoice) {
            $this->flash('error', 'Facture introuvable.');
            $this->redirect('/invoices');
        }

        try {
            $amount = $this->input('amount') ? (float) $this->input('amount') : null;
            $result = $this->stripe->refund($id, $amount);

            $this->flash('success', "Remboursement effectué: {$result['amount']}€");
            $this->redirect("/invoices/{$id}");
        } catch (\Exception $e) {
            $this->flash('error', 'Erreur de remboursement: ' . $e->getMessage());
            $this->redirect("/invoices/{$id}");
        }
    }
}
