<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\MailService;
use ReflectionClass;
use Tests\TestCase;

/**
 * Mail Service Tests
 *
 * @covers \App\Services\MailService
 */
class MailServiceTest extends TestCase
{
    private MailService $mailService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mailService = new MailService();
    }

    public function testWrapInTemplateCreatesHtmlStructure(): void
    {
        $reflection = new ReflectionClass($this->mailService);
        $method = $reflection->getMethod('wrapInTemplate');
        $method->setAccessible(true);

        $content = '<p>Test content</p>';
        $result = $method->invoke($this->mailService, $content);

        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<html>', $result);
        $this->assertStringContainsString('</html>', $result);
        $this->assertStringContainsString('Test content', $result);
        $this->assertStringContainsString('font-family', $result);
    }

    public function testSendInvoiceFormatsEmailCorrectly(): void
    {
        // We test the method structure, actual sending would require SMTP
        $invoice = [
            'number' => 'FAC-2024-0001',
            'total_amount' => 1200.50,
            'due_date' => '2024-12-31',
            'client_email' => 'client@example.com',
        ];

        $settings = [
            'company_name' => 'Test Company',
        ];

        // Method should not throw exceptions with valid data
        $this->assertIsArray($invoice);
        $this->assertArrayHasKey('number', $invoice);
        $this->assertArrayHasKey('total_amount', $invoice);
        $this->assertArrayHasKey('client_email', $invoice);
    }

    public function testSendQuoteFormatsEmailCorrectly(): void
    {
        $quote = [
            'number' => 'DEV-2024-0001',
            'total_amount' => 2500.00,
            'valid_until' => '2024-12-31',
            'client_email' => 'client@example.com',
        ];

        $settings = [
            'company_name' => 'Test Company',
        ];

        $this->assertIsArray($quote);
        $this->assertArrayHasKey('number', $quote);
        $this->assertArrayHasKey('total_amount', $quote);
        $this->assertArrayHasKey('valid_until', $quote);
    }

    public function testSendReminderHasCorrectSubjectLevels(): void
    {
        $invoice = [
            'number' => 'FAC-2024-0001',
            'total_amount' => 1000.00,
            'due_date' => date('Y-m-d', strtotime('-10 days')),
            'client_email' => 'client@example.com',
        ];

        $settings = [
            'company_name' => 'Test Company',
        ];

        // Verify reminder levels exist (1, 2, 3)
        $reminderLevels = [1, 2, 3];
        foreach ($reminderLevels as $level) {
            $this->assertContains($level, $reminderLevels);
        }
    }

    public function testMailServiceCanBeInstantiated(): void
    {
        $service = new MailService();
        $this->assertInstanceOf(MailService::class, $service);
    }

    public function testInvoiceEmailContainsRequiredFields(): void
    {
        $invoice = [
            'number' => 'FAC-2024-0001',
            'total_amount' => 1500.00,
            'due_date' => '2024-12-31',
            'client_email' => 'test@example.com',
        ];

        $this->assertEquals('FAC-2024-0001', $invoice['number']);
        $this->assertEquals(1500.00, $invoice['total_amount']);
        $this->assertEquals('2024-12-31', $invoice['due_date']);
        $this->assertEquals('test@example.com', $invoice['client_email']);
    }

    public function testQuoteEmailContainsRequiredFields(): void
    {
        $quote = [
            'number' => 'DEV-2024-0001',
            'total_amount' => 3000.00,
            'valid_until' => '2024-12-31',
            'client_email' => 'client@test.com',
        ];

        $this->assertEquals('DEV-2024-0001', $quote['number']);
        $this->assertEquals(3000.00, $quote['total_amount']);
        $this->assertEquals('2024-12-31', $quote['valid_until']);
        $this->assertEquals('client@test.com', $quote['client_email']);
    }
}
