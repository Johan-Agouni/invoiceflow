<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\PdfService;
use ReflectionClass;
use Tests\TestCase;

/**
 * PDF Service Tests
 *
 * @covers \App\Services\PdfService
 */
class PdfServiceTest extends TestCase
{
    private PdfService $pdfService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pdfService = new PdfService();
    }

    public function testCanBeInstantiated(): void
    {
        $service = new PdfService();
        $this->assertInstanceOf(PdfService::class, $service);
    }

    public function testGenerateInvoiceToFileReturnsPath(): void
    {
        $invoice = [
            'number' => 'FAC-2024-0001',
            'issue_date' => '2024-01-15',
            'due_date' => '2024-02-15',
            'subtotal' => 1000.00,
            'vat_amount' => 200.00,
            'total_amount' => 1200.00,
            'status' => 'draft',
            'notes' => 'Test invoice',
            'company_name' => 'Test Client',
            'client_address' => '123 Test Street',
            'client_postal_code' => '75001',
            'client_city' => 'Paris',
            'client_country' => 'France',
            'client_vat_number' => '',
        ];

        $items = [
            [
                'description' => 'Web Development',
                'quantity' => 10,
                'unit_price' => 100.00,
                'vat_rate' => 20,
                'total' => 1000.00,
            ],
        ];

        $settings = [
            'company_name' => 'My Company',
            'company_address' => '456 Business Ave',
            'company_postal_code' => '75002',
            'company_city' => 'Paris',
            'company_siret' => '12345678901234',
            'company_vat_number' => 'FR12345678901',
            'company_logo' => null,
            'bank_iban' => 'FR7630001007941234567890185',
            'bank_bic' => 'BNPAFRPP',
            'bank_name' => 'BNP Paribas',
            'invoice_footer' => 'Merci pour votre confiance',
        ];

        $path = $this->pdfService->generateInvoice($invoice, $items, $settings, false);

        $this->assertNotNull($path);
        $this->assertFileExists($path);
        $this->assertStringEndsWith('.pdf', $path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function testGenerateQuoteToFileReturnsPath(): void
    {
        $quote = [
            'number' => 'DEV-2024-0001',
            'issue_date' => '2024-01-15',
            'valid_until' => '2024-02-15',
            'subtotal' => 2000.00,
            'vat_amount' => 400.00,
            'total_amount' => 2400.00,
            'status' => 'draft',
            'notes' => 'Test quote',
            'company_name' => 'Test Client',
            'client_address' => '123 Test Street',
            'client_postal_code' => '75001',
            'client_city' => 'Paris',
            'client_country' => 'France',
            'client_vat_number' => '',
        ];

        $items = [
            [
                'description' => 'Consulting',
                'quantity' => 20,
                'unit_price' => 100.00,
                'vat_rate' => 20,
                'total' => 2000.00,
            ],
        ];

        $settings = [
            'company_name' => 'My Company',
            'company_address' => '456 Business Ave',
            'company_postal_code' => '75002',
            'company_city' => 'Paris',
            'company_siret' => '12345678901234',
            'company_vat_number' => 'FR12345678901',
            'company_logo' => null,
            'bank_iban' => 'FR7630001007941234567890185',
            'bank_bic' => 'BNPAFRPP',
            'bank_name' => 'BNP Paribas',
            'invoice_footer' => 'Merci pour votre confiance',
        ];

        $path = $this->pdfService->generateQuote($quote, $items, $settings, false);

        $this->assertNotNull($path);
        $this->assertFileExists($path);
        $this->assertStringEndsWith('.pdf', $path);

        // Cleanup
        if (file_exists($path)) {
            unlink($path);
        }
    }

    public function testRenderInvoiceHtmlContainsInvoiceData(): void
    {
        $reflection = new ReflectionClass($this->pdfService);
        $method = $reflection->getMethod('renderInvoiceHtml');
        $method->setAccessible(true);

        $invoice = [
            'number' => 'FAC-TEST-001',
            'issue_date' => '2024-06-01',
            'due_date' => '2024-07-01',
            'subtotal' => 500.00,
            'vat_amount' => 100.00,
            'total_amount' => 600.00,
            'status' => 'pending',
            'notes' => '',
            'company_name' => 'Client SARL',
            'client_address' => '1 Rue Test',
            'client_postal_code' => '75000',
            'client_city' => 'Paris',
            'client_country' => 'France',
            'client_vat_number' => '',
        ];

        $items = [
            [
                'description' => 'Service A',
                'quantity' => 5,
                'unit_price' => 100.00,
                'vat_rate' => 20,
                'total' => 500.00,
            ],
        ];

        $settings = [
            'company_name' => 'Mon Entreprise',
            'company_address' => '2 Avenue Business',
            'company_postal_code' => '75001',
            'company_city' => 'Paris',
            'company_siret' => '',
            'company_vat_number' => '',
            'company_logo' => null,
            'bank_iban' => '',
            'bank_bic' => '',
            'bank_name' => '',
            'invoice_footer' => '',
        ];

        $html = $method->invoke($this->pdfService, $invoice, $items, $settings);

        $this->assertStringContainsString('FAC-TEST-001', $html);
        $this->assertStringContainsString('FACTURE', $html);
        $this->assertStringContainsString('Client SARL', $html);
        $this->assertStringContainsString('Service A', $html);
        $this->assertStringContainsString('Mon Entreprise', $html);
    }

    public function testRenderQuoteHtmlConvertsToDevis(): void
    {
        $reflection = new ReflectionClass($this->pdfService);
        $method = $reflection->getMethod('renderQuoteHtml');
        $method->setAccessible(true);

        $quote = [
            'number' => 'DEV-TEST-001',
            'issue_date' => '2024-06-01',
            'valid_until' => '2024-07-01',
            'subtotal' => 1000.00,
            'vat_amount' => 200.00,
            'total_amount' => 1200.00,
            'status' => 'draft',
            'notes' => '',
            'company_name' => 'Client Test',
            'client_address' => '1 Rue Example',
            'client_postal_code' => '75000',
            'client_city' => 'Paris',
            'client_country' => 'France',
            'client_vat_number' => '',
        ];

        $items = [
            [
                'description' => 'Prestation',
                'quantity' => 10,
                'unit_price' => 100.00,
                'vat_rate' => 20,
                'total' => 1000.00,
            ],
        ];

        $settings = [
            'company_name' => 'Ma Societe',
            'company_address' => '10 Rue Commerce',
            'company_postal_code' => '75002',
            'company_city' => 'Paris',
            'company_siret' => '',
            'company_vat_number' => '',
            'company_logo' => null,
            'bank_iban' => '',
            'bank_bic' => '',
            'bank_name' => '',
            'invoice_footer' => '',
        ];

        $html = $method->invoke($this->pdfService, $quote, $items, $settings);

        $this->assertStringContainsString('DEVIS', $html);
        $this->assertStringNotContainsString('>FACTURE<', $html);
    }

    public function testPdfContainsCorrectStatusBadge(): void
    {
        $reflection = new ReflectionClass($this->pdfService);
        $method = $reflection->getMethod('renderInvoiceHtml');
        $method->setAccessible(true);

        $statuses = ['draft' => 'Brouillon', 'pending' => 'En attente', 'paid' => 'Payée', 'overdue' => 'En retard'];

        foreach ($statuses as $status => $label) {
            $invoice = [
                'number' => 'FAC-STATUS-TEST',
                'issue_date' => '2024-01-01',
                'due_date' => '2024-02-01',
                'subtotal' => 100,
                'vat_amount' => 20,
                'total_amount' => 120,
                'status' => $status,
                'notes' => '',
                'company_name' => 'Test',
                'client_address' => 'Test',
                'client_postal_code' => '75000',
                'client_city' => 'Paris',
                'client_country' => 'France',
                'client_vat_number' => '',
            ];

            $html = $method->invoke($this->pdfService, $invoice, [], [
                'company_name' => 'Test',
                'company_address' => '',
                'company_postal_code' => '',
                'company_city' => '',
                'company_siret' => '',
                'company_vat_number' => '',
                'company_logo' => null,
                'bank_iban' => '',
                'bank_bic' => '',
                'bank_name' => '',
                'invoice_footer' => '',
            ]);

            $this->assertStringContainsString($label, $html, "Status '{$status}' should show label '{$label}'");
        }
    }
}
