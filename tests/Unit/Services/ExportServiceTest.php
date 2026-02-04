<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\ExportService;
use App\Models\InvoiceItem;
use ReflectionClass;

/**
 * Export Service Tests
 *
 * @covers \App\Services\ExportService
 */
class ExportServiceTest extends TestCase
{
    private ExportService $exportService;
    private array $user;
    private array $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new ExportService();
        $this->user = $this->createUser();
        $this->client = $this->createClient($this->user['id']);
    }

    public function testCanBeInstantiated(): void
    {
        $service = new ExportService();
        $this->assertInstanceOf(ExportService::class, $service);
    }

    public function testToCsvReturnsCsvString(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id']);
        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'Test Item', 'quantity' => 2, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        $csv = $this->exportService->toCsv([$invoice], 'fr');

        $this->assertIsString($csv);
        $this->assertStringContainsString('Numero Facture', $csv);
        $this->assertStringContainsString('Test Item', $csv);
    }

    public function testToCsvIncludesBomForExcelCompatibility(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id']);
        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 50, 'vat_rate' => 20],
        ]);

        $csv = $this->exportService->toCsv([$invoice]);

        // Check for UTF-8 BOM
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
    }

    public function testToCsvUsesSemicolonDelimiter(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id']);
        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        $csv = $this->exportService->toCsv([$invoice]);

        // Semicolon is used as delimiter for French Excel compatibility
        $this->assertStringContainsString(';', $csv);
    }

    public function testGetHeadersReturnsFrenchByDefault(): void
    {
        $reflection = new ReflectionClass($this->exportService);
        $method = $reflection->getMethod('getHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->exportService, 'fr');

        $this->assertContains('Numero Facture', $headers);
        $this->assertContains('Date Emission', $headers);
        $this->assertContains('Total TTC', $headers);
    }

    public function testGetHeadersReturnsEnglish(): void
    {
        $reflection = new ReflectionClass($this->exportService);
        $method = $reflection->getMethod('getHeaders');
        $method->setAccessible(true);

        $headers = $method->invoke($this->exportService, 'en');

        $this->assertContains('Invoice Number', $headers);
        $this->assertContains('Issue Date', $headers);
        $this->assertContains('Total (incl. VAT)', $headers);
    }

    public function testTranslateStatusReturnsCorrectTranslation(): void
    {
        $reflection = new ReflectionClass($this->exportService);
        $method = $reflection->getMethod('translateStatus');
        $method->setAccessible(true);

        $this->assertEquals('Brouillon', $method->invoke($this->exportService, 'draft', 'fr'));
        $this->assertEquals('En attente', $method->invoke($this->exportService, 'pending', 'fr'));
        $this->assertEquals('Payee', $method->invoke($this->exportService, 'paid', 'fr'));
        $this->assertEquals('En retard', $method->invoke($this->exportService, 'overdue', 'fr'));

        $this->assertEquals('Draft', $method->invoke($this->exportService, 'draft', 'en'));
        $this->assertEquals('Pending', $method->invoke($this->exportService, 'pending', 'en'));
        $this->assertEquals('Paid', $method->invoke($this->exportService, 'paid', 'en'));
        $this->assertEquals('Overdue', $method->invoke($this->exportService, 'overdue', 'en'));
    }

    public function testTranslateStatusReturnsOriginalForUnknown(): void
    {
        $reflection = new ReflectionClass($this->exportService);
        $method = $reflection->getMethod('translateStatus');
        $method->setAccessible(true);

        $this->assertEquals('unknown_status', $method->invoke($this->exportService, 'unknown_status', 'fr'));
    }

    public function testToExcelReturnsXmlString(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id']);
        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'Excel Test', 'quantity' => 3, 'unit_price' => 75, 'vat_rate' => 20],
        ]);

        $excel = $this->exportService->toExcel([$invoice]);

        $this->assertIsString($excel);
        $this->assertStringContainsString('<?xml version="1.0"', $excel);
        $this->assertStringContainsString('Workbook', $excel);
        $this->assertStringContainsString('Excel Test', $excel);
    }

    public function testToExcelContainsSummaryWorksheet(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'paid']);
        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        $excel = $this->exportService->toExcel([$invoice], 'fr');

        $this->assertStringContainsString('Worksheet ss:Name="Resume"', $excel);
        $this->assertStringContainsString('Paye', $excel);
    }

    public function testToExcelEnglishSummary(): void
    {
        $invoice = $this->createInvoice($this->user['id'], $this->client['id']);
        InvoiceItem::createMany($invoice['id'], [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        $excel = $this->exportService->toExcel([$invoice], 'en');

        $this->assertStringContainsString('Worksheet ss:Name="Summary"', $excel);
        $this->assertStringContainsString('Pending', $excel);
    }

    public function testFormatRowCalculatesCorrectTotals(): void
    {
        $reflection = new ReflectionClass($this->exportService);
        $method = $reflection->getMethod('formatRow');
        $method->setAccessible(true);

        $invoice = ['number' => 'FAC-001', 'issue_date' => '2024-01-01', 'due_date' => '2024-02-01', 'status' => 'draft'];
        $client = ['company_name' => 'Test Client'];
        $item = ['description' => 'Service', 'quantity' => 2, 'unit_price' => 100, 'vat_rate' => 20];

        $row = $method->invoke($this->exportService, $invoice, $client, $item, 'fr');

        $this->assertCount(12, $row);
        $this->assertEquals('FAC-001', $row[0]);
        $this->assertEquals('Test Client', $row[4]);
        $this->assertEquals('Service', $row[5]);
    }

    public function testExportHandlesEmptyInvoices(): void
    {
        $csv = $this->exportService->toCsv([]);

        $this->assertIsString($csv);
        // Should still have headers
        $this->assertStringContainsString('Numero Facture', $csv);
    }

    public function testExportHandlesMultipleInvoices(): void
    {
        $invoice1 = $this->createInvoice($this->user['id'], $this->client['id'], ['number' => 'FAC-001']);
        $invoice2 = $this->createInvoice($this->user['id'], $this->client['id'], ['number' => 'FAC-002']);

        InvoiceItem::createMany($invoice1['id'], [
            ['description' => 'Item 1', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20],
        ]);
        InvoiceItem::createMany($invoice2['id'], [
            ['description' => 'Item 2', 'quantity' => 2, 'unit_price' => 50, 'vat_rate' => 20],
        ]);

        $csv = $this->exportService->toCsv([$invoice1, $invoice2]);

        $this->assertStringContainsString('FAC-001', $csv);
        $this->assertStringContainsString('FAC-002', $csv);
        $this->assertStringContainsString('Item 1', $csv);
        $this->assertStringContainsString('Item 2', $csv);
    }

    public function testSummaryCalculatesTotalsCorrectly(): void
    {
        $reflection = new ReflectionClass($this->exportService);
        $method = $reflection->getMethod('generateSummaryWorksheet');
        $method->setAccessible(true);

        $invoices = [
            ['total_amount' => 1000, 'status' => 'paid'],
            ['total_amount' => 500, 'status' => 'pending'],
            ['total_amount' => 300, 'status' => 'overdue'],
        ];

        $xml = $method->invoke($this->exportService, $invoices, 'fr');

        $this->assertStringContainsString('1800', $xml); // Total
        $this->assertStringContainsString('1000', $xml); // Paid
        $this->assertStringContainsString('500', $xml);  // Pending
        $this->assertStringContainsString('300', $xml);  // Overdue
    }
}
