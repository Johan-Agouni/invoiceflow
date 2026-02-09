<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Invoice;
use App\Models\Quote;
use App\Models\QuoteItem;
use Tests\TestCase;

/**
 * Quote Model Tests
 *
 * @covers \App\Models\Quote
 * @covers \App\Models\QuoteItem
 */
class QuoteTest extends TestCase
{
    private array $user;

    private array $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        $this->client = $this->createClient($this->user['id']);
    }

    public function testCanCreateQuote(): void
    {
        $quoteData = [
            'user_id' => $this->user['id'],
            'client_id' => $this->client['id'],
            'number' => 'DEV-2024-0001',
            'status' => 'draft',
            'issue_date' => '2024-01-15',
            'valid_until' => '2024-02-15',
            'subtotal' => 1000.00,
            'vat_amount' => 200.00,
            'total_amount' => 1200.00,
        ];

        $quoteId = Quote::create($quoteData);

        $this->assertIsInt($quoteId);
        $this->assertDatabaseHas('quotes', [
            'id' => $quoteId,
            'number' => 'DEV-2024-0001',
        ]);
    }

    public function testCanGenerateQuoteNumber(): void
    {
        $number = Quote::generateNumber($this->user['id']);

        $this->assertMatchesRegularExpression('/^DEV-\d{4}-\d{4}$/', $number);
    }

    public function testCanGetAllQuotesForUser(): void
    {
        $this->createQuote($this->user['id'], $this->client['id']);
        $this->createQuote($this->user['id'], $this->client['id']);

        $otherUser = $this->createUser();
        $otherClient = $this->createClient($otherUser['id']);
        $this->createQuote($otherUser['id'], $otherClient['id']);

        $quotes = Quote::allForUser($this->user['id']);

        $this->assertCount(2, $quotes);
    }

    public function testCanFilterQuotesByStatus(): void
    {
        $this->createQuote($this->user['id'], $this->client['id'], ['status' => 'draft']);
        $this->createQuote($this->user['id'], $this->client['id'], ['status' => 'sent']);
        $this->createQuote($this->user['id'], $this->client['id'], ['status' => 'accepted']);

        $sentQuotes = Quote::allForUser($this->user['id'], 'sent');

        $this->assertCount(1, $sentQuotes);
        $this->assertEquals('sent', $sentQuotes[0]['status']);
    }

    public function testCanConvertQuoteToInvoice(): void
    {
        $quote = $this->createQuote($this->user['id'], $this->client['id'], [
            'status' => 'accepted',
            'subtotal' => 1000.00,
            'vat_amount' => 200.00,
            'total_amount' => 1200.00,
        ]);

        // Add items to quote
        QuoteItem::createMany($quote['id'], [
            ['description' => 'Service A', 'quantity' => 2, 'unit_price' => 500, 'vat_rate' => 20],
        ]);

        $invoiceId = Quote::convertToInvoice($quote['id'], $this->user['id']);

        // Check invoice was created
        $this->assertIsInt($invoiceId);
        $invoice = Invoice::find($invoiceId);
        $this->assertNotNull($invoice);
        $this->assertEquals(1200.00, $invoice['total_amount']);
        $this->assertEquals($this->client['id'], $invoice['client_id']);

        // Check quote status was updated
        $updatedQuote = Quote::find($quote['id']);
        $this->assertEquals('invoiced', $updatedQuote['status']);
    }

    public function testCanGetQuoteStats(): void
    {
        $this->createQuote($this->user['id'], $this->client['id'], [
            'status' => 'accepted',
            'total_amount' => 1000.00,
        ]);
        $this->createQuote($this->user['id'], $this->client['id'], [
            'status' => 'sent',
            'total_amount' => 500.00,
        ]);
        $this->createQuote($this->user['id'], $this->client['id'], [
            'status' => 'declined',
            'total_amount' => 300.00,
        ]);

        $stats = Quote::getStats($this->user['id']);

        $this->assertEquals(3, $stats['total']);
        $this->assertEquals(1, $stats['accepted']);
        $this->assertEquals(1, $stats['sent']);
        $this->assertEquals(1, $stats['declined']);
    }

    public function testCanCreateQuoteItems(): void
    {
        $quote = $this->createQuote($this->user['id'], $this->client['id']);

        $items = [
            ['description' => 'Design', 'quantity' => 5, 'unit_price' => 80.00, 'vat_rate' => 20],
            ['description' => 'Development', 'quantity' => 10, 'unit_price' => 100.00, 'vat_rate' => 20],
        ];

        QuoteItem::createMany($quote['id'], $items);

        $savedItems = QuoteItem::getForQuote($quote['id']);

        $this->assertCount(2, $savedItems);
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
}
