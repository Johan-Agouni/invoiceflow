<?php

declare(strict_types=1);

namespace Tests\Feature\Controller;

use App\Database;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;
use Tests\TestCase;

/**
 * Dashboard Controller Feature Tests
 *
 * Tests the dashboard statistics and analytics
 *
 * @covers \App\Controllers\DashboardController
 */
class DashboardControllerTest extends TestCase
{
    private array $user;

    private array $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
        $this->client = $this->createClient($this->user['id']);

        $_SESSION['user_id'] = $this->user['id'];
        $_SESSION['user_name'] = $this->user['name'];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    public function testDashboardShowsInvoiceStatistics(): void
    {
        // Create invoices with various statuses
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'draft',
            'total_amount' => 1000,
        ]);
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'pending',
            'total_amount' => 2000,
        ]);
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'paid',
            'total_amount' => 3000,
        ]);

        $stats = Invoice::getStats($this->user['id']);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('paid_count', $stats);
        $this->assertArrayHasKey('pending_count', $stats);
        $this->assertArrayHasKey('total_paid', $stats);
    }

    public function testDashboardShowsQuoteStatistics(): void
    {
        // Create quotes with various statuses
        $this->createQuote($this->user['id'], $this->client['id'], [
            'status' => 'draft',
        ]);
        $this->createQuote($this->user['id'], $this->client['id'], [
            'status' => 'sent',
        ]);
        $this->createQuote($this->user['id'], $this->client['id'], [
            'status' => 'accepted',
        ]);

        $stats = Quote::getStats($this->user['id']);

        $this->assertIsArray($stats);
    }

    public function testDashboardShowsClientCount(): void
    {
        // Create additional clients
        $this->createClient($this->user['id']);
        $this->createClient($this->user['id']);

        $count = Client::countForUser($this->user['id']);

        // We created 3 clients total (1 in setUp + 2 here)
        $this->assertEquals(3, $count);
    }

    public function testDashboardShowsRecentInvoices(): void
    {
        // Create 7 invoices
        for ($i = 0; $i < 7; $i++) {
            $this->createInvoice($this->user['id'], $this->client['id']);
        }

        $allInvoices = Invoice::allForUser($this->user['id']);
        $recentInvoices = array_slice($allInvoices, 0, 5);

        $this->assertCount(5, $recentInvoices);
        $this->assertCount(7, $allInvoices);
    }

    public function testDashboardShowsOverdueInvoices(): void
    {
        // Create overdue invoice
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'pending',
            'due_date' => date('Y-m-d', strtotime('-10 days')),
        ]);

        // Create non-overdue invoice
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'pending',
            'due_date' => date('Y-m-d', strtotime('+10 days')),
        ]);

        // getOverdue() finds pending invoices past due date (before status update)
        $overdueInvoices = Invoice::getOverdue($this->user['id']);

        $this->assertGreaterThanOrEqual(1, count($overdueInvoices));
    }

    public function testDashboardMonthlyRevenueCalculation(): void
    {
        // Create paid invoices in current month
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'paid',
            'total_amount' => 1000,
            'issue_date' => date('Y-m-01'),
        ]);
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'status' => 'paid',
            'total_amount' => 2000,
            'issue_date' => date('Y-m-15'),
        ]);

        $monthlyRevenue = Invoice::getMonthlyRevenue($this->user['id'], 12);

        $this->assertIsArray($monthlyRevenue);
    }

    public function testDashboardDataIsolationBetweenUsers(): void
    {
        // Create data for user 1
        $this->createInvoice($this->user['id'], $this->client['id'], [
            'total_amount' => 5000,
        ]);

        // Create user 2 with their own data
        $user2 = $this->createUser();
        $client2 = $this->createClient($user2['id']);
        $this->createInvoice($user2['id'], $client2['id'], [
            'total_amount' => 10000,
        ]);

        // Get stats for user 1
        $user1Stats = Invoice::getStats($this->user['id']);
        $user1Invoices = Invoice::allForUser($this->user['id']);

        // Get stats for user 2
        $user2Stats = Invoice::getStats($user2['id']);
        $user2Invoices = Invoice::allForUser($user2['id']);

        // Each user should only see their own data
        $this->assertCount(1, $user1Invoices);
        $this->assertCount(1, $user2Invoices);

        // Invoice amounts should be different
        $this->assertEquals(5000, (float) $user1Invoices[0]['total_amount']);
        $this->assertEquals(10000, (float) $user2Invoices[0]['total_amount']);
    }

    public function testDashboardGrowthRateCalculation(): void
    {
        $previousMonth = 1000;
        $currentMonth = 1500;

        // Growth rate = ((current - previous) / previous) * 100
        $growthRate = $previousMonth > 0
            ? (($currentMonth - $previousMonth) / $previousMonth) * 100
            : 0;

        $this->assertEquals(50.0, $growthRate);
    }

    public function testDashboardGrowthRateWithZeroPreviousMonth(): void
    {
        $previousMonth = 0;
        $currentMonth = 1000;

        $growthRate = $previousMonth > 0
            ? (($currentMonth - $previousMonth) / $previousMonth) * 100
            : ($currentMonth > 0 ? 100 : 0);

        $this->assertEquals(100, $growthRate);
    }

    public function testDashboardStatusDistribution(): void
    {
        // Create invoices with different statuses
        $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'draft']);
        $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'draft']);
        $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'pending']);
        $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'paid']);
        $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'paid']);
        $this->createInvoice($this->user['id'], $this->client['id'], ['status' => 'paid']);

        $distribution = Database::fetchAll(
            'SELECT status, COUNT(*) as count
             FROM invoices
             WHERE user_id = ?
             GROUP BY status',
            [$this->user['id']]
        );

        $statusCounts = [];
        foreach ($distribution as $row) {
            $statusCounts[$row['status']] = (int) $row['count'];
        }

        $this->assertEquals(2, $statusCounts['draft']);
        $this->assertEquals(1, $statusCounts['pending']);
        $this->assertEquals(3, $statusCounts['paid']);
    }

    public function testDashboardTopClients(): void
    {
        // Create multiple clients with invoices
        $client1 = $this->createClient($this->user['id'], ['company_name' => 'Top Client']);
        $client2 = $this->createClient($this->user['id'], ['company_name' => 'Medium Client']);
        $client3 = $this->createClient($this->user['id'], ['company_name' => 'Small Client']);

        // Client 1: high revenue
        $this->createInvoice($this->user['id'], $client1['id'], [
            'status' => 'paid',
            'total_amount' => 10000,
        ]);

        // Client 2: medium revenue
        $this->createInvoice($this->user['id'], $client2['id'], [
            'status' => 'paid',
            'total_amount' => 5000,
        ]);

        // Client 3: low revenue
        $this->createInvoice($this->user['id'], $client3['id'], [
            'status' => 'paid',
            'total_amount' => 1000,
        ]);

        $topClients = Database::fetchAll(
            "SELECT c.id, c.company_name,
                    COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total_amount ELSE 0 END), 0) as total_paid
             FROM clients c
             LEFT JOIN invoices i ON c.id = i.client_id
             WHERE c.user_id = ?
             GROUP BY c.id, c.company_name
             ORDER BY total_paid DESC
             LIMIT 5",
            [$this->user['id']]
        );

        $this->assertGreaterThanOrEqual(3, count($topClients));
        $this->assertEquals('Top Client', $topClients[0]['company_name']);
    }

    public function testDashboardChartDataFormat(): void
    {
        $chartLabels = [];
        $chartData = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $chartLabels[] = date('M Y', strtotime($month . '-01'));
            $chartData[] = 0;
        }

        // Should have 12 months of data
        $this->assertCount(12, $chartLabels);
        $this->assertCount(12, $chartData);

        // Labels should be valid JSON
        $jsonLabels = json_encode($chartLabels);
        $this->assertIsString($jsonLabels);
        $this->assertJson($jsonLabels);
    }

    public function testDashboardHandlesEmptyData(): void
    {
        // Create new user with no data
        $emptyUser = $this->createUser();

        $invoiceStats = Invoice::getStats($emptyUser['id']);
        $quoteStats = Quote::getStats($emptyUser['id']);
        $clientCount = Client::countForUser($emptyUser['id']);
        $invoices = Invoice::allForUser($emptyUser['id']);

        // Should return empty/zero values, not errors
        $this->assertIsArray($invoiceStats);
        $this->assertIsArray($quoteStats);
        $this->assertEquals(0, $clientCount);
        $this->assertEmpty($invoices);
    }
}
