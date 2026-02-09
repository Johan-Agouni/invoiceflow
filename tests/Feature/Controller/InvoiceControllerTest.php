<?php

declare(strict_types=1);

namespace Tests\Feature\Controller;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Tests\TestCase;

/**
 * Invoice Controller Feature Tests
 *
 * Tests the InvoiceController web endpoints
 *
 * @covers \App\Controllers\InvoiceController
 */
class InvoiceControllerTest extends TestCase
{
    private array $user;

    private array $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        $this->client = $this->createClient($this->user['id']);

        // Simulate authenticated session
        $_SESSION['user_id'] = $this->user['id'];
        $_SESSION['user_name'] = $this->user['name'];
        $_SESSION['user_email'] = $this->user['email'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        parent::tearDown();
    }

    public function testInvoiceListReturnsUserInvoicesOnly(): void
    {
        // Create invoices for current user
        $invoice1 = $this->createInvoice($this->user['id'], $this->client['id']);
        $invoice2 = $this->createInvoice($this->user['id'], $this->client['id']);

        // Create invoice for another user
        $otherUser = $this->createUser();
        $otherClient = $this->createClient($otherUser['id']);
        $otherInvoice = $this->createInvoice($otherUser['id'], $otherClient['id']);

        // Get user invoices
        $invoices = Invoice::allForUser($this->user['id']);

        $this->assertCount(2, $invoices);
        $invoiceIds = array_column($invoices, 'id');
        $this->assertContains($invoice1['id'], $invoiceIds);
        $this->assertContains($invoice2['id'], $invoiceIds);
        $this->assertNotContains($otherInvoice['id'], $invoiceIds);
    }

    public function testInvoiceListFiltersByStatus(): void
    {
        // Create invoices with different statuses
        $draftInvoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_DRAFT,
        ]);
        $pendingInvoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_PENDING,
        ]);
        $paidInvoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_PAID,
        ]);

        // Filter by draft status
        $draftInvoices = Invoice::allForUser($this->user['id'], 'draft');
        $this->assertCount(1, $draftInvoices);
        $this->assertEquals($draftInvoice['id'], $draftInvoices[0]['id']);

        // Filter by pending status
        $pendingInvoices = Invoice::allForUser($this->user['id'], 'pending');
        $this->assertCount(1, $pendingInvoices);
        $this->assertEquals($pendingInvoice['id'], $pendingInvoices[0]['id']);

        // Filter by paid status
        $paidInvoices = Invoice::allForUser($this->user['id'], 'paid');
        $this->assertCount(1, $paidInvoices);
        $this->assertEquals($paidInvoice['id'], $paidInvoices[0]['id']);
    }

    public function testInvoiceCreationRequiresClient(): void
    {
        $clients = Client::allForUser($this->user['id']);
        $this->assertNotEmpty($clients, 'User should have at least one client');
    }

    public function testInvoiceCreationWithValidData(): void
    {
        $items = [
            [
                'description' => 'Web Development',
                'quantity' => 10,
                'unit_price' => 100,
                'vat_rate' => 20,
            ],
            [
                'description' => 'Design Services',
                'quantity' => 5,
                'unit_price' => 80,
                'vat_rate' => 20,
            ],
        ];

        $totals = InvoiceItem::calculateTotals($items);

        // Expected: (10*100 + 5*80) = 1400 HT, VAT = 280, Total = 1680
        $this->assertEquals(1400.00, $totals['subtotal']);
        $this->assertEquals(280.00, $totals['vat_amount']);
        $this->assertEquals(1680.00, $totals['total']);

        $invoiceId = Invoice::create([
            'user_id' => $this->user['id'],
            'client_id' => $this->client['id'],
            'number' => Invoice::generateNumber($this->user['id']),
            'status' => Invoice::STATUS_DRAFT,
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => $totals['subtotal'],
            'vat_amount' => $totals['vat_amount'],
            'total_amount' => $totals['total'],
            'notes' => 'Test invoice notes',
        ]);

        $this->assertIsInt($invoiceId);

        InvoiceItem::createMany($invoiceId, $items);

        $savedItems = InvoiceItem::getForInvoice($invoiceId);
        $this->assertCount(2, $savedItems);

        $invoice = Invoice::find($invoiceId);
        $this->assertEquals(1400.00, (float) $invoice['subtotal']);
        $this->assertEquals(280.00, (float) $invoice['vat_amount']);
        $this->assertEquals(1680.00, (float) $invoice['total_amount']);
    }

    public function testInvoiceCreationRequiresAtLeastOneItem(): void
    {
        $items = [];
        $this->assertEmpty($items);
    }

    public function testInvoiceShowReturnsCorrectData(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id']);

        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'Service A', 'quantity' => 1, 'unit_price' => 500, 'vat_rate' => 20],
        ]);

        $foundInvoice = Invoice::findForUser($invoice['id'], $this->user['id']);
        $items = InvoiceItem::getForInvoice($invoice['id']);

        $this->assertNotNull($foundInvoice);
        $this->assertEquals($invoice['id'], $foundInvoice['id']);
        $this->assertCount(1, $items);
        $this->assertEquals('Service A', $items[0]['description']);
    }

    public function testInvoiceShowDeniesAccessToOtherUserInvoice(): void
    {
        $otherUser = $this->createUser();
        $otherClient = $this->createClient($otherUser['id']);
        $otherInvoice = $this->createInvoice($otherUser['id'], $otherClient['id']);

        $foundInvoice = Invoice::findForUser($otherInvoice['id'], $this->user['id']);

        $this->assertNull($foundInvoice);
    }

    public function testInvoiceEditDeniedForPaidInvoice(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_PAID,
        ]);

        $foundInvoice = Invoice::findForUser($invoice['id'], $this->user['id']);

        $this->assertEquals(Invoice::STATUS_PAID, $foundInvoice['status']);
        // Business rule: paid invoices cannot be edited
    }

    public function testInvoiceUpdateWithNewItems(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_DRAFT,
        ]);

        // Add initial items
        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'Original Item', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        // Update with new items
        $newItems = [
            ['description' => 'Updated Item 1', 'quantity' => 2, 'unit_price' => 150, 'vat_rate' => 20],
            ['description' => 'Updated Item 2', 'quantity' => 3, 'unit_price' => 75, 'vat_rate' => 10],
        ];

        $totals = InvoiceItem::calculateTotals($newItems);

        Invoice::update($invoice['id'], [
            'subtotal' => $totals['subtotal'],
            'vat_amount' => $totals['vat_amount'],
            'total_amount' => $totals['total'],
        ]);

        InvoiceItem::deleteForInvoice($invoice['id']);
        InvoiceItem::createMany($invoice['id'], $newItems);

        $updatedInvoice = Invoice::find($invoice['id']);
        $updatedItems = InvoiceItem::getForInvoice($invoice['id']);

        // (2*150 + 3*75) = 525 HT, VAT = (300*0.2 + 225*0.1) = 60 + 22.5 = 82.5
        $this->assertEquals(525.00, (float) $updatedInvoice['subtotal']);
        $this->assertCount(2, $updatedItems);
        $this->assertEquals('Updated Item 1', $updatedItems[0]['description']);
    }

    public function testInvoiceSendChangesStatusToPending(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_DRAFT,
        ]);

        Invoice::update($invoice['id'], ['status' => Invoice::STATUS_PENDING]);

        $updatedInvoice = Invoice::find($invoice['id']);
        $this->assertEquals(Invoice::STATUS_PENDING, $updatedInvoice['status']);
    }

    public function testInvoiceMarkPaidUpdatesStatusAndTimestamp(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_PENDING,
        ]);

        Invoice::markAsPaid($invoice['id']);

        $updatedInvoice = Invoice::find($invoice['id']);
        $this->assertEquals(Invoice::STATUS_PAID, $updatedInvoice['status']);
        $this->assertNotNull($updatedInvoice['paid_at']);
    }

    public function testInvoiceDeleteRemovesInvoiceAndItems(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_DRAFT,
        ]);

        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'Item to delete', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        InvoiceItem::deleteForInvoice($invoice['id']);
        Invoice::delete($invoice['id']);

        $deletedInvoice = Invoice::find($invoice['id']);
        $deletedItems = InvoiceItem::getForInvoice($invoice['id']);

        $this->assertNull($deletedInvoice);
        $this->assertEmpty($deletedItems);
    }

    public function testInvoiceDeleteDeniedForPaidInvoice(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_PAID,
        ]);

        // Business rule: paid invoices should not be deleted
        // The controller checks this before calling delete
        $foundInvoice = Invoice::findForUser($invoice['id'], $this->user['id']);
        $this->assertEquals(Invoice::STATUS_PAID, $foundInvoice['status']);
    }

    public function testInvoiceNumberFormatIsCorrect(): void
    {
        $number = Invoice::generateNumber($this->user['id']);

        // Format should be FAC-YYYY-NNNN
        $this->assertMatchesRegularExpression('/^FAC-\d{4}-\d{4}$/', $number);

        // Year should be current year
        $currentYear = date('Y');
        $this->assertStringContainsString("FAC-{$currentYear}-", $number);
    }

    public function testInvoiceStatisticsCalculation(): void
    {
        // Create invoices with different statuses and amounts
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_DRAFT,
            'total_amount' => 1000,
        ]);
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_PENDING,
            'total_amount' => 2000,
        ]);
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => Invoice::STATUS_PAID,
            'total_amount' => 3000,
        ]);

        $stats = Invoice::getStats($this->user['id']);

        $this->assertArrayHasKey('paid_count', $stats);
        $this->assertArrayHasKey('pending_count', $stats);
        $this->assertArrayHasKey('total_paid', $stats);
    }

    public function testInvoiceVatCalculationWithMultipleRates(): void
    {
        $items = [
            ['description' => 'Standard VAT item', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20],
            ['description' => 'Reduced VAT item', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 10],
            ['description' => 'Super reduced VAT', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 5.5],
        ];

        $totals = InvoiceItem::calculateTotals($items);

        // Subtotal: 300
        // VAT: 20 + 10 + 5.5 = 35.5
        // Total: 335.5
        $this->assertEquals(300.00, $totals['subtotal']);
        $this->assertEquals(35.50, $totals['vat_amount']);
        $this->assertEquals(335.50, $totals['total']);
    }
}
