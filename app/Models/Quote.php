<?php

declare(strict_types=1);

namespace App\Models;

use App\Model;
use App\Database;

class Quote extends Model
{
    protected static string $table = 'quotes';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_INVOICED = 'invoiced';

    public static function allForUser(int $userId, ?string $status = null): array
    {
        $sql = "SELECT q.*, c.company_name as client_name, c.email as client_email
                FROM quotes q
                JOIN clients c ON c.id = q.client_id
                WHERE q.user_id = ?";
        $params = [$userId];

        if ($status) {
            $sql .= " AND q.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY q.created_at DESC";

        return Database::fetchAll($sql, $params);
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        return Database::fetch(
            "SELECT q.*, c.company_name, c.email as client_email, c.address as client_address,
                    c.postal_code as client_postal_code, c.city as client_city, c.country as client_country
             FROM quotes q
             JOIN clients c ON c.id = q.client_id
             WHERE q.id = ? AND q.user_id = ?",
            [$id, $userId]
        );
    }

    public static function generateNumber(int $userId): string
    {
        $year = date('Y');
        $prefix = 'DEV';

        $count = Database::fetch(
            "SELECT COUNT(*) as count FROM quotes WHERE user_id = ? AND YEAR(created_at) = ?",
            [$userId, $year]
        );

        $number = ((int) $count['count']) + 1;
        return sprintf('%s-%s-%04d', $prefix, $year, $number);
    }

    public static function convertToInvoice(int $quoteId, int $userId): int
    {
        $quote = self::findForUser($quoteId, $userId);

        if (!$quote) {
            throw new \RuntimeException('Quote not found');
        }

        // Create invoice
        $invoiceId = Invoice::create([
            'user_id' => $userId,
            'client_id' => $quote['client_id'],
            'quote_id' => $quoteId,
            'number' => Invoice::generateNumber($userId),
            'status' => Invoice::STATUS_PENDING,
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => $quote['subtotal'],
            'vat_amount' => $quote['vat_amount'],
            'total_amount' => $quote['total_amount'],
            'notes' => $quote['notes'],
        ]);

        // Copy items
        $items = QuoteItem::getForQuote($quoteId);
        foreach ($items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoiceId,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'vat_rate' => $item['vat_rate'],
                'total' => $item['total'],
            ]);
        }

        // Update quote status
        self::update($quoteId, ['status' => self::STATUS_INVOICED]);

        return $invoiceId;
    }

    public static function getStats(int $userId): array
    {
        return Database::fetch(
            "SELECT
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'accepted' THEN 1 END) as accepted,
                COUNT(CASE WHEN status = 'declined' THEN 1 END) as declined,
                COUNT(CASE WHEN status = 'sent' THEN 1 END) as pending,
                COALESCE(SUM(CASE WHEN status = 'accepted' THEN total_amount ELSE 0 END), 0) as accepted_value
             FROM quotes
             WHERE user_id = ?",
            [$userId]
        ) ?: ['total' => 0, 'accepted' => 0, 'declined' => 0, 'pending' => 0, 'accepted_value' => 0];
    }
}
