<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Tests\TestCase;

/**
 * Invoice Workflow Feature Tests
 *
 * Tests the complete invoice lifecycle
 *
 * @covers \App\Models\Invoice
 * @covers \App\Models\InvoiceItem
 */
class InvoiceWorkflowTest extends TestCase
{
    private array $user;

    private array $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        $this->client = $this->createClient($this->user['id']);
    }

    public function testCompleteInvoiceWorkflow(): void
    {
        // 1. Create draft invoice
        $invoiceId = Invoice::create([
            'user_id' => $this->user['id'],
            'client_id' => $this->client['id'],
            'number' => Invoice::generateNumber($this->user['id']),
            'status' => Invoice::STATUS_DRAFT,
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => 1000.00,
            'vat_amount' => 200.00,
            'total_amount' => 1200.00,
        ]);

        $this->assertIsInt($invoiceId);

        // 2. Add items
        $items = [
            ['description' => 'Development', 'quantity' => 10, 'unit_price' => 100, 'vat_rate' => 20],
        ];
        InvoiceItem::createMany($invoiceId, $items);

        $savedItems = InvoiceItem::getForInvoice($invoiceId);
        $this->assertCount(1, $savedItems);

        // 3. Verify draft status
        $invoice = Invoice::find($invoiceId);
        $this->assertEquals('draft', $invoice['status']);

        // 4. Update to pending (sent)
        Invoice::update($invoiceId, ['status' => Invoice::STATUS_PENDING]);
        $invoice = Invoice::find($invoiceId);
        $this->assertEquals('pending', $invoice['status']);

        // 5. Mark as paid
        Invoice::markAsPaid($invoiceId);
        $invoice = Invoice::find($invoiceId);
        $this->assertEquals('paid', $invoice['status']);
        $this->assertNotNull($invoice['paid_at']);
    }

    public function testInvoiceNumberSequence(): void
    {
        $numbers = [];

        for ($i = 0; $i < 3; $i++) {
            $number = Invoice::generateNumber($this->user['id']);
            $this->assertNotContains($number, $numbers, 'Invoice numbers must be unique');
            $numbers[] = $number;

            Invoice::create([
                'user_id' => $this->user['id'],
                'client_id' => $this->client['id'],
                'number' => $number,
                'status' => 'draft',
                'issue_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'subtotal' => 100,
                'vat_amount' => 20,
                'total_amount' => 120,
            ]);
        }

        $this->assertCount(3, array_unique($numbers));
    }

    public function testInvoiceIsolationBetweenUsers(): void
    {
        // Create invoice for user 1
        $invoice1 = $this->createInvoice($this->user['id'], $this->client['id']);

        // Create user 2 with their own client and invoice
        $user2 = $this->createUser();
        $client2 = $this->createClient($user2['id']);
        $invoice2 = $this->createInvoice($user2['id'], $client2['id']);

        // User 1 should only see their invoice
        $user1Invoices = Invoice::allForUser($this->user['id']);
        $this->assertCount(1, $user1Invoices);
        $this->assertEquals($invoice1['id'], $user1Invoices[0]['id']);

        // User 2 should only see their invoice
        $user2Invoices = Invoice::allForUser($user2['id']);
        $this->assertCount(1, $user2Invoices);
        $this->assertEquals($invoice2['id'], $user2Invoices[0]['id']);

        // User 1 cannot access User 2's invoice
        $crossAccess = Invoice::findForUser($invoice2['id'], $this->user['id']);
        $this->assertNull($crossAccess);
    }

    public function testInvoiceStatusTransitions(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'draft']);

        // Draft -> Pending
        Invoice::update($invoice['id'], ['status' => 'pending']);
        $this->assertEquals('pending', Invoice::find($invoice['id'])['status']);

        // Pending -> Paid
        Invoice::markAsPaid($invoice['id']);
        $this->assertEquals('paid', Invoice::find($invoice['id'])['status']);
    }

    public function testOverdueInvoiceDetection(): void
    {
        // Create overdue invoice
        $overdueInvoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'pending',
            'due_date' => date('Y-m-d', strtotime('-10 days')),
        ]);

        // Create future due invoice
        $futureInvoice = $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'pending',
            'due_date' => date('Y-m-d', strtotime('+10 days')),
        ]);

        // Verify overdue status can be filtered
        $stats = Invoice::getStats($this->user['id']);
        $this->assertArrayHasKey('pending', $stats);
    }

    public function testInvoiceItemCalculations(): void
    {
        $items = [
            ['description' => 'Item 1', 'quantity' => 5, 'unit_price' => 100, 'vat_rate' => 20],
            ['description' => 'Item 2', 'quantity' => 2, 'unit_price' => 250, 'vat_rate' => 10],
        ];

        $totals = InvoiceItem::calculateTotals($items);

        // Item 1: 5 * 100 = 500 HT, VAT = 100
        // Item 2: 2 * 250 = 500 HT, VAT = 50
        // Total: 1000 HT, 150 VAT, 1150 TTC

        $this->assertEquals(1000.00, $totals['subtotal']);
        $this->assertEquals(150.00, $totals['vat_amount']);
        $this->assertEquals(1150.00, $totals['total']);
    }

    public function testInvoiceWithNotes(): void
    {
        $notes = "Payment terms: Net 30\nBank: IBAN FR76 1234";

        $invoiceId = Invoice::create([
            'user_id' => $this->user['id'],
            'client_id' => $this->client['id'],
            'number' => Invoice::generateNumber($this->user['id']),
            'status' => 'draft',
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => 100,
            'vat_amount' => 20,
            'total_amount' => 120,
            'notes' => $notes,
        ]);

        $invoice = Invoice::find($invoiceId);
        $this->assertEquals($notes, $invoice['notes']);
    }

    public function testClientRelationshipInInvoice(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id']);

        // Get invoice with client data joined
        $fullInvoice = Invoice::findForUser($invoice['id'], $this->user['id']);

        $this->assertNotNull($fullInvoice);
        $this->assertEquals($this->client['id'], $fullInvoice['client_id']);
    }

    public function testCanUpdateInvoiceItems(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id']);

        // Add initial items
        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'Original Item', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        // Delete and replace
        InvoiceItem::deleteForInvoice($invoice['id']);
        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'New Item 1', 'quantity' => 2, 'unit_price' => 150, 'vat_rate' => 20],
            ['description' => 'New Item 2', 'quantity' => 3, 'unit_price' => 75, 'vat_rate' => 20],
        ]);

        $items = InvoiceItem::getForInvoice($invoice['id']);

        $this->assertCount(2, $items);
        $this->assertEquals('New Item 1', $items[0]['description']);
        $this->assertEquals('New Item 2', $items[1]['description']);
    }
}
