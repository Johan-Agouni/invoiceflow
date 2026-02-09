<?php

declare(strict_types=1);

namespace App\Api;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\ExportService;
use App\Services\PdfService;
use App\Services\ReminderService;

/**
 * Invoice API Controller
 *
 * @api
 *
 * @tag Invoices
 */
class InvoiceApiController extends ApiController
{
    /**
     * List all invoices
     *
     * @route GET /api/v1/invoices
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

        $invoices = Invoice::allForUser($this->userId(), $status);

        // Filter by client if specified
        if ($clientId) {
            $invoices = array_filter($invoices, fn ($inv) => $inv['client_id'] == $clientId);
        }

        $total = count($invoices);
        $offset = ($page - 1) * $perPage;
        $paginatedInvoices = array_slice($invoices, $offset, $perPage);

        $data = array_map([$this, 'formatInvoice'], $paginatedInvoices);

        $this->json($this->paginate($data, $total, $page, $perPage));
    }

    /**
     * Get a single invoice
     *
     * @route GET /api/v1/invoices/{id}
     *
     * @param int $id Invoice ID
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function show(int $id): void
    {
        $this->requireAuth();

        $invoice = Invoice::findForUser($id, $this->userId());

        if (!$invoice) {
            $this->notFound('Invoice not found');
        }

        $items = InvoiceItem::getForInvoice($id);
        $client = Client::find($invoice['client_id']);

        $this->success($this->formatInvoice($invoice, $items, $client));
    }

    /**
     * Create a new invoice
     *
     * @route POST /api/v1/invoices
     *
     * @body {"client_id": 1, "issue_date": "2024-01-15", "due_date": "2024-02-15", "items": [...]}
     *
     * @response 201 {"success": true, "data": {...}}
     */
    public function store(): void
    {
        $this->requireAuth();

        $data = $this->validate([
            'client_id' => 'required|integer',
            'issue_date' => 'required|date',
            'due_date' => 'required|date',
            'notes' => 'string',
        ]);

        // Verify client belongs to user
        $client = Client::findForUser((int) $data['client_id'], $this->userId());
        if (!$client) {
            $this->error('Client not found', 404);
        }

        // Validate items
        $items = $this->input('items', []);
        if (empty($items)) {
            $this->validationError(['items' => ['At least one item is required']]);
        }

        // Calculate totals
        $totals = InvoiceItem::calculateTotals($items);

        // Create invoice
        $invoiceData = [
            'user_id' => $this->userId(),
            'client_id' => (int) $data['client_id'],
            'number' => Invoice::generateNumber($this->userId()),
            'status' => 'draft',
            'issue_date' => $data['issue_date'],
            'due_date' => $data['due_date'],
            'subtotal' => $totals['subtotal'],
            'vat_amount' => $totals['vat_amount'],
            'total_amount' => $totals['total'],
            'notes' => $data['notes'] ?? null,
        ];

        $invoiceId = Invoice::create($invoiceData);

        // Create items
        InvoiceItem::createMany($invoiceId, $items);

        $invoice = Invoice::find($invoiceId);
        $items = InvoiceItem::getForInvoice($invoiceId);

        $this->created($this->formatInvoice($invoice, $items, $client), 'Invoice created successfully');
    }

    /**
     * Update an invoice
     *
     * @route PUT /api/v1/invoices/{id}
     *
     * @param int $id Invoice ID
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function update(int $id): void
    {
        $this->requireAuth();

        $invoice = Invoice::findForUser($id, $this->userId());

        if (!$invoice) {
            $this->notFound('Invoice not found');
        }

        if ($invoice['status'] === 'paid') {
            $this->error('Cannot modify a paid invoice', 403);
        }

        $data = $this->validate([
            'client_id' => 'integer',
            'issue_date' => 'date',
            'due_date' => 'date',
            'notes' => 'string',
        ]);

        // If client_id provided, verify it belongs to user
        if (isset($data['client_id'])) {
            $client = Client::findForUser((int) $data['client_id'], $this->userId());
            if (!$client) {
                $this->error('Client not found', 404);
            }
        }

        // Handle items update if provided
        $items = $this->input('items');
        if ($items !== null) {
            if (empty($items)) {
                $this->validationError(['items' => ['At least one item is required']]);
            }

            $totals = InvoiceItem::calculateTotals($items);
            $data['subtotal'] = $totals['subtotal'];
            $data['vat_amount'] = $totals['vat_amount'];
            $data['total_amount'] = $totals['total'];

            InvoiceItem::deleteForInvoice($id);
            InvoiceItem::createMany($id, $items);
        }

        // Remove null values
        $data = array_filter($data, fn ($v) => $v !== null);

        if (!empty($data)) {
            Invoice::update($id, $data);
        }

        $invoice = Invoice::find($id);
        $items = InvoiceItem::getForInvoice($id);
        $client = Client::find($invoice['client_id']);

        $this->success($this->formatInvoice($invoice, $items, $client), 'Invoice updated successfully');
    }

    /**
     * Delete an invoice
     *
     * @route DELETE /api/v1/invoices/{id}
     *
     * @param int $id Invoice ID
     *
     * @response 200 {"success": true, "message": "Invoice deleted"}
     */
    public function destroy(int $id): void
    {
        $this->requireAuth();

        $invoice = Invoice::findForUser($id, $this->userId());

        if (!$invoice) {
            $this->notFound('Invoice not found');
        }

        if ($invoice['status'] === 'paid') {
            $this->error('Cannot delete a paid invoice', 403);
        }

        Invoice::delete($id);

        $this->success(null, 'Invoice deleted successfully');
    }

    /**
     * Send invoice to client
     *
     * @route POST /api/v1/invoices/{id}/send
     *
     * @param int $id Invoice ID
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function send(int $id): void
    {
        $this->requireAuth();

        $invoice = Invoice::findForUser($id, $this->userId());

        if (!$invoice) {
            $this->notFound('Invoice not found');
        }

        Invoice::update($id, ['status' => 'pending']);

        $invoice = Invoice::find($id);
        $this->success($this->formatInvoice($invoice), 'Invoice sent successfully');
    }

    /**
     * Mark invoice as paid
     *
     * @route POST /api/v1/invoices/{id}/pay
     *
     * @param int $id Invoice ID
     *
     * @body {"paid_at": "2024-01-20"} Optional payment date
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function markPaid(int $id): void
    {
        $this->requireAuth();

        $invoice = Invoice::findForUser($id, $this->userId());

        if (!$invoice) {
            $this->notFound('Invoice not found');
        }

        $paidAt = $this->input('paid_at') ?? date('Y-m-d H:i:s');
        Invoice::markAsPaid($id, $paidAt);

        $invoice = Invoice::find($id);
        $this->success($this->formatInvoice($invoice), 'Invoice marked as paid');
    }

    /**
     * Download invoice PDF
     *
     * @route GET /api/v1/invoices/{id}/pdf
     *
     * @param int $id Invoice ID
     *
     * @response 200 PDF file
     */
    public function pdf(int $id): void
    {
        $this->requireAuth();

        $invoice = Invoice::findForUser($id, $this->userId());

        if (!$invoice) {
            $this->notFound('Invoice not found');
        }

        $items = InvoiceItem::getForInvoice($id);
        $client = Client::find($invoice['client_id']);

        $pdfService = new PdfService();
        $pdfService->generateInvoice($invoice, $items, $client);
    }

    /**
     * Send payment reminder for an invoice
     *
     * @route POST /api/v1/invoices/{id}/remind
     *
     * @param int $id Invoice ID
     *
     * @response 200 {"success": true, "message": "Reminder sent"}
     */
    public function sendReminder(int $id): void
    {
        $this->requireAuth();

        $invoice = Invoice::findForUser($id, $this->userId());

        if (!$invoice) {
            $this->notFound('Invoice not found');
        }

        if ($invoice['status'] === 'paid') {
            $this->error('Cannot send reminder for paid invoice', 400);
        }

        if ($invoice['status'] === 'draft') {
            $this->error('Cannot send reminder for draft invoice', 400);
        }

        $reminderService = new ReminderService();
        $result = $reminderService->triggerManualReminder($id, $this->userId());

        if ($result['success']) {
            $this->success([
                'reminder_count' => ($invoice['reminder_count'] ?? 0) + 1,
            ], $result['message']);
        } else {
            $this->error($result['message'], 400);
        }
    }

    /**
     * Get invoice statistics
     *
     * @route GET /api/v1/invoices/stats
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function stats(): void
    {
        $this->requireAuth();

        $stats = Invoice::getStats($this->userId());
        $monthlyRevenue = Invoice::getMonthlyRevenue($this->userId());

        $this->success([
            'overview' => [
                'total_invoices' => (int) ($stats['total'] ?? 0),
                'draft' => (int) ($stats['draft'] ?? 0),
                'pending' => (int) ($stats['pending'] ?? 0),
                'paid' => (int) ($stats['paid'] ?? 0),
                'overdue' => (int) ($stats['overdue'] ?? 0),
            ],
            'amounts' => [
                'total_billed' => (float) ($stats['total_amount'] ?? 0),
                'total_paid' => (float) ($stats['paid_amount'] ?? 0),
                'total_pending' => (float) ($stats['pending_amount'] ?? 0),
                'total_overdue' => (float) ($stats['overdue_amount'] ?? 0),
            ],
            'monthly_revenue' => $monthlyRevenue,
        ]);
    }

    /**
     * Export invoices to CSV
     *
     * @route GET /api/v1/invoices/export/csv
     *
     * @response 200 CSV file
     */
    public function exportCsv(): void
    {
        $this->requireAuth();

        $status = $this->query('status');
        $lang = $this->query('lang', 'fr');

        $invoices = Invoice::allForUser($this->userId(), $status);

        $exportService = new ExportService();
        $csv = $exportService->toCsv($invoices, $lang);

        $filename = 'invoices_' . date('Y-m-d') . '.csv';
        $exportService->sendCsvHeaders($filename);

        echo $csv;
        exit;
    }

    /**
     * Export invoices to Excel
     *
     * @route GET /api/v1/invoices/export/excel
     *
     * @response 200 Excel file
     */
    public function exportExcel(): void
    {
        $this->requireAuth();

        $status = $this->query('status');
        $lang = $this->query('lang', 'fr');

        $invoices = Invoice::allForUser($this->userId(), $status);

        $exportService = new ExportService();
        $excel = $exportService->toExcel($invoices, $lang);

        $filename = 'invoices_' . date('Y-m-d') . '.xls';
        $exportService->sendExcelHeaders($filename);

        echo $excel;
        exit;
    }

    /**
     * Format invoice for API response
     */
    private function formatInvoice(array $invoice, ?array $items = null, ?array $client = null): array
    {
        $formatted = [
            'id' => (int) $invoice['id'],
            'number' => $invoice['number'],
            'status' => $invoice['status'],
            'issue_date' => $invoice['issue_date'],
            'due_date' => $invoice['due_date'],
            'paid_at' => $invoice['paid_at'],
            'subtotal' => (float) $invoice['subtotal'],
            'vat_amount' => (float) $invoice['vat_amount'],
            'total_amount' => (float) $invoice['total_amount'],
            'notes' => $invoice['notes'],
            'client_id' => (int) $invoice['client_id'],
            'created_at' => $invoice['created_at'],
            'updated_at' => $invoice['updated_at'],
        ];

        // Include client name if available from join
        if (isset($invoice['client_name'])) {
            $formatted['client_name'] = $invoice['client_name'];
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
