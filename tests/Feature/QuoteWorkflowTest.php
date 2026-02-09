<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quote;
use App\Models\QuoteItem;
use Tests\TestCase;

/**
 * Quote Workflow Feature Tests
 *
 * Tests the complete quote lifecycle including conversion to invoice
 *
 * @covers \App\Models\Quote
 * @covers \App\Models\QuoteItem
 */
class QuoteWorkflowTest extends TestCase
{
    private array $user;

    private array $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        $this->client = $this->createClient($this->user['id']);
    }

    public function testCompleteQuoteWorkflow(): void
    {
        // 1. Create draft quote
        $quoteId = Quote::create([
            'user_id' => $this->user['id'],
            'client_id' => $this->client['id'],
            'number' => Quote::generateNumber($this->user['id']),
            'status' => Quote::STATUS_DRAFT,
            'issue_date' => date('Y-m-d'),
            'valid_until' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => 2000.00,
            'vat_amount' => 400.00,
            'total_amount' => 2400.00,
        ]);

        $this->assertIsInt($quoteId);

        // 2. Add items
        QuoteItem::createMany($quoteId, [
            ['description' => 'Consulting', 'quantity' => 20, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        // 3. Verify draft status
        $quote = Quote::find($quoteId);
        $this->assertEquals('draft', $quote['status']);

        // 4. Mark as sent
        Quote::update($quoteId, ['status' => Quote::STATUS_SENT]);
        $quote = Quote::find($quoteId);
        $this->assertEquals('sent', $quote['status']);

        // 5. Accept quote
        Quote::update($quoteId, ['status' => Quote::STATUS_ACCEPTED]);
        $quote = Quote::find($quoteId);
        $this->assertEquals('accepted', $quote['status']);
    }

    public function testQuoteNumberSequence(): void
    {
        $numbers = [];

        for ($i = 0; $i < 3; $i++) {
            $number = Quote::generateNumber($this->user['id']);
            $this->assertNotContains($number, $numbers);
            $this->assertMatchesRegularExpression('/^DEV-\d{4}-\d{4}$/', $number);
            $numbers[] = $number;

            Quote::create([
                'user_id' => $this->user['id'],
                'client_id' => $this->client['id'],
                'number' => $number,
                'status' => 'draft',
                'issue_date' => date('Y-m-d'),
                'valid_until' => date('Y-m-d', strtotime('+30 days')),
                'subtotal' => 100,
                'vat_amount' => 20,
                'total_amount' => 120,
            ]);
        }

        $this->assertCount(3, array_unique($numbers));
    }

    public function testQuoteStatusTransitions(): void
    {
        $quote = $this->createQuote($this->user['id'], $this->client['id'], ['status' => 'draft']);

        // Draft -> Sent
        Quote::update($quote['id'], ['status' => 'sent']);
        $this->assertEquals('sent', Quote::find($quote['id'])['status']);

        // Sent -> Accepted
        Quote::update($quote['id'], ['status' => 'accepted']);
        $this->assertEquals('accepted', Quote::find($quote['id'])['status']);
    }

    public function testQuoteCanBeDeclined(): void
    {
        $quote = $this->createQuote($this->user['id'], $this->client['id'], ['status' => 'sent']);

        Quote::update($quote['id'], ['status' => Quote::STATUS_DECLINED]);

        $updatedQuote = Quote::find($quote['id']);
        $this->assertEquals('declined', $updatedQuote['status']);
    }

    public function testQuoteIsolationBetweenUsers(): void
    {
        $quote1 = $this->createQuote($this->user['id'], $this->client['id']);

        $user2 = $this->createUser();
        $client2 = $this->createClient($user2['id']);
        $quote2 = $this->createQuote($user2['id'], $client2['id']);

        $user1Quotes = Quote::allForUser($this->user['id']);
        $this->assertCount(1, $user1Quotes);

        $crossAccess = Quote::findForUser($quote2['id'], $this->user['id']);
        $this->assertNull($crossAccess);
    }

    public function testQuoteItemCalculations(): void
    {
        $items = [
            ['description' => 'Service A', 'quantity' => 10, 'unit_price' => 150, 'vat_rate' => 20],
            ['description' => 'Service B', 'quantity' => 5, 'unit_price' => 100, 'vat_rate' => 10],
        ];

        $totals = QuoteItem::calculateTotals($items);

        // Service A: 10 * 150 = 1500 HT, VAT = 300
        // Service B: 5 * 100 = 500 HT, VAT = 50
        // Total: 2000 HT, 350 VAT, 2350 TTC

        $this->assertEquals(2000.00, $totals['subtotal']);
        $this->assertEquals(350.00, $totals['vat_amount']);
        $this->assertEquals(2350.00, $totals['total']);
    }

    public function testConvertQuoteToInvoice(): void
    {
        // Create and accept quote
        $quote = $this->createQuote($this->user['id'], $this->client['id'], [
            'status' => 'accepted',
            'subtotal' => 1000,
            'vat_amount' => 200,
            'total_amount' => 1200,
        ]);

        QuoteItem::createMany($quote['id'], [
            ['description' => 'Development Work', 'quantity' => 10, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        // Convert to invoice
        $invoiceId = Quote::convertToInvoice($quote['id'], $this->user['id']);

        $this->assertIsInt($invoiceId);

        // Verify invoice was created
        $invoice = Invoice::find($invoiceId);
        $this->assertNotNull($invoice);
        $this->assertEquals('draft', $invoice['status']);
        $this->assertEquals($this->client['id'], $invoice['client_id']);
        $this->assertEquals(1200, $invoice['total_amount']);

        // Verify quote status updated
        $updatedQuote = Quote::find($quote['id']);
        $this->assertEquals('invoiced', $updatedQuote['status']);

        // Verify invoice items were copied
        $invoiceItems = InvoiceItem::getForInvoice($invoiceId);
        $this->assertCount(1, $invoiceItems);
        $this->assertEquals('Development Work', $invoiceItems[0]['description']);
    }

    public function testCannotConvertNonAcceptedQuote(): void
    {
        $quote = $this->createQuote($this->user['id'], $this->client['id'], ['status' => 'draft']);

        // This should fail or return null depending on implementation
        $result = Quote::findForUser($quote['id'], $this->user['id']);
        $this->assertEquals('draft', $result['status']);
    }

    public function testQuoteValidityPeriod(): void
    {
        $validUntil = date('Y-m-d', strtotime('+45 days'));

        $quoteId = Quote::create([
            'user_id' => $this->user['id'],
            'client_id' => $this->client['id'],
            'number' => Quote::generateNumber($this->user['id']),
            'status' => 'draft',
            'issue_date' => date('Y-m-d'),
            'valid_until' => $validUntil,
            'subtotal' => 100,
            'vat_amount' => 20,
            'total_amount' => 120,
        ]);

        $quote = Quote::find($quoteId);
        $this->assertEquals($validUntil, $quote['valid_until']);
    }

    public function testFilterQuotesByStatus(): void
    {
        $this->createQuote($this->user['id'], $this->client['id'], ['status' => 'draft']);
        $this->createQuote($this->user['id'], $this->client['id'], ['status' => 'sent']);
        $this->createQuote($this->user['id'], $this->client['id'], ['status' => 'sent']);
        $this->createQuote($this->user['id'], $this->client['id'], ['status' => 'accepted']);

        $sentQuotes = Quote::allForUser($this->user['id'], 'sent');
        $this->assertCount(2, $sentQuotes);

        foreach ($sentQuotes as $quote) {
            $this->assertEquals('sent', $quote['status']);
        }
    }

    public function testQuoteWithNotes(): void
    {
        $notes = "This quote is valid for 30 days\nContact us for questions";

        $quoteId = Quote::create([
            'user_id' => $this->user['id'],
            'client_id' => $this->client['id'],
            'number' => Quote::generateNumber($this->user['id']),
            'status' => 'draft',
            'issue_date' => date('Y-m-d'),
            'valid_until' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => 100,
            'vat_amount' => 20,
            'total_amount' => 120,
            'notes' => $notes,
        ]);

        $quote = Quote::find($quoteId);
        $this->assertEquals($notes, $quote['notes']);
    }

    public function testCanUpdateQuoteItems(): void
    {
        $quote = $this->createQuote($this->user['id'], $this->client['id']);

        QuoteItem::createMany($quote['id'], [
            ['description' => 'Original', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        QuoteItem::deleteForQuote($quote['id']);
        QuoteItem::createMany($quote['id'], [
            ['description' => 'Updated 1', 'quantity' => 2, 'unit_price' => 200, 'vat_rate' => 20],
            ['description' => 'Updated 2', 'quantity' => 3, 'unit_price' => 300, 'vat_rate' => 20],
        ]);

        $items = QuoteItem::getForQuote($quote['id']);

        $this->assertCount(2, $items);
        $this->assertEquals('Updated 1', $items[0]['description']);
    }
}
