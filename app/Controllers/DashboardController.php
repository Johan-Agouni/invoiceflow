<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Database;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quote;

class DashboardController extends Controller
{
    public function index(): void
    {
        $userId = $this->userId();

        // Update overdue invoices
        Invoice::updateOverdueStatus();

        // Get statistics
        $invoiceStats = Invoice::getStats($userId);
        $quoteStats = Quote::getStats($userId);
        $clientCount = Client::countForUser($userId);

        // Get recent data
        $recentInvoices = array_slice(Invoice::allForUser($userId), 0, 5);
        $overdueInvoices = Invoice::getOverdue($userId);

        // Monthly revenue (12 months)
        $monthlyRevenue = Invoice::getMonthlyRevenue($userId, 12);

        // Get trend data
        $trendData = $this->calculateTrends($userId);

        // Get comparison with previous period
        $periodComparison = $this->getPeriodComparison($userId);

        // Get invoice status distribution
        $statusDistribution = $this->getStatusDistribution($userId);

        // Get top clients
        $topClients = $this->getTopClients($userId, 5);

        // Format chart data - Revenue
        $chartLabels = [];
        $chartData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $chartLabels[] = date('M Y', strtotime($month . '-01'));
            $chartData[] = 0;
        }

        foreach ($monthlyRevenue as $row) {
            $index = array_search(date('M Y', strtotime($row['month'] . '-01')), $chartLabels, true);
            if ($index !== false) {
                $chartData[$index] = (float) $row['revenue'];
            }
        }

        // Calculate growth rate
        $currentMonth = end($chartData);
        $previousMonth = prev($chartData) ?: 0;
        $growthRate = $previousMonth > 0 ? (($currentMonth - $previousMonth) / $previousMonth) * 100 : 0;

        $this->view('dashboard.index', [
            'invoiceStats' => $invoiceStats,
            'quoteStats' => $quoteStats,
            'clientCount' => $clientCount,
            'recentInvoices' => $recentInvoices,
            'overdueInvoices' => $overdueInvoices,
            'chartLabels' => json_encode($chartLabels),
            'chartData' => json_encode($chartData),
            'trendData' => $trendData,
            'periodComparison' => $periodComparison,
            'statusDistribution' => json_encode($statusDistribution),
            'topClients' => $topClients,
            'growthRate' => round($growthRate, 1),
            'flash' => $this->getFlash(),
        ]);
    }

    /**
     * Calculate trend data for the dashboard
     */
    private function calculateTrends(int $userId): array
    {
        // Get invoice count by month
        $invoiceTrend = Database::fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
             FROM invoices WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month ASC",
            [$userId]
        );

        // Get quote count by month
        $quoteTrend = Database::fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count
             FROM quotes WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month ASC",
            [$userId]
        );

        // Get paid invoices by month
        $paidTrend = Database::fetchAll(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') as month,
                    COUNT(*) as count,
                    SUM(total_amount) as amount
             FROM invoices
             WHERE user_id = ? AND status = 'paid' AND paid_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
             ORDER BY month ASC",
            [$userId]
        );

        // Format for chart
        $labels = [];
        $invoiceCounts = [];
        $quoteCounts = [];
        $paidAmounts = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $labels[] = date('M', strtotime($month . '-01'));
            $invoiceCounts[$month] = 0;
            $quoteCounts[$month] = 0;
            $paidAmounts[$month] = 0;
        }

        foreach ($invoiceTrend as $row) {
            if (isset($invoiceCounts[$row['month']])) {
                $invoiceCounts[$row['month']] = (int) $row['count'];
            }
        }

        foreach ($quoteTrend as $row) {
            if (isset($quoteCounts[$row['month']])) {
                $quoteCounts[$row['month']] = (int) $row['count'];
            }
        }

        foreach ($paidTrend as $row) {
            if (isset($paidAmounts[$row['month']])) {
                $paidAmounts[$row['month']] = (float) $row['amount'];
            }
        }

        return [
            'labels' => json_encode($labels),
            'invoices' => json_encode(array_values($invoiceCounts)),
            'quotes' => json_encode(array_values($quoteCounts)),
            'paid' => json_encode(array_values($paidAmounts)),
        ];
    }

    /**
     * Compare current period with previous period
     */
    private function getPeriodComparison(int $userId): array
    {
        // Current month
        $currentMonth = Database::fetch(
            "SELECT COUNT(*) as invoice_count,
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_amount,
                    COALESCE(SUM(total_amount), 0) as total_amount
             FROM invoices
             WHERE user_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')",
            [$userId]
        );

        // Previous month
        $previousMonth = Database::fetch(
            "SELECT COUNT(*) as invoice_count,
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_amount,
                    COALESCE(SUM(total_amount), 0) as total_amount
             FROM invoices
             WHERE user_id = ? AND DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 1 MONTH), '%Y-%m')",
            [$userId]
        );

        // Current year
        $currentYear = Database::fetch(
            "SELECT COUNT(*) as invoice_count,
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_amount,
                    COALESCE(SUM(total_amount), 0) as total_amount
             FROM invoices
             WHERE user_id = ? AND YEAR(created_at) = YEAR(NOW())",
            [$userId]
        );

        // Previous year
        $previousYear = Database::fetch(
            "SELECT COUNT(*) as invoice_count,
                    COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_amount,
                    COALESCE(SUM(total_amount), 0) as total_amount
             FROM invoices
             WHERE user_id = ? AND YEAR(created_at) = YEAR(NOW()) - 1",
            [$userId]
        );

        // Calculate percentage changes
        $monthChange = $this->calculateChange(
            (float) ($previousMonth['paid_amount'] ?? 0),
            (float) ($currentMonth['paid_amount'] ?? 0)
        );

        $yearChange = $this->calculateChange(
            (float) ($previousYear['paid_amount'] ?? 0),
            (float) ($currentYear['paid_amount'] ?? 0)
        );

        return [
            'current_month' => [
                'invoices' => (int) ($currentMonth['invoice_count'] ?? 0),
                'paid' => (float) ($currentMonth['paid_amount'] ?? 0),
                'total' => (float) ($currentMonth['total_amount'] ?? 0),
            ],
            'previous_month' => [
                'invoices' => (int) ($previousMonth['invoice_count'] ?? 0),
                'paid' => (float) ($previousMonth['paid_amount'] ?? 0),
                'total' => (float) ($previousMonth['total_amount'] ?? 0),
            ],
            'current_year' => [
                'invoices' => (int) ($currentYear['invoice_count'] ?? 0),
                'paid' => (float) ($currentYear['paid_amount'] ?? 0),
                'total' => (float) ($currentYear['total_amount'] ?? 0),
            ],
            'previous_year' => [
                'invoices' => (int) ($previousYear['invoice_count'] ?? 0),
                'paid' => (float) ($previousYear['paid_amount'] ?? 0),
                'total' => (float) ($previousYear['total_amount'] ?? 0),
            ],
            'month_change' => $monthChange,
            'year_change' => $yearChange,
        ];
    }

    /**
     * Calculate percentage change
     */
    private function calculateChange(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get invoice status distribution for pie chart
     */
    private function getStatusDistribution(int $userId): array
    {
        $distribution = Database::fetchAll(
            'SELECT status, COUNT(*) as count, COALESCE(SUM(total_amount), 0) as amount
             FROM invoices
             WHERE user_id = ?
             GROUP BY status',
            [$userId]
        );

        $result = [
            'labels' => [],
            'counts' => [],
            'amounts' => [],
            'colors' => [],
        ];

        $statusColors = [
            'draft' => '#94a3b8',
            'pending' => '#f59e0b',
            'paid' => '#10b981',
            'overdue' => '#ef4444',
            'cancelled' => '#6b7280',
        ];

        $statusLabels = [
            'draft' => 'Brouillon',
            'pending' => 'En attente',
            'paid' => 'Payee',
            'overdue' => 'En retard',
            'cancelled' => 'Annulee',
        ];

        foreach ($distribution as $row) {
            $status = $row['status'];
            $result['labels'][] = $statusLabels[$status] ?? ucfirst($status);
            $result['counts'][] = (int) $row['count'];
            $result['amounts'][] = (float) $row['amount'];
            $result['colors'][] = $statusColors[$status] ?? '#64748b';
        }

        return $result;
    }

    /**
     * Get top clients by revenue
     */
    private function getTopClients(int $userId, int $limit = 5): array
    {
        return Database::fetchAll(
            "SELECT c.id, c.company_name,
                    COUNT(i.id) as invoice_count,
                    COALESCE(SUM(i.total_amount), 0) as total_billed,
                    COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total_amount ELSE 0 END), 0) as total_paid
             FROM clients c
             LEFT JOIN invoices i ON c.id = i.client_id
             WHERE c.user_id = ?
             GROUP BY c.id, c.company_name
             ORDER BY total_paid DESC
             LIMIT ?",
            [$userId, $limit]
        );
    }
}
