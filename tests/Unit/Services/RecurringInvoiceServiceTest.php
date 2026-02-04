<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RecurringInvoiceService;

class RecurringInvoiceServiceTest extends TestCase
{
    private RecurringInvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RecurringInvoiceService();
    }

    public function testCalculateNextInvoiceDateMonthly(): void
    {
        $nextDate = $this->service->calculateNextInvoiceDate(
            '2024-01-15',
            RecurringInvoiceService::FREQUENCY_MONTHLY,
            1
        );

        $this->assertEquals('2024-02-01', $nextDate);
    }

    public function testCalculateNextInvoiceDateMonthlyWithDay15(): void
    {
        $nextDate = $this->service->calculateNextInvoiceDate(
            '2024-01-01',
            RecurringInvoiceService::FREQUENCY_MONTHLY,
            15
        );

        $this->assertEquals('2024-02-15', $nextDate);
    }

    public function testCalculateNextInvoiceDateMonthlyEndOfMonth(): void
    {
        // Février n'a que 28/29 jours
        $nextDate = $this->service->calculateNextInvoiceDate(
            '2024-01-31',
            RecurringInvoiceService::FREQUENCY_MONTHLY,
            31
        );

        // Devrait être le dernier jour de février
        $this->assertEquals('2024-02-29', $nextDate); // 2024 est bissextile
    }

    public function testCalculateNextInvoiceDateWeekly(): void
    {
        // 2024-01-01 est un lundi
        $nextDate = $this->service->calculateNextInvoiceDate(
            '2024-01-01',
            RecurringInvoiceService::FREQUENCY_WEEKLY,
            null,
            1 // Lundi
        );

        $this->assertEquals('2024-01-08', $nextDate);
    }

    public function testCalculateNextInvoiceDateQuarterly(): void
    {
        $nextDate = $this->service->calculateNextInvoiceDate(
            '2024-01-15',
            RecurringInvoiceService::FREQUENCY_QUARTERLY,
            1
        );

        $this->assertEquals('2024-04-01', $nextDate);
    }

    public function testCalculateNextInvoiceDateYearly(): void
    {
        $nextDate = $this->service->calculateNextInvoiceDate(
            '2024-01-15',
            RecurringInvoiceService::FREQUENCY_YEARLY,
            15
        );

        $this->assertEquals('2025-01-15', $nextDate);
    }

    public function testCreateRecurringInvoice(): void
    {
        $user = $this->createUser();
        $client = $this->createClient($user['id']);

        $data = [
            'user_id' => $user['id'],
            'client_id' => $client['id'],
            'name' => 'Abonnement mensuel',
            'frequency' => RecurringInvoiceService::FREQUENCY_MONTHLY,
            'day_of_month' => 1,
            'start_date' => date('Y-m-d'),
            'notes' => 'Facture récurrente de test',
            'auto_send' => false,
        ];

        $items = [
            [
                'description' => 'Abonnement mensuel',
                'quantity' => 1,
                'unit_price' => 100.00,
                'vat_rate' => 20,
            ],
        ];

        $recurringId = $this->service->create($data, $items);

        $this->assertGreaterThan(0, $recurringId);
        $this->assertDatabaseHas('recurring_invoices', [
            'id' => $recurringId,
            'user_id' => $user['id'],
            'client_id' => $client['id'],
            'name' => 'Abonnement mensuel',
            'status' => 'active',
        ]);
    }

    public function testFindRecurringInvoice(): void
    {
        $user = $this->createUser();
        $client = $this->createClient($user['id']);

        $data = [
            'user_id' => $user['id'],
            'client_id' => $client['id'],
            'name' => 'Test Recurring',
            'frequency' => RecurringInvoiceService::FREQUENCY_MONTHLY,
            'start_date' => date('Y-m-d'),
        ];

        $recurringId = $this->service->create($data, [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 50, 'vat_rate' => 20],
        ]);

        $found = $this->service->find($recurringId, $user['id']);

        $this->assertNotNull($found);
        $this->assertEquals('Test Recurring', $found['name']);
        $this->assertEquals($client['company_name'], $found['client_name']);
    }

    public function testFindReturnsNullForWrongUser(): void
    {
        $user1 = $this->createUser();
        $user2 = $this->createUser();
        $client = $this->createClient($user1['id']);

        $recurringId = $this->service->create([
            'user_id' => $user1['id'],
            'client_id' => $client['id'],
            'name' => 'Test',
            'frequency' => RecurringInvoiceService::FREQUENCY_MONTHLY,
            'start_date' => date('Y-m-d'),
        ], [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 50, 'vat_rate' => 20],
        ]);

        $found = $this->service->find($recurringId, $user2['id']);

        $this->assertNull($found);
    }

    public function testPauseRecurringInvoice(): void
    {
        $user = $this->createUser();
        $client = $this->createClient($user['id']);

        $recurringId = $this->service->create([
            'user_id' => $user['id'],
            'client_id' => $client['id'],
            'name' => 'Test',
            'frequency' => RecurringInvoiceService::FREQUENCY_MONTHLY,
            'start_date' => date('Y-m-d'),
        ], [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 50, 'vat_rate' => 20],
        ]);

        $result = $this->service->pause($recurringId);

        $this->assertTrue($result);
        $this->assertDatabaseHas('recurring_invoices', [
            'id' => $recurringId,
            'status' => 'paused',
        ]);
    }

    public function testResumeRecurringInvoice(): void
    {
        $user = $this->createUser();
        $client = $this->createClient($user['id']);

        $recurringId = $this->service->create([
            'user_id' => $user['id'],
            'client_id' => $client['id'],
            'name' => 'Test',
            'frequency' => RecurringInvoiceService::FREQUENCY_MONTHLY,
            'start_date' => date('Y-m-d'),
        ], [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 50, 'vat_rate' => 20],
        ]);

        $this->service->pause($recurringId);
        $result = $this->service->resume($recurringId);

        $this->assertTrue($result);
        $this->assertDatabaseHas('recurring_invoices', [
            'id' => $recurringId,
            'status' => 'active',
        ]);
    }

    public function testCancelRecurringInvoice(): void
    {
        $user = $this->createUser();
        $client = $this->createClient($user['id']);

        $recurringId = $this->service->create([
            'user_id' => $user['id'],
            'client_id' => $client['id'],
            'name' => 'Test',
            'frequency' => RecurringInvoiceService::FREQUENCY_MONTHLY,
            'start_date' => date('Y-m-d'),
        ], [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 50, 'vat_rate' => 20],
        ]);

        $result = $this->service->cancel($recurringId);

        $this->assertTrue($result);
        $this->assertDatabaseHas('recurring_invoices', [
            'id' => $recurringId,
            'status' => 'cancelled',
        ]);
    }

    public function testGetAllForUser(): void
    {
        $user = $this->createUser();
        $client = $this->createClient($user['id']);

        $this->service->create([
            'user_id' => $user['id'],
            'client_id' => $client['id'],
            'name' => 'Recurring 1',
            'frequency' => RecurringInvoiceService::FREQUENCY_MONTHLY,
            'start_date' => date('Y-m-d'),
        ], [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 50, 'vat_rate' => 20],
        ]);

        $this->service->create([
            'user_id' => $user['id'],
            'client_id' => $client['id'],
            'name' => 'Recurring 2',
            'frequency' => RecurringInvoiceService::FREQUENCY_YEARLY,
            'start_date' => date('Y-m-d'),
        ], [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 20],
        ]);

        $all = $this->service->getAllForUser($user['id']);

        $this->assertCount(2, $all);
    }

    public function testGetAllForUserWithStatusFilter(): void
    {
        $user = $this->createUser();
        $client = $this->createClient($user['id']);

        $recurring1 = $this->service->create([
            'user_id' => $user['id'],
            'client_id' => $client['id'],
            'name' => 'Active',
            'frequency' => RecurringInvoiceService::FREQUENCY_MONTHLY,
            'start_date' => date('Y-m-d'),
        ], [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 50, 'vat_rate' => 20],
        ]);

        $recurring2 = $this->service->create([
            'user_id' => $user['id'],
            'client_id' => $client['id'],
            'name' => 'Paused',
            'frequency' => RecurringInvoiceService::FREQUENCY_MONTHLY,
            'start_date' => date('Y-m-d'),
        ], [
            ['description' => 'Test', 'quantity' => 1, 'unit_price' => 50, 'vat_rate' => 20],
        ]);

        $this->service->pause($recurring2);

        $active = $this->service->getAllForUser($user['id'], 'active');

        $this->assertCount(1, $active);
        $this->assertEquals('Active', $active[0]['name']);
    }

    public function testGetItems(): void
    {
        $user = $this->createUser();
        $client = $this->createClient($user['id']);

        $recurringId = $this->service->create([
            'user_id' => $user['id'],
            'client_id' => $client['id'],
            'name' => 'Test',
            'frequency' => RecurringInvoiceService::FREQUENCY_MONTHLY,
            'start_date' => date('Y-m-d'),
        ], [
            ['description' => 'Item 1', 'quantity' => 1, 'unit_price' => 50, 'vat_rate' => 20],
            ['description' => 'Item 2', 'quantity' => 2, 'unit_price' => 25, 'vat_rate' => 20],
        ]);

        $items = $this->service->getItems($recurringId);

        $this->assertCount(2, $items);
        $this->assertEquals('Item 1', $items[0]['description']);
        $this->assertEquals('Item 2', $items[1]['description']);
    }
}
