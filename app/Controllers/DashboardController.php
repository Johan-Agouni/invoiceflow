<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Client;

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
        $monthlyRevenue = Invoice::getMonthlyRevenue($userId, 12);

        // Format chart data
        $chartLabels = [];
        $chartData = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $chartLabels[] = date('M Y', strtotime($month . '-01'));
            $chartData[] = 0;
        }

        foreach ($monthlyRevenue as $row) {
            $index = array_search(date('M Y', strtotime($row['month'] . '-01')), $chartLabels);
            if ($index !== false) {
                $chartData[$index] = (float) $row['revenue'];
            }
        }

        $this->view('dashboard.index', [
            'invoiceStats' => $invoiceStats,
            'quoteStats' => $quoteStats,
            'clientCount' => $clientCount,
            'recentInvoices' => $recentInvoices,
            'overdueInvoices' => $overdueInvoices,
            'chartLabels' => json_encode($chartLabels),
            'chartData' => json_encode($chartData),
            'flash' => $this->getFlash(),
        ]);
    }
}
