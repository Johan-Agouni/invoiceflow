<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use App\Models\Invoice;

/**
 * Service de gestion des factures récurrentes
 *
 * Permet de créer des modèles de facturation automatique
 * avec différentes fréquences (hebdomadaire, mensuel, trimestriel, annuel).
 */
class RecurringInvoiceService
{
    public const FREQUENCY_WEEKLY = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_QUARTERLY = 'quarterly';
    public const FREQUENCY_YEARLY = 'yearly';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Crée une nouvelle facture récurrente
     */
    public function create(array $data, array $items): int
    {
        // Calcul de la prochaine date de facturation
        $nextDate = $this->calculateNextInvoiceDate(
            $data['start_date'],
            $data['frequency'],
            $data['day_of_month'] ?? 1,
            $data['day_of_week'] ?? null
        );

        // Calcul des totaux
        $totals = $this->calculateTotals($items);

        $recurringId = (int) Database::query(
            'INSERT INTO recurring_invoices
                (user_id, client_id, name, frequency, day_of_month, day_of_week,
                 start_date, end_date, next_invoice_date, subtotal, vat_amount,
                 total_amount, notes, auto_send, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['client_id'],
                $data['name'],
                $data['frequency'],
                $data['day_of_month'] ?? 1,
                $data['day_of_week'] ?? null,
                $data['start_date'],
                $data['end_date'] ?? null,
                $nextDate,
                $totals['subtotal'],
                $totals['vat_amount'],
                $totals['total'],
                $data['notes'] ?? null,
                $data['auto_send'] ?? 0,
                self::STATUS_ACTIVE,
            ]
        )->rowCount() > 0 ? (int) Database::lastInsertId() : 0;

        if ($recurringId > 0) {
            $this->saveItems($recurringId, $items);
        }

        return $recurringId;
    }

    /**
     * Met à jour une facture récurrente
     */
    public function update(int $id, array $data, array $items): bool
    {
        $totals = $this->calculateTotals($items);

        $result = Database::query(
            'UPDATE recurring_invoices SET
                client_id = ?,
                name = ?,
                frequency = ?,
                day_of_month = ?,
                day_of_week = ?,
                end_date = ?,
                subtotal = ?,
                vat_amount = ?,
                total_amount = ?,
                notes = ?,
                auto_send = ?
             WHERE id = ?',
            [
                $data['client_id'],
                $data['name'],
                $data['frequency'],
                $data['day_of_month'] ?? 1,
                $data['day_of_week'] ?? null,
                $data['end_date'] ?? null,
                $totals['subtotal'],
                $totals['vat_amount'],
                $totals['total'],
                $data['notes'] ?? null,
                $data['auto_send'] ?? 0,
                $id,
            ]
        );

        if ($result->rowCount() > 0) {
            // Supprimer les anciens items et recréer
            Database::query('DELETE FROM recurring_invoice_items WHERE recurring_invoice_id = ?', [$id]);
            $this->saveItems($id, $items);

            return true;
        }

        return false;
    }

    /**
     * Génère les factures dues pour aujourd'hui
     */
    public function generateDueInvoices(): array
    {
        $today = date('Y-m-d');
        $generated = [];

        $dueRecurring = Database::fetchAll(
            'SELECT r.*, c.company_name as client_name
             FROM recurring_invoices r
             JOIN clients c ON c.id = r.client_id
             WHERE r.status = ?
               AND r.next_invoice_date <= ?
               AND (r.end_date IS NULL OR r.end_date >= ?)',
            [self::STATUS_ACTIVE, $today, $today]
        );

        foreach ($dueRecurring as $recurring) {
            $invoiceId = $this->generateInvoice($recurring);

            if ($invoiceId) {
                $generated[] = [
                    'recurring_id' => $recurring['id'],
                    'invoice_id' => $invoiceId,
                    'client_name' => $recurring['client_name'],
                ];

                // Envoi automatique si activé
                if ($recurring['auto_send']) {
                    $this->sendInvoice($invoiceId);
                }
            }
        }

        return $generated;
    }

    /**
     * Génère une facture à partir d'un modèle récurrent
     */
    public function generateInvoice(array $recurring): ?int
    {
        $items = $this->getItems($recurring['id']);

        if (empty($items)) {
            return null;
        }

        // Création de la facture
        $invoiceNumber = Invoice::generateNumber($recurring['user_id']);
        $issueDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+30 days'));

        $invoiceId = (int) Database::query(
            "INSERT INTO invoices
                (user_id, client_id, number, status, issue_date, due_date,
                 subtotal, vat_amount, total_amount, notes)
             VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?)",
            [
                $recurring['user_id'],
                $recurring['client_id'],
                $invoiceNumber,
                $issueDate,
                $dueDate,
                $recurring['subtotal'],
                $recurring['vat_amount'],
                $recurring['total_amount'],
                $recurring['notes'],
            ]
        )->rowCount() > 0 ? (int) Database::lastInsertId() : 0;

        if ($invoiceId) {
            // Copier les items
            foreach ($items as $item) {
                Database::query(
                    'INSERT INTO invoice_items
                        (invoice_id, description, quantity, unit_price, vat_rate, total)
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [
                        $invoiceId,
                        $item['description'],
                        $item['quantity'],
                        $item['unit_price'],
                        $item['vat_rate'],
                        $item['total'],
                    ]
                );
            }

            // Mettre à jour la récurrence
            $nextDate = $this->calculateNextInvoiceDate(
                date('Y-m-d', strtotime('+1 day')),
                $recurring['frequency'],
                $recurring['day_of_month'],
                $recurring['day_of_week']
            );

            Database::query(
                'UPDATE recurring_invoices SET
                    last_invoice_date = ?,
                    last_invoice_id = ?,
                    next_invoice_date = ?,
                    invoices_generated = invoices_generated + 1
                 WHERE id = ?',
                [$issueDate, $invoiceId, $nextDate, $recurring['id']]
            );

            // Vérifier si la récurrence est terminée
            if ($recurring['end_date'] && $nextDate > $recurring['end_date']) {
                Database::query(
                    'UPDATE recurring_invoices SET status = ? WHERE id = ?',
                    [self::STATUS_COMPLETED, $recurring['id']]
                );
            }
        }

        return $invoiceId ?: null;
    }

    /**
     * Calcule la prochaine date de facturation
     */
    public function calculateNextInvoiceDate(
        string $fromDate,
        string $frequency,
        ?int $dayOfMonth = 1,
        ?int $dayOfWeek = null
    ): string {
        $date = new \DateTime($fromDate);

        switch ($frequency) {
            case self::FREQUENCY_WEEKLY:
                if ($dayOfWeek !== null) {
                    $currentDay = (int) $date->format('N');
                    $diff = $dayOfWeek - $currentDay;

                    if ($diff <= 0) {
                        $diff += 7;
                    }

                    $date->modify("+{$diff} days");
                } else {
                    $date->modify('+1 week');
                }
                break;

            case self::FREQUENCY_MONTHLY:
                $date->modify('first day of next month');
                $lastDay = (int) $date->format('t');
                $targetDay = min($dayOfMonth, $lastDay);
                $date->setDate((int) $date->format('Y'), (int) $date->format('m'), $targetDay);
                break;

            case self::FREQUENCY_QUARTERLY:
                $date->modify('+3 months');
                $date->modify('first day of this month');
                $lastDay = (int) $date->format('t');
                $targetDay = min($dayOfMonth, $lastDay);
                $date->setDate((int) $date->format('Y'), (int) $date->format('m'), $targetDay);
                break;

            case self::FREQUENCY_YEARLY:
                $date->modify('+1 year');
                break;
        }

        return $date->format('Y-m-d');
    }

    /**
     * Récupère toutes les factures récurrentes d'un utilisateur
     */
    public function getAllForUser(int $userId, ?string $status = null): array
    {
        $sql = 'SELECT r.*, c.company_name as client_name
                FROM recurring_invoices r
                JOIN clients c ON c.id = r.client_id
                WHERE r.user_id = ?';
        $params = [$userId];

        if ($status) {
            $sql .= ' AND r.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY r.next_invoice_date ASC';

        return Database::fetchAll($sql, $params);
    }

    /**
     * Récupère une facture récurrente
     */
    public function find(int $id, int $userId): ?array
    {
        return Database::fetch(
            'SELECT r.*, c.company_name as client_name
             FROM recurring_invoices r
             JOIN clients c ON c.id = r.client_id
             WHERE r.id = ? AND r.user_id = ?',
            [$id, $userId]
        );
    }

    /**
     * Met en pause une facture récurrente
     */
    public function pause(int $id): bool
    {
        $result = Database::query(
            'UPDATE recurring_invoices SET status = ? WHERE id = ? AND status = ?',
            [self::STATUS_PAUSED, $id, self::STATUS_ACTIVE]
        );

        return $result->rowCount() > 0;
    }

    /**
     * Reprend une facture récurrente
     */
    public function resume(int $id): bool
    {
        // Recalculer la prochaine date si elle est passée
        $recurring = Database::fetch('SELECT * FROM recurring_invoices WHERE id = ?', [$id]);

        if (!$recurring || $recurring['status'] !== self::STATUS_PAUSED) {
            return false;
        }

        $nextDate = $recurring['next_invoice_date'];

        if ($nextDate < date('Y-m-d')) {
            $nextDate = $this->calculateNextInvoiceDate(
                date('Y-m-d'),
                $recurring['frequency'],
                $recurring['day_of_month'],
                $recurring['day_of_week']
            );
        }

        $result = Database::query(
            'UPDATE recurring_invoices SET status = ?, next_invoice_date = ? WHERE id = ?',
            [self::STATUS_ACTIVE, $nextDate, $id]
        );

        return $result->rowCount() > 0;
    }

    /**
     * Annule une facture récurrente
     */
    public function cancel(int $id): bool
    {
        $result = Database::query(
            'UPDATE recurring_invoices SET status = ? WHERE id = ? AND status IN (?, ?)',
            [self::STATUS_CANCELLED, $id, self::STATUS_ACTIVE, self::STATUS_PAUSED]
        );

        return $result->rowCount() > 0;
    }

    /**
     * Récupère les items d'une facture récurrente
     */
    public function getItems(int $recurringId): array
    {
        return Database::fetchAll(
            'SELECT * FROM recurring_invoice_items WHERE recurring_invoice_id = ? ORDER BY id',
            [$recurringId]
        );
    }

    /**
     * Sauvegarde les items
     */
    private function saveItems(int $recurringId, array $items): void
    {
        foreach ($items as $item) {
            $total = (float) $item['quantity'] * (float) $item['unit_price'];

            Database::query(
                'INSERT INTO recurring_invoice_items
                    (recurring_invoice_id, description, quantity, unit_price, vat_rate, total)
                 VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $recurringId,
                    $item['description'],
                    $item['quantity'],
                    $item['unit_price'],
                    $item['vat_rate'] ?? 20,
                    $total,
                ]
            );
        }
    }

    /**
     * Calcule les totaux à partir des items
     */
    private function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $vatAmount = 0;

        foreach ($items as $item) {
            $lineTotal = (float) $item['quantity'] * (float) $item['unit_price'];
            $subtotal += $lineTotal;
            $vatAmount += $lineTotal * ((float) ($item['vat_rate'] ?? 20) / 100);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'vat_amount' => round($vatAmount, 2),
            'total' => round($subtotal + $vatAmount, 2),
        ];
    }

    /**
     * Envoie une facture par email
     *
     * Génère le PDF de la facture et l'envoie au client par email.
     */
    private function sendInvoice(int $invoiceId): bool
    {
        // Récupérer la facture avec les informations client
        $invoice = Database::fetch(
            'SELECT i.*, c.email as client_email, c.company_name as client_name
             FROM invoices i
             JOIN clients c ON c.id = i.client_id
             WHERE i.id = ?',
            [$invoiceId]
        );

        if (!$invoice || empty($invoice['client_email'])) {
            return false;
        }

        // Récupérer les paramètres utilisateur
        $settings = Database::fetch(
            'SELECT * FROM settings WHERE user_id = ?',
            [$invoice['user_id']]
        );

        if (!$settings) {
            return false;
        }

        // Récupérer les items de la facture
        $items = Database::fetchAll(
            'SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id',
            [$invoiceId]
        );

        try {
            // Générer le PDF
            $pdfService = new PdfService();
            $pdfPath = $pdfService->generateInvoice($invoice, $items, $settings, false);

            if (!$pdfPath || !file_exists($pdfPath)) {
                return false;
            }

            // Envoyer l'email
            $mailService = new MailService();
            $sent = $mailService->sendInvoice($invoice, $settings, $pdfPath);

            // Nettoyer le fichier temporaire
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }

            // Mettre à jour le statut de la facture si l'envoi a réussi
            if ($sent) {
                Database::query(
                    "UPDATE invoices SET status = 'pending' WHERE id = ? AND status = 'draft'",
                    [$invoiceId]
                );
            }

            return $sent;
        } catch (\Exception $e) {
            error_log("RecurringInvoiceService: Failed to send invoice {$invoiceId}: " . $e->getMessage());

            return false;
        }
    }
}
