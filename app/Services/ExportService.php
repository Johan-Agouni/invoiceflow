<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;

/**
 * Export Service
 *
 * Handles export of invoices to various formats (CSV, Excel)
 * for accounting and reporting purposes.
 */
class ExportService
{
    /**
     * Export invoices to CSV format
     *
     * @param array $invoices List of invoices to export
     * @param string $locale Language for headers (fr|en)
     *
     * @return string CSV content
     */
    public function toCsv(array $invoices, string $locale = 'fr'): string
    {
        $headers = $this->getHeaders($locale);

        $output = fopen('php://temp', 'r+');

        // Add BOM for Excel UTF-8 compatibility
        fwrite($output, "\xEF\xBB\xBF");

        // Write headers
        fputcsv($output, $headers, ';');

        // Write data rows
        foreach ($invoices as $invoice) {
            $client = Client::find($invoice['client_id']);
            $items = InvoiceItem::getForInvoice($invoice['id']);

            foreach ($items as $item) {
                $row = $this->formatRow($invoice, $client, $item, $locale);
                fputcsv($output, $row, ';');
            }
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    /**
     * Export invoices to Excel format (XLSX via CSV with Excel-specific formatting)
     *
     * @param array $invoices List of invoices to export
     * @param string $locale Language for headers (fr|en)
     *
     * @return string Excel XML content (SpreadsheetML)
     */
    public function toExcel(array $invoices, string $locale = 'fr'): string
    {
        $headers = $this->getHeaders($locale);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?mso-application progid="Excel.Sheet"?>' . "\n";
        $xml .= '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
            xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";

        // Styles
        $xml .= '<Styles>
            <Style ss:ID="Header">
                <Font ss:Bold="1" ss:Size="11"/>
                <Interior ss:Color="#1E293B" ss:Pattern="Solid"/>
                <Font ss:Color="#FFFFFF"/>
            </Style>
            <Style ss:ID="Currency">
                <NumberFormat ss:Format="#,##0.00\ &quot;€&quot;"/>
            </Style>
            <Style ss:ID="Date">
                <NumberFormat ss:Format="yyyy-mm-dd"/>
            </Style>
            <Style ss:ID="Percent">
                <NumberFormat ss:Format="0.00%"/>
            </Style>
        </Styles>' . "\n";

        $xml .= '<Worksheet ss:Name="Factures">' . "\n";
        $xml .= '<Table>' . "\n";

        // Column widths
        $xml .= '<Column ss:Width="100"/>' . "\n"; // Invoice number
        $xml .= '<Column ss:Width="80"/>' . "\n";  // Date
        $xml .= '<Column ss:Width="80"/>' . "\n";  // Due date
        $xml .= '<Column ss:Width="80"/>' . "\n";  // Status
        $xml .= '<Column ss:Width="150"/>' . "\n"; // Client
        $xml .= '<Column ss:Width="200"/>' . "\n"; // Description
        $xml .= '<Column ss:Width="60"/>' . "\n";  // Quantity
        $xml .= '<Column ss:Width="80"/>' . "\n";  // Unit price
        $xml .= '<Column ss:Width="60"/>' . "\n";  // VAT rate
        $xml .= '<Column ss:Width="80"/>' . "\n";  // VAT amount
        $xml .= '<Column ss:Width="100"/>' . "\n"; // Total HT
        $xml .= '<Column ss:Width="100"/>' . "\n"; // Total TTC

        // Headers
        $xml .= '<Row ss:StyleID="Header">' . "\n";
        foreach ($headers as $header) {
            $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
        }
        $xml .= '</Row>' . "\n";

        // Data rows
        foreach ($invoices as $invoice) {
            $client = Client::find($invoice['client_id']);
            $items = InvoiceItem::getForInvoice($invoice['id']);

            foreach ($items as $item) {
                $xml .= '<Row>' . "\n";
                $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($invoice['number']) . '</Data></Cell>' . "\n";
                $xml .= '<Cell ss:StyleID="Date"><Data ss:Type="String">' . $invoice['issue_date'] . '</Data></Cell>' . "\n";
                $xml .= '<Cell ss:StyleID="Date"><Data ss:Type="String">' . $invoice['due_date'] . '</Data></Cell>' . "\n";
                $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($this->translateStatus($invoice['status'], $locale)) . '</Data></Cell>' . "\n";
                $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($client['company_name'] ?? '') . '</Data></Cell>' . "\n";
                $xml .= '<Cell><Data ss:Type="String">' . htmlspecialchars($item['description']) . '</Data></Cell>' . "\n";
                $xml .= '<Cell><Data ss:Type="Number">' . $item['quantity'] . '</Data></Cell>' . "\n";
                $xml .= '<Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $item['unit_price'] . '</Data></Cell>' . "\n";
                $xml .= '<Cell><Data ss:Type="Number">' . ($item['vat_rate'] / 100) . '</Data></Cell>' . "\n";

                $vatAmount = $item['quantity'] * $item['unit_price'] * ($item['vat_rate'] / 100);
                $totalHT = $item['quantity'] * $item['unit_price'];
                $totalTTC = $totalHT + $vatAmount;

                $xml .= '<Cell ss:StyleID="Currency"><Data ss:Type="Number">' . round($vatAmount, 2) . '</Data></Cell>' . "\n";
                $xml .= '<Cell ss:StyleID="Currency"><Data ss:Type="Number">' . round($totalHT, 2) . '</Data></Cell>' . "\n";
                $xml .= '<Cell ss:StyleID="Currency"><Data ss:Type="Number">' . round($totalTTC, 2) . '</Data></Cell>' . "\n";
                $xml .= '</Row>' . "\n";
            }
        }

        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";

        // Summary worksheet
        $xml .= $this->generateSummaryWorksheet($invoices, $locale);

        $xml .= '</Workbook>';

        return $xml;
    }

    /**
     * Generate summary worksheet
     */
    private function generateSummaryWorksheet(array $invoices, string $locale): string
    {
        $summaryTitle = $locale === 'fr' ? 'Resume' : 'Summary';
        $totalLabel = $locale === 'fr' ? 'Total' : 'Total';
        $paidLabel = $locale === 'fr' ? 'Paye' : 'Paid';
        $pendingLabel = $locale === 'fr' ? 'En attente' : 'Pending';
        $overdueLabel = $locale === 'fr' ? 'En retard' : 'Overdue';

        $totals = [
            'total' => 0,
            'paid' => 0,
            'pending' => 0,
            'overdue' => 0,
        ];

        foreach ($invoices as $invoice) {
            $totals['total'] += (float) $invoice['total_amount'];
            if ($invoice['status'] === 'paid') {
                $totals['paid'] += (float) $invoice['total_amount'];
            } elseif ($invoice['status'] === 'overdue') {
                $totals['overdue'] += (float) $invoice['total_amount'];
            } elseif ($invoice['status'] === 'pending') {
                $totals['pending'] += (float) $invoice['total_amount'];
            }
        }

        $xml = '<Worksheet ss:Name="' . $summaryTitle . '">' . "\n";
        $xml .= '<Table>' . "\n";

        $xml .= '<Row ss:StyleID="Header"><Cell><Data ss:Type="String">Statut</Data></Cell><Cell><Data ss:Type="String">Montant</Data></Cell></Row>' . "\n";
        $xml .= '<Row><Cell><Data ss:Type="String">' . $totalLabel . '</Data></Cell><Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $totals['total'] . '</Data></Cell></Row>' . "\n";
        $xml .= '<Row><Cell><Data ss:Type="String">' . $paidLabel . '</Data></Cell><Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $totals['paid'] . '</Data></Cell></Row>' . "\n";
        $xml .= '<Row><Cell><Data ss:Type="String">' . $pendingLabel . '</Data></Cell><Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $totals['pending'] . '</Data></Cell></Row>' . "\n";
        $xml .= '<Row><Cell><Data ss:Type="String">' . $overdueLabel . '</Data></Cell><Cell ss:StyleID="Currency"><Data ss:Type="Number">' . $totals['overdue'] . '</Data></Cell></Row>' . "\n";

        $xml .= '</Table>' . "\n";
        $xml .= '</Worksheet>' . "\n";

        return $xml;
    }

    /**
     * Get CSV/Excel headers based on locale
     */
    private function getHeaders(string $locale): array
    {
        if ($locale === 'en') {
            return [
                'Invoice Number',
                'Issue Date',
                'Due Date',
                'Status',
                'Client',
                'Description',
                'Quantity',
                'Unit Price',
                'VAT Rate',
                'VAT Amount',
                'Total (excl. VAT)',
                'Total (incl. VAT)',
            ];
        }

        return [
            'Numero Facture',
            'Date Emission',
            'Date Echeance',
            'Statut',
            'Client',
            'Description',
            'Quantite',
            'Prix Unitaire',
            'Taux TVA',
            'Montant TVA',
            'Total HT',
            'Total TTC',
        ];
    }

    /**
     * Format a row for CSV export
     */
    private function formatRow(array $invoice, ?array $client, array $item, string $locale): array
    {
        $vatAmount = $item['quantity'] * $item['unit_price'] * ($item['vat_rate'] / 100);
        $totalHT = $item['quantity'] * $item['unit_price'];
        $totalTTC = $totalHT + $vatAmount;

        return [
            $invoice['number'],
            $invoice['issue_date'],
            $invoice['due_date'],
            $this->translateStatus($invoice['status'], $locale),
            $client['company_name'] ?? '',
            $item['description'],
            number_format((float) $item['quantity'], 2, ',', ''),
            number_format((float) $item['unit_price'], 2, ',', ''),
            number_format((float) $item['vat_rate'], 1, ',', '') . '%',
            number_format($vatAmount, 2, ',', ''),
            number_format($totalHT, 2, ',', ''),
            number_format($totalTTC, 2, ',', ''),
        ];
    }

    /**
     * Translate invoice status
     */
    private function translateStatus(string $status, string $locale): string
    {
        $translations = [
            'fr' => [
                'draft' => 'Brouillon',
                'pending' => 'En attente',
                'paid' => 'Payee',
                'overdue' => 'En retard',
                'cancelled' => 'Annulee',
            ],
            'en' => [
                'draft' => 'Draft',
                'pending' => 'Pending',
                'paid' => 'Paid',
                'overdue' => 'Overdue',
                'cancelled' => 'Cancelled',
            ],
        ];

        return $translations[$locale][$status] ?? $status;
    }

    /**
     * Send CSV download headers
     */
    public function sendCsvHeaders(string $filename): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
    }

    /**
     * Send Excel download headers
     */
    public function sendExcelHeaders(string $filename): void
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
    }
}
