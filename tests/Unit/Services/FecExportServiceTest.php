<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\FecExportService;
use App\Database;

class FecExportServiceTest extends TestCase
{
    private int $userId;

    protected function setUp(): void
    {
        parent::setUp();

        $user = $this->createUser();
        $this->userId = $user['id'];

        // Créer les settings avec SIRET
        Database::query(
            "INSERT INTO settings (user_id, company_name, company_siret) VALUES (?, ?, ?)",
            [$this->userId, 'Test Company', '12345678901234']
        );
    }

    public function testValidateWithoutSiretReturnsError(): void
    {
        $user = $this->createUser();
        Database::query(
            "INSERT INTO settings (user_id, company_name) VALUES (?, ?)",
            [$user['id'], 'No SIRET Company']
        );

        $service = new FecExportService($user['id']);
        $errors = $service->validate('2024-01-01', '2024-12-31');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('SIRET', $errors[0]);
    }

    public function testValidateWithNoInvoicesReturnsError(): void
    {
        $service = new FecExportService($this->userId);
        $errors = $service->validate('2024-01-01', '2024-12-31');

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Aucune facture', $errors[0]);
    }

    public function testValidateWithValidDataReturnsNoErrors(): void
    {
        $client = $this->createClient($this->userId);
        $this->createInvoice($this->userId, $client['id'], [
            'status' => 'pending',
            'issue_date' => '2024-06-15',
        ]);

        $service = new FecExportService($this->userId);
        $errors = $service->validate('2024-01-01', '2024-12-31');

        $this->assertEmpty($errors);
    }

    public function testGenerateReturnsValidFecFormat(): void
    {
        $client = $this->createClient($this->userId);
        $this->createInvoice($this->userId, $client['id'], [
            'status' => 'pending',
            'issue_date' => '2024-06-15',
            'subtotal' => 1000.00,
            'vat_amount' => 200.00,
            'total_amount' => 1200.00,
        ]);

        $service = new FecExportService($this->userId);
        $content = $service->generate('2024-01-01', '2024-12-31');

        $this->assertNotEmpty($content);

        // Vérifier l'en-tête
        $lines = explode("\r\n", $content);
        $header = $lines[0];

        $this->assertStringContainsString('JournalCode', $header);
        $this->assertStringContainsString('EcritureNum', $header);
        $this->assertStringContainsString('CompteNum', $header);
        $this->assertStringContainsString('Debit', $header);
        $this->assertStringContainsString('Credit', $header);
    }

    public function testGenerateIncludesAllRequiredColumns(): void
    {
        $client = $this->createClient($this->userId);
        $this->createInvoice($this->userId, $client['id'], [
            'status' => 'pending',
            'issue_date' => '2024-06-15',
        ]);

        $service = new FecExportService($this->userId);
        $content = $service->generate('2024-01-01', '2024-12-31');

        $lines = explode("\r\n", $content);
        $header = explode("\t", $lines[0]);

        // FEC exige 18 colonnes
        $this->assertCount(18, $header);

        $requiredColumns = [
            'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate',
            'CompteNum', 'CompteLib', 'CompAuxNum', 'CompAuxLib',
            'PieceRef', 'PieceDate', 'EcritureLib', 'Debit', 'Credit',
            'EcritureLet', 'DateLet', 'ValidDate', 'Montantdevise', 'Idevise',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertContains($column, $header);
        }
    }

    public function testGenerateCreatesBalancedEntries(): void
    {
        $client = $this->createClient($this->userId);
        $this->createInvoice($this->userId, $client['id'], [
            'status' => 'pending',
            'issue_date' => '2024-06-15',
            'subtotal' => 1000.00,
            'vat_amount' => 200.00,
            'total_amount' => 1200.00,
        ]);

        $service = new FecExportService($this->userId);
        $content = $service->generate('2024-01-01', '2024-12-31');

        $lines = explode("\r\n", $content);
        array_shift($lines); // Supprimer l'en-tête

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $columns = explode("\t", $line);
            $debit = (float) str_replace(',', '.', $columns[11] ?? '0');
            $credit = (float) str_replace(',', '.', $columns[12] ?? '0');
            $totalDebit += $debit;
            $totalCredit += $credit;
        }

        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
    }

    public function testGenerateExcludesDraftInvoices(): void
    {
        $client = $this->createClient($this->userId);
        $this->createInvoice($this->userId, $client['id'], [
            'status' => 'draft',
            'issue_date' => '2024-06-15',
        ]);

        $service = new FecExportService($this->userId);

        $this->expectException(\RuntimeException::class);
        $service->generate('2024-01-01', '2024-12-31');
    }

    public function testGenerateIncludesPaymentEntriesForPaidInvoices(): void
    {
        $client = $this->createClient($this->userId);
        $this->createInvoice($this->userId, $client['id'], [
            'status' => 'paid',
            'issue_date' => '2024-06-15',
            'paid_at' => '2024-06-20 10:00:00',
            'subtotal' => 1000.00,
            'vat_amount' => 200.00,
            'total_amount' => 1200.00,
        ]);

        $service = new FecExportService($this->userId);
        $content = $service->generate('2024-01-01', '2024-12-31');

        // Vérifier la présence des journaux VE (Ventes) et BQ (Banque)
        $this->assertStringContainsString("\tVE\t", $content);
        $this->assertStringContainsString("\tBQ\t", $content);
    }

    public function testGenerateUsesCorrectVatAccount(): void
    {
        $client = $this->createClient($this->userId);
        $this->createInvoice($this->userId, $client['id'], [
            'status' => 'pending',
            'issue_date' => '2024-06-15',
            'subtotal' => 1000.00,
            'vat_amount' => 200.00, // 20%
            'total_amount' => 1200.00,
        ]);

        $service = new FecExportService($this->userId);
        $content = $service->generate('2024-01-01', '2024-12-31');

        // Compte TVA collectée 20%
        $this->assertStringContainsString('445710', $content);
    }

    public function testAmountFormatUsesCommaAsDecimalSeparator(): void
    {
        $client = $this->createClient($this->userId);
        $this->createInvoice($this->userId, $client['id'], [
            'status' => 'pending',
            'issue_date' => '2024-06-15',
            'subtotal' => 1234.56,
            'vat_amount' => 246.91,
            'total_amount' => 1481.47,
        ]);

        $service = new FecExportService($this->userId);
        $content = $service->generate('2024-01-01', '2024-12-31');

        $this->assertStringContainsString('1481,47', $content);
        $this->assertStringContainsString('1234,56', $content);
    }
}
