<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Invoice;
use App\Models\InvoiceItem;

/**
 * Invoice Model Tests
 *
 * @covers \App\Models\Invoice
 * @covers \App\Models\InvoiceItem
 */
class InvoiceTest extends TestCase
{
    private array $user;
    private array $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        $this->client = $this->createClient($this->user['id']);
    }

    public function testCanCreateInvoice(): void
    {
        $invoiceData = [
            'user_id' => $this->user['id'],
            'client_id' => $this->client['id'],
            'number' => 'FAC-2024-0001',
            'status' => 'draft',
            'issue_date' => '2024-01-15',
            'due_date' => '2024-02-15',
            'subtotal' => 1000.00,
            'vat_amount' => 200.00,
            'total_amount' => 1200.00,
        ];

        $invoiceId = Invoice::create($invoiceData);

        $this->assertIsInt($invoiceId);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'number' => 'FAC-2024-0001',
        ]);
    }

    public function testCanGenerateInvoiceNumber(): void
    {
        $number1 = Invoice::generateNumber($this->user['id']);
        $this->assertMatchesRegularExpression('/^FAC-\d{4}-\d{4}$/', $number1);

        // Create an invoice with this number
        $this->createInvoice($this->user['id'], $this->client['id'], ['number' => $number1]);

        // Next number should be different
        $number2 = Invoice::generateNumber($this->user['id']);
        $this->assertNotEquals($number1, $number2);
    }

    public function testCanGetAllInvoicesForUser(): void
    {
        $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'draft']);
        $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'paid']);

        $otherUser = $this->createUser();
        $otherClient = $this->createClient($otherUser['id']);
        $this->createInvoice($otherUser['id'], $otherClient['id']);

        $invoices = Invoice::allForUser($this->user['id']);

        $this->assertCount(2, $invoices);
    }

    public function testCanFilterInvoicesByStatus(): void
    {
        $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'draft']);
        $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'paid']);
        $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'paid']);

        $paidInvoices = Invoice::allForUser($this->user['id'], 'paid');

        $this->assertCount(2, $paidInvoices);
        foreach ($paidInvoices as $invoice) {
            $this->assertEquals('paid', $invoice['status']);
        }
    }

    public function testCanMarkInvoiceAsPaid(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'pending']);

        Invoice::markAsPaid($invoice['id']);

        $updated = Invoice::find($invoice['id']);
        $this->assertEquals('paid', $updated['status']);
        $this->assertNotNull($updated['paid_at']);
    }

    public function testCanGetInvoiceStats(): void
    {
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'paid',
            'total_amount' => 1000.00,
        ]);
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'pending',
            'total_amount' => 500.00,
        ]);
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'draft',
            'total_amount' => 300.00,
        ]);

        $stats = Invoice::getStats($this->user['id']);

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['paid']);
        $this->assertEquals(1, $stats['pending']);
        $this->assertEquals(1, $stats['draft']);
    }

    public function testCanCreateInvoiceItems(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id']);

        $items = [
            [
                'description' => 'Web Development',
                'quantity' => 10,
                'unit_price' => 100.00,
                'vat_rate' => 20,
            ],
            [
                'description' => 'Hosting',
                'quantity' => 1,
                'unit_price' => 50.00,
                'vat_rate' => 20,
            ],
        ];

        InvoiceItem::createMany($invoice['id'], $items);

        $savedItems = InvoiceItem::getForInvoice($invoice['id']);

        $this->assertCount(2, $savedItems);
        $this->assertEquals('Web Development', $savedItems[0]['description']);
        $this->assertEquals(10, $savedItems[0]['quantity']);
    }

    public function testCanCalculateItemTotals(): void
    {
        $items = [
            ['description' => 'Item 1', 'quantity' => 2, 'unit_price' => 100, 'vat_rate' => 20],
            ['description' => 'Item 2', 'quantity' => 1, 'unit_price' => 50, 'vat_rate' => 20],
        ];

        $totals = InvoiceItem::calculateTotals($items);

        $this->assertEquals(250.00, $totals['subtotal']); // (2*100) + (1*50)
        $this->assertEquals(50.00, $totals['vat_amount']); // 250 * 0.20
        $this->assertEquals(300.00, $totals['total']); // 250 + 50
    }

    public function testCanDeleteInvoiceItems(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id']);

        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'Item 1', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        InvoiceItem::deleteForInvoice($invoice['id']);

        $items = InvoiceItem::getForInvoice($invoice['id']);
        $this->assertEmpty($items);
    }
}
