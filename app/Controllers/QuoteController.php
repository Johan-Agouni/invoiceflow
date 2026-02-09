<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\Settings;
use App\Services\MailService;
use App\Services\PdfService;

class QuoteController extends Controller
{
    public function index(): void
    {
        $status = $this->input('status');
        $quotes = Quote::allForUser($this->userId(), $status);

        $this->view('quotes.index', [
            'quotes' => $quotes,
            'currentStatus' => $status,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $clients = Client::allForUser($this->userId());
        $settings = Settings::getForUser($this->userId());

        if (empty($clients)) {
            $this->flash('error', 'Vous devez d\'abord créer un client.');
            $this->redirect('/clients/create');
        }

        $this->view('quotes.create', [
            'clients' => $clients,
            'settings' => $settings,
            'csrf_token' => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée.');
            $this->redirect('/quotes/create');
        }

        $userId = $this->userId();
        $clientId = (int) $this->input('client_id');

        $client = Client::findForUser($clientId, $userId);
        if (!$client) {
            $this->flash('error', 'Client non trouvé.');
            $this->redirect('/quotes/create');
        }

        $items = $this->parseItems();
        if (empty($items)) {
            $this->flash('error', 'Veuillez ajouter au moins une ligne.');
            $this->redirect('/quotes/create');
        }

        $totals = QuoteItem::calculateTotals($items);

        $quoteId = Quote::create([
            'user_id' => $userId,
            'client_id' => $clientId,
            'number' => Quote::generateNumber($userId),
            'status' => Quote::STATUS_DRAFT,
            'issue_date' => $this->input('issue_date', date('Y-m-d')),
            'valid_until' => $this->input('valid_until', date('Y-m-d', strtotime('+30 days'))),
            'subtotal' => $totals['subtotal'],
            'vat_amount' => $totals['vat_amount'],
            'total_amount' => $totals['total'],
            'notes' => trim($this->input('notes', '')),
        ]);

        QuoteItem::createMany($quoteId, $items);

        $this->flash('success', 'Devis créé avec succès.');
        $this->redirect("/quotes/{$quoteId}");
    }

    public function show(string $id): void
    {
        $quote = Quote::findForUser((int) $id, $this->userId());

        if (!$quote) {
            $this->flash('error', 'Devis non trouvé.');
            $this->redirect('/quotes');
        }

        $items = QuoteItem::getForQuote((int) $id);
        $settings = Settings::getForUser($this->userId());

        $this->view('quotes.show', [
            'quote' => $quote,
            'items' => $items,
            'settings' => $settings,
            'flash' => $this->getFlash(),
        ]);
    }

    public function edit(string $id): void
    {
        $quote = Quote::findForUser((int) $id, $this->userId());

        if (!$quote || in_array($quote['status'], [Quote::STATUS_ACCEPTED, Quote::STATUS_INVOICED], true)) {
            $this->flash('error', 'Devis non modifiable.');
            $this->redirect('/quotes');
        }

        $items = QuoteItem::getForQuote((int) $id);
        $clients = Client::allForUser($this->userId());
        $settings = Settings::getForUser($this->userId());

        $this->view('quotes.edit', [
            'quote' => $quote,
            'items' => $items,
            'clients' => $clients,
            'settings' => $settings,
            'csrf_token' => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée.');
            $this->redirect("/quotes/{$id}/edit");
        }

        $quote = Quote::findForUser((int) $id, $this->userId());

        if (!$quote || in_array($quote['status'], [Quote::STATUS_ACCEPTED, Quote::STATUS_INVOICED], true)) {
            $this->flash('error', 'Devis non modifiable.');
            $this->redirect('/quotes');
        }

        $items = $this->parseItems();
        if (empty($items)) {
            $this->flash('error', 'Veuillez ajouter au moins une ligne.');
            $this->redirect("/quotes/{$id}/edit");
        }

        $totals = QuoteItem::calculateTotals($items);

        Quote::update((int) $id, [
            'client_id' => (int) $this->input('client_id'),
            'issue_date' => $this->input('issue_date'),
            'valid_until' => $this->input('valid_until'),
            'subtotal' => $totals['subtotal'],
            'vat_amount' => $totals['vat_amount'],
            'total_amount' => $totals['total'],
            'notes' => trim($this->input('notes', '')),
        ]);

        QuoteItem::deleteForQuote((int) $id);
        QuoteItem::createMany((int) $id, $items);

        $this->flash('success', 'Devis mis à jour.');
        $this->redirect("/quotes/{$id}");
    }

    public function send(string $id): void
    {
        $quote = Quote::findForUser((int) $id, $this->userId());

        if (!$quote) {
            $this->flash('error', 'Devis non trouvé.');
            $this->redirect('/quotes');
        }

        $items = QuoteItem::getForQuote((int) $id);
        $settings = Settings::getForUser($this->userId());

        // Generate PDF to temp file
        $pdf = new PdfService();
        $pdfPath = $pdf->generateQuote($quote, $items, $settings, false);

        // Send email with PDF attachment
        $mailService = new MailService();
        $sent = $mailService->sendQuote($quote, $settings, $pdfPath);

        // Clean up temp file
        if ($pdfPath && file_exists($pdfPath)) {
            unlink($pdfPath);
        }

        Quote::update((int) $id, ['status' => Quote::STATUS_SENT]);

        if ($sent) {
            $this->flash('success', 'Devis envoyé par email avec succès.');
        } else {
            $this->flash('warning', 'Devis marqué comme envoyé, mais l\'email n\'a pas pu être envoyé.');
        }

        $this->redirect("/quotes/{$id}");
    }

    public function accept(string $id): void
    {
        $quote = Quote::findForUser((int) $id, $this->userId());

        if (!$quote) {
            $this->flash('error', 'Devis non trouvé.');
            $this->redirect('/quotes');
        }

        Quote::update((int) $id, ['status' => Quote::STATUS_ACCEPTED]);

        $this->flash('success', 'Devis accepté.');
        $this->redirect("/quotes/{$id}");
    }

    public function decline(string $id): void
    {
        $quote = Quote::findForUser((int) $id, $this->userId());

        if (!$quote) {
            $this->flash('error', 'Devis non trouvé.');
            $this->redirect('/quotes');
        }

        Quote::update((int) $id, ['status' => Quote::STATUS_DECLINED]);

        $this->flash('success', 'Devis refusé.');
        $this->redirect("/quotes/{$id}");
    }

    public function convertToInvoice(string $id): void
    {
        $quote = Quote::findForUser((int) $id, $this->userId());

        if (!$quote || $quote['status'] !== Quote::STATUS_ACCEPTED) {
            $this->flash('error', 'Seuls les devis acceptés peuvent être convertis.');
            $this->redirect('/quotes');
        }

        $invoiceId = Quote::convertToInvoice((int) $id, $this->userId());

        $this->flash('success', 'Devis converti en facture.');
        $this->redirect("/invoices/{$invoiceId}");
    }

    public function pdf(string $id): void
    {
        $quote = Quote::findForUser((int) $id, $this->userId());

        if (!$quote) {
            $this->flash('error', 'Devis non trouvé.');
            $this->redirect('/quotes');
        }

        $items = QuoteItem::getForQuote((int) $id);
        $settings = Settings::getForUser($this->userId());

        $pdf = new PdfService();
        $pdf->generateQuote($quote, $items, $settings);
    }

    public function destroy(string $id): void
    {
        $quote = Quote::findForUser((int) $id, $this->userId());

        if (!$quote || $quote['status'] === Quote::STATUS_INVOICED) {
            $this->flash('error', 'Devis non supprimable.');
            $this->redirect('/quotes');
        }

        QuoteItem::deleteForQuote((int) $id);
        Quote::delete((int) $id);

        $this->flash('success', 'Devis supprimé.');
        $this->redirect('/quotes');
    }

    private function parseItems(): array
    {
        $descriptions = $this->input('item_description', []);
        $quantities = $this->input('item_quantity', []);
        $prices = $this->input('item_price', []);
        $vatRates = $this->input('item_vat', []);

        $items = [];
        foreach ($descriptions as $i => $description) {
            if (empty(trim($description))) {
                continue;
            }

            $items[] = [
                'description' => trim($description),
                'quantity' => (float) ($quantities[$i] ?? 1),
                'unit_price' => (float) ($prices[$i] ?? 0),
                'vat_rate' => (float) ($vatRates[$i] ?? 20),
            ];
        }

        return $items;
    }
}
