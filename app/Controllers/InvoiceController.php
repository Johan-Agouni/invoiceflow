<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Settings;
use App\Services\PdfService;

class InvoiceController extends Controller
{
    public function index(): void
    {
        $status = $this->input('status');
        $invoices = Invoice::allForUser($this->userId(), $status);

        $this->view('invoices.index', [
            'invoices' => $invoices,
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

        $this->view('invoices.create', [
            'clients' => $clients,
            'settings' => $settings,
            'csrf_token' => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée. Veuillez réessayer.');
            $this->redirect('/invoices/create');
        }

        $userId = $this->userId();
        $clientId = (int) $this->input('client_id');

        $client = Client::findForUser($clientId, $userId);
        if (!$client) {
            $this->flash('error', 'Client non trouvé.');
            $this->redirect('/invoices/create');
        }

        // Parse items
        $items = $this->parseItems();
        if (empty($items)) {
            $this->flash('error', 'Veuillez ajouter au moins une ligne.');
            $this->redirect('/invoices/create');
        }

        $totals = InvoiceItem::calculateTotals($items);

        // Create invoice
        $invoiceId = Invoice::create([
            'user_id' => $userId,
            'client_id' => $clientId,
            'number' => Invoice::generateNumber($userId),
            'status' => Invoice::STATUS_DRAFT,
            'issue_date' => $this->input('issue_date', date('Y-m-d')),
            'due_date' => $this->input('due_date', date('Y-m-d', strtotime('+30 days'))),
            'subtotal' => $totals['subtotal'],
            'vat_amount' => $totals['vat_amount'],
            'total_amount' => $totals['total'],
            'notes' => trim($this->input('notes', '')),
        ]);

        // Create items
        InvoiceItem::createMany($invoiceId, $items);

        $this->flash('success', 'Facture créée avec succès.');
        $this->redirect("/invoices/{$invoiceId}");
    }

    public function show(string $id): void
    {
        $invoice = Invoice::findForUser((int) $id, $this->userId());

        if (!$invoice) {
            $this->flash('error', 'Facture non trouvée.');
            $this->redirect('/invoices');
        }

        $items = InvoiceItem::getForInvoice((int) $id);
        $settings = Settings::getForUser($this->userId());

        $this->view('invoices.show', [
            'invoice' => $invoice,
            'items' => $items,
            'settings' => $settings,
            'flash' => $this->getFlash(),
        ]);
    }

    public function edit(string $id): void
    {
        $invoice = Invoice::findForUser((int) $id, $this->userId());

        if (!$invoice) {
            $this->flash('error', 'Facture non trouvée.');
            $this->redirect('/invoices');
        }

        if ($invoice['status'] === Invoice::STATUS_PAID) {
            $this->flash('error', 'Une facture payée ne peut pas être modifiée.');
            $this->redirect("/invoices/{$id}");
        }

        $items = InvoiceItem::getForInvoice((int) $id);
        $clients = Client::allForUser($this->userId());
        $settings = Settings::getForUser($this->userId());

        $this->view('invoices.edit', [
            'invoice' => $invoice,
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
            $this->redirect("/invoices/{$id}/edit");
        }

        $invoice = Invoice::findForUser((int) $id, $this->userId());

        if (!$invoice || $invoice['status'] === Invoice::STATUS_PAID) {
            $this->flash('error', 'Facture non modifiable.');
            $this->redirect('/invoices');
        }

        $items = $this->parseItems();
        if (empty($items)) {
            $this->flash('error', 'Veuillez ajouter au moins une ligne.');
            $this->redirect("/invoices/{$id}/edit");
        }

        $totals = InvoiceItem::calculateTotals($items);

        Invoice::update((int) $id, [
            'client_id' => (int) $this->input('client_id'),
            'issue_date' => $this->input('issue_date'),
            'due_date' => $this->input('due_date'),
            'subtotal' => $totals['subtotal'],
            'vat_amount' => $totals['vat_amount'],
            'total_amount' => $totals['total'],
            'notes' => trim($this->input('notes', '')),
        ]);

        // Update items
        InvoiceItem::deleteForInvoice((int) $id);
        InvoiceItem::createMany((int) $id, $items);

        $this->flash('success', 'Facture mise à jour.');
        $this->redirect("/invoices/{$id}");
    }

    public function send(string $id): void
    {
        $invoice = Invoice::findForUser((int) $id, $this->userId());

        if (!$invoice) {
            $this->flash('error', 'Facture non trouvée.');
            $this->redirect('/invoices');
        }

        Invoice::update((int) $id, ['status' => Invoice::STATUS_PENDING]);

        // TODO: Send email with PDF

        $this->flash('success', 'Facture marquée comme envoyée.');
        $this->redirect("/invoices/{$id}");
    }

    public function markPaid(string $id): void
    {
        $invoice = Invoice::findForUser((int) $id, $this->userId());

        if (!$invoice) {
            $this->flash('error', 'Facture non trouvée.');
            $this->redirect('/invoices');
        }

        Invoice::markAsPaid((int) $id);

        $this->flash('success', 'Facture marquée comme payée.');
        $this->redirect("/invoices/{$id}");
    }

    public function pdf(string $id): void
    {
        $invoice = Invoice::findForUser((int) $id, $this->userId());

        if (!$invoice) {
            $this->flash('error', 'Facture non trouvée.');
            $this->redirect('/invoices');
        }

        $items = InvoiceItem::getForInvoice((int) $id);
        $settings = Settings::getForUser($this->userId());

        $pdf = new PdfService();
        $pdf->generateInvoice($invoice, $items, $settings);
    }

    public function destroy(string $id): void
    {
        $invoice = Invoice::findForUser((int) $id, $this->userId());

        if (!$invoice) {
            $this->flash('error', 'Facture non trouvée.');
            $this->redirect('/invoices');
        }

        if ($invoice['status'] === Invoice::STATUS_PAID) {
            $this->flash('error', 'Une facture payée ne peut pas être supprimée.');
            $this->redirect("/invoices/{$id}");
        }

        InvoiceItem::deleteForInvoice((int) $id);
        Invoice::delete((int) $id);

        $this->flash('success', 'Facture supprimée.');
        $this->redirect('/invoices');
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
