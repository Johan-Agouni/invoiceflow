<?php

declare(strict_types=1);

namespace App\Api;

use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Services\PdfService;

/**
 * Quote API Controller
 *
 * @api
 *
 * @tag Quotes
 */
class QuoteApiController extends ApiController
{
    /**
     * List all quotes
     *
     * @route GET /api/v1/quotes
     *
     * @response 200 {"success": true, "data": [...], "meta": {...}}
     */
    public function index(): void
    {
        $this->requireAuth();

        $page = max(1, (int) $this->query('page', 1));
        $perPage = min(100, max(1, (int) $this->query('per_page', 20)));
        $status = $this->query('status');
        $clientId = $this->query('client_id');

        $quotes = Quote::allForUser($this->userId(), $status);

        if ($clientId) {
            $quotes = array_filter($quotes, fn ($q) => $q['client_id'] == $clientId);
        }

        $total = count($quotes);
        $offset = ($page - 1) * $perPage;
        $paginatedQuotes = array_slice($quotes, $offset, $perPage);

        $data = array_map([$this, 'formatQuote'], $paginatedQuotes);

        $this->json($this->paginate($data, $total, $page, $perPage));
    }

    /**
     * Get a single quote
     *
     * @route GET /api/v1/quotes/{id}
     *
     * @param int $id Quote ID
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function show(int $id): void
    {
        $this->requireAuth();

        $quote = Quote::findForUser($id, $this->userId());

        if (!$quote) {
            $this->notFound('Quote not found');
        }

        $items = QuoteItem::getForQuote($id);
        $client = Client::find($quote['client_id']);

        $this->success($this->formatQuote($quote, $items, $client));
    }

    /**
     * Create a new quote
     *
     * @route POST /api/v1/quotes
     *
     * @body {"client_id": 1, "issue_date": "2024-01-15", "valid_until": "2024-02-15", "items": [...]}
     *
     * @response 201 {"success": true, "data": {...}}
     */
    public function store(): void
    {
        $this->requireAuth();

        $data = $this->validate([
            'client_id' => 'required|integer',
            'issue_date' => 'required|date',
            'valid_until' => 'required|date',
            'notes' => 'string',
        ]);

        $client = Client::findForUser((int) $data['client_id'], $this->userId());
        if (!$client) {
            $this->error('Client not found', 404);
        }

        $items = $this->input('items', []);
        if (empty($items)) {
            $this->validationError(['items' => ['At least one item is required']]);
        }

        $totals = QuoteItem::calculateTotals($items);

        $quoteData = [
            'user_id' => $this->userId(),
            'client_id' => (int) $data['client_id'],
            'number' => Quote::generateNumber($this->userId()),
            'status' => 'draft',
            'issue_date' => $data['issue_date'],
            'valid_until' => $data['valid_until'],
            'subtotal' => $totals['subtotal'],
            'vat_amount' => $totals['vat_amount'],
            'total_amount' => $totals['total'],
            'notes' => $data['notes'] ?? null,
        ];

        $quoteId = Quote::create($quoteData);
        QuoteItem::createMany($quoteId, $items);

        $quote = Quote::find($quoteId);
        $items = QuoteItem::getForQuote($quoteId);

        $this->created($this->formatQuote($quote, $items, $client), 'Quote created successfully');
    }

    /**
     * Update a quote
     *
     * @route PUT /api/v1/quotes/{id}
     *
     * @param int $id Quote ID
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function update(int $id): void
    {
        $this->requireAuth();

        $quote = Quote::findForUser($id, $this->userId());

        if (!$quote) {
            $this->notFound('Quote not found');
        }

        if (in_array($quote['status'], ['accepted', 'invoiced'], true)) {
            $this->error('Cannot modify an accepted or invoiced quote', 403);
        }

        $data = $this->validate([
            'client_id' => 'integer',
            'issue_date' => 'date',
            'valid_until' => 'date',
            'notes' => 'string',
        ]);

        if (isset($data['client_id'])) {
            $client = Client::findForUser((int) $data['client_id'], $this->userId());
            if (!$client) {
                $this->error('Client not found', 404);
            }
        }

        $items = $this->input('items');
        if ($items !== null) {
            if (empty($items)) {
                $this->validationError(['items' => ['At least one item is required']]);
            }

            $totals = QuoteItem::calculateTotals($items);
            $data['subtotal'] = $totals['subtotal'];
            $data['vat_amount'] = $totals['vat_amount'];
            $data['total_amount'] = $totals['total'];

            QuoteItem::deleteForQuote($id);
            QuoteItem::createMany($id, $items);
        }

        $data = array_filter($data, fn ($v) => $v !== null);

        if (!empty($data)) {
            Quote::update($id, $data);
        }

        $quote = Quote::find($id);
        $items = QuoteItem::getForQuote($id);
        $client = Client::find($quote['client_id']);

        $this->success($this->formatQuote($quote, $items, $client), 'Quote updated successfully');
    }

    /**
     * Delete a quote
     *
     * @route DELETE /api/v1/quotes/{id}
     *
     * @param int $id Quote ID
     *
     * @response 200 {"success": true, "message": "Quote deleted"}
     */
    public function destroy(int $id): void
    {
        $this->requireAuth();

        $quote = Quote::findForUser($id, $this->userId());

        if (!$quote) {
            $this->notFound('Quote not found');
        }

        if ($quote['status'] === 'invoiced') {
            $this->error('Cannot delete an invoiced quote', 403);
        }

        Quote::delete($id);

        $this->success(null, 'Quote deleted successfully');
    }

    /**
     * Send quote to client
     *
     * @route POST /api/v1/quotes/{id}/send
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function send(int $id): void
    {
        $this->requireAuth();

        $quote = Quote::findForUser($id, $this->userId());

        if (!$quote) {
            $this->notFound('Quote not found');
        }

        Quote::update($id, ['status' => 'sent']);

        $quote = Quote::find($id);
        $this->success($this->formatQuote($quote), 'Quote sent successfully');
    }

    /**
     * Accept a quote
     *
     * @route POST /api/v1/quotes/{id}/accept
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function accept(int $id): void
    {
        $this->requireAuth();

        $quote = Quote::findForUser($id, $this->userId());

        if (!$quote) {
            $this->notFound('Quote not found');
        }

        Quote::update($id, ['status' => 'accepted']);

        $quote = Quote::find($id);
        $this->success($this->formatQuote($quote), 'Quote accepted');
    }

    /**
     * Decline a quote
     *
     * @route POST /api/v1/quotes/{id}/decline
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function decline(int $id): void
    {
        $this->requireAuth();

        $quote = Quote::findForUser($id, $this->userId());

        if (!$quote) {
            $this->notFound('Quote not found');
        }

        Quote::update($id, ['status' => 'declined']);

        $quote = Quote::find($id);
        $this->success($this->formatQuote($quote), 'Quote declined');
    }

    /**
     * Convert quote to invoice
     *
     * @route POST /api/v1/quotes/{id}/convert
     *
     * @response 201 {"success": true, "data": {...}}
     */
    public function convert(int $id): void
    {
        $this->requireAuth();

        $quote = Quote::findForUser($id, $this->userId());

        if (!$quote) {
            $this->notFound('Quote not found');
        }

        if ($quote['status'] !== 'accepted') {
            $this->error('Only accepted quotes can be converted to invoices', 400);
        }

        $invoiceId = Quote::convertToInvoice($id, $this->userId());

        $invoice = \App\Models\Invoice::find($invoiceId);
        $items = \App\Models\InvoiceItem::getForInvoice($invoiceId);

        $this->created([
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoice['number'],
            'quote_id' => $id,
            'message' => 'Quote converted to invoice successfully',
        ]);
    }

    /**
     * Download quote PDF
     *
     * @route GET /api/v1/quotes/{id}/pdf
     *
     * @response 200 PDF file
     */
    public function pdf(int $id): void
    {
        $this->requireAuth();

        $quote = Quote::findForUser($id, $this->userId());

        if (!$quote) {
            $this->notFound('Quote not found');
        }

        $items = QuoteItem::getForQuote($id);
        $client = Client::find($quote['client_id']);

        $pdfService = new PdfService();
        $pdfService->generateQuote($quote, $items, $client);
    }

    /**
     * Get quote statistics
     *
     * @route GET /api/v1/quotes/stats
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function stats(): void
    {
        $this->requireAuth();

        $stats = Quote::getStats($this->userId());

        $this->success([
            'overview' => [
                'total_quotes' => (int) ($stats['total'] ?? 0),
                'draft' => (int) ($stats['draft'] ?? 0),
                'sent' => (int) ($stats['sent'] ?? 0),
                'accepted' => (int) ($stats['accepted'] ?? 0),
                'declined' => (int) ($stats['declined'] ?? 0),
                'expired' => (int) ($stats['expired'] ?? 0),
                'invoiced' => (int) ($stats['invoiced'] ?? 0),
            ],
            'amounts' => [
                'total_quoted' => (float) ($stats['total_amount'] ?? 0),
                'accepted_amount' => (float) ($stats['accepted_amount'] ?? 0),
                'pending_amount' => (float) ($stats['pending_amount'] ?? 0),
            ],
            'conversion_rate' => $stats['total'] > 0
                ? round(($stats['accepted'] / $stats['total']) * 100, 1)
                : 0,
        ]);
    }

    /**
     * Format quote for API response
     */
    private function formatQuote(array $quote, ?array $items = null, ?array $client = null): array
    {
        $formatted = [
            'id' => (int) $quote['id'],
            'number' => $quote['number'],
            'status' => $quote['status'],
            'issue_date' => $quote['issue_date'],
            'valid_until' => $quote['valid_until'],
            'subtotal' => (float) $quote['subtotal'],
            'vat_amount' => (float) $quote['vat_amount'],
            'total_amount' => (float) $quote['total_amount'],
            'notes' => $quote['notes'],
            'client_id' => (int) $quote['client_id'],
            'created_at' => $quote['created_at'],
            'updated_at' => $quote['updated_at'],
        ];

        if (isset($quote['client_name'])) {
            $formatted['client_name'] = $quote['client_name'];
        }

        if ($client) {
            $formatted['client'] = [
                'id' => (int) $client['id'],
                'company_name' => $client['company_name'],
                'email' => $client['email'],
            ];
        }

        if ($items) {
            $formatted['items'] = array_map(fn ($item) => [
                'id' => (int) $item['id'],
                'description' => $item['description'],
                'quantity' => (float) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'vat_rate' => (float) $item['vat_rate'],
                'total' => (float) $item['total'],
            ], $items);
        }

        return $formatted;
    }
}
