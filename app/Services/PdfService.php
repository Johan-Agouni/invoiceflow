<?php

declare(strict_types=1);

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfService
{
    private Dompdf $dompdf;

    public function __construct()
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $this->dompdf = new Dompdf($options);
    }

    public function generateInvoice(array $invoice, array $items, array $settings, bool $stream = true): ?string
    {
        $html = $this->renderInvoiceHtml($invoice, $items, $settings);

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        $filename = "facture_{$invoice['number']}.pdf";

        if ($stream) {
            // Attachment => true force le téléchargement au lieu de l'affichage dans le navigateur
            $this->dompdf->stream($filename, ['Attachment' => true]);
            exit; // Important: arrêter l'exécution après le stream
        }

        // Save to temp file and return path
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($tempPath, $this->dompdf->output());
        return $tempPath;
    }

    public function generateQuote(array $quote, array $items, array $settings, bool $stream = true): ?string
    {
        $html = $this->renderQuoteHtml($quote, $items, $settings);

        $this->dompdf->loadHtml($html);
        $this->dompdf->setPaper('A4', 'portrait');
        $this->dompdf->render();

        $filename = "devis_{$quote['number']}.pdf";

        if ($stream) {
            // Attachment => true force le téléchargement au lieu de l'affichage dans le navigateur
            $this->dompdf->stream($filename, ['Attachment' => true]);
            exit; // Important: arrêter l'exécution après le stream
        }

        // Save to temp file and return path
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        file_put_contents($tempPath, $this->dompdf->output());
        return $tempPath;
    }

    private function renderInvoiceHtml(array $invoice, array $items, array $settings): string
    {
        $statusLabels = [
            'draft' => 'Brouillon',
            'pending' => 'En attente',
            'paid' => 'Payée',
            'overdue' => 'En retard',
        ];

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }
                body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #333; line-height: 1.4; }
                .container { padding: 40px; }
                .header { display: flex; justify-content: space-between; margin-bottom: 40px; }
                .header-left { width: 50%; }
                .header-right { width: 50%; text-align: right; }
                .logo { max-width: 150px; max-height: 80px; margin-bottom: 10px; }
                .company-name { font-size: 16pt; font-weight: bold; color: #2563eb; }
                .company-info { font-size: 9pt; color: #666; margin-top: 5px; }
                .invoice-title { font-size: 24pt; font-weight: bold; color: #1e40af; }
                .invoice-number { font-size: 12pt; color: #666; margin-top: 5px; }
                .invoice-meta { margin-top: 10px; font-size: 10pt; }
                .invoice-meta span { display: block; }
                .addresses { display: flex; margin-bottom: 30px; }
                .address-box { width: 50%; }
                .address-label { font-size: 9pt; text-transform: uppercase; color: #666; margin-bottom: 5px; }
                .address-content { font-size: 10pt; }
                .client-name { font-weight: bold; font-size: 11pt; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th { background: #f1f5f9; text-align: left; padding: 10px; font-size: 9pt; text-transform: uppercase; color: #475569; border-bottom: 2px solid #e2e8f0; }
                td { padding: 10px; border-bottom: 1px solid #e2e8f0; font-size: 10pt; }
                .text-right { text-align: right; }
                .totals { width: 300px; margin-left: auto; margin-top: 20px; }
                .totals tr td { padding: 8px 10px; }
                .totals .total-row { font-weight: bold; font-size: 12pt; background: #1e40af; color: white; }
                .notes { margin-top: 30px; padding: 15px; background: #f8fafc; border-radius: 5px; }
                .notes-title { font-weight: bold; margin-bottom: 5px; }
                .footer { margin-top: 40px; padding-top: 20px; border-top: 1px solid #e2e8f0; font-size: 8pt; color: #666; }
                .bank-info { margin-top: 15px; }
                .bank-info strong { color: #333; }
                .status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 9pt; font-weight: bold; }
                .status-paid { background: #dcfce7; color: #166534; }
                .status-pending { background: #fef3c7; color: #92400e; }
                .status-overdue { background: #fee2e2; color: #991b1b; }
            </style>
        </head>
        <body>
            <div class="container">
                <table style="margin-bottom: 30px;">
                    <tr>
                        <td style="width: 50%; vertical-align: top; border: none;">
                            <?php if (!empty($settings['company_logo'])): ?>
                                <img src="<?= $_ENV['APP_URL'] . $settings['company_logo'] ?>" class="logo" alt="Logo">
                            <?php endif; ?>
                            <div class="company-name"><?= htmlspecialchars($settings['company_name'] ?: 'Votre Entreprise') ?></div>
                            <div class="company-info">
                                <?= htmlspecialchars($settings['company_address']) ?><br>
                                <?= htmlspecialchars($settings['company_postal_code']) ?> <?= htmlspecialchars($settings['company_city']) ?><br>
                                <?php if ($settings['company_siret']): ?>
                                    SIRET : <?= htmlspecialchars($settings['company_siret']) ?><br>
                                <?php endif; ?>
                                <?php if ($settings['company_vat_number']): ?>
                                    N° TVA : <?= htmlspecialchars($settings['company_vat_number']) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td style="width: 50%; vertical-align: top; text-align: right; border: none;">
                            <div class="invoice-title">FACTURE</div>
                            <div class="invoice-number"><?= htmlspecialchars($invoice['number']) ?></div>
                            <div class="invoice-meta">
                                <span>Date : <?= date('d/m/Y', strtotime($invoice['issue_date'])) ?></span>
                                <span>Échéance : <?= date('d/m/Y', strtotime($invoice['due_date'])) ?></span>
                                <span style="margin-top: 10px;">
                                    <span class="status-badge status-<?= $invoice['status'] ?>">
                                        <?= $statusLabels[$invoice['status']] ?? $invoice['status'] ?>
                                    </span>
                                </span>
                            </div>
                        </td>
                    </tr>
                </table>

                <table style="margin-bottom: 30px;">
                    <tr>
                        <td style="width: 50%; border: none;"></td>
                        <td style="width: 50%; vertical-align: top; border: none; padding: 15px; background: #f8fafc; border-radius: 5px;">
                            <div class="address-label">Facturer à</div>
                            <div class="client-name"><?= htmlspecialchars($invoice['company_name']) ?></div>
                            <div class="address-content">
                                <?= htmlspecialchars($invoice['client_address']) ?><br>
                                <?= htmlspecialchars($invoice['client_postal_code']) ?> <?= htmlspecialchars($invoice['client_city']) ?><br>
                                <?= htmlspecialchars($invoice['client_country']) ?>
                                <?php if ($invoice['client_vat_number']): ?>
                                    <br>N° TVA : <?= htmlspecialchars($invoice['client_vat_number']) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                </table>

                <table>
                    <thead>
                        <tr>
                            <th style="width: 50%;">Description</th>
                            <th class="text-right" style="width: 12%;">Qté</th>
                            <th class="text-right" style="width: 15%;">Prix unit. HT</th>
                            <th class="text-right" style="width: 10%;">TVA</th>
                            <th class="text-right" style="width: 13%;">Total HT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td class="text-right"><?= number_format($item['quantity'], 2, ',', ' ') ?></td>
                            <td class="text-right"><?= number_format($item['unit_price'], 2, ',', ' ') ?> €</td>
                            <td class="text-right"><?= number_format($item['vat_rate'], 0) ?>%</td>
                            <td class="text-right"><?= number_format($item['total'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <table class="totals">
                    <tr>
                        <td>Sous-total HT</td>
                        <td class="text-right"><?= number_format($invoice['subtotal'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td>TVA</td>
                        <td class="text-right"><?= number_format($invoice['vat_amount'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="total-row">
                        <td>Total TTC</td>
                        <td class="text-right"><?= number_format($invoice['total_amount'], 2, ',', ' ') ?> €</td>
                    </tr>
                </table>

                <?php if ($invoice['notes']): ?>
                <div class="notes">
                    <div class="notes-title">Notes</div>
                    <?= nl2br(htmlspecialchars($invoice['notes'])) ?>
                </div>
                <?php endif; ?>

                <div class="footer">
                    <?php if ($settings['bank_iban']): ?>
                    <div class="bank-info">
                        <strong>Coordonnées bancaires :</strong><br>
                        <?= htmlspecialchars($settings['bank_name']) ?><br>
                        IBAN : <?= htmlspecialchars($settings['bank_iban']) ?><br>
                        BIC : <?= htmlspecialchars($settings['bank_bic']) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($settings['invoice_footer']): ?>
                    <div style="margin-top: 15px;">
                        <?= nl2br(htmlspecialchars($settings['invoice_footer'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    private function renderQuoteHtml(array $quote, array $items, array $settings): string
    {
        // Similar to invoice but with "DEVIS" title and valid_until instead of due_date
        $html = $this->renderInvoiceHtml(
            array_merge($quote, [
                'due_date' => $quote['valid_until'],
                'number' => $quote['number'],
            ]),
            $items,
            $settings
        );

        // Replace FACTURE with DEVIS
        $html = str_replace('>FACTURE<', '>DEVIS<', $html);
        $html = str_replace('Échéance', 'Valide jusqu\'au', $html);

        return $html;
    }
}
