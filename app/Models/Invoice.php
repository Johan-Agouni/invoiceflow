<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use App\Model;

class Invoice extends Model
{
    protected static string $table = 'invoices';

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    public static function allForUser(int $userId, ?string $status = null): array
    {
        $sql = 'SELECT i.*, c.company_name as client_name, c.email as client_email
                FROM invoices i
                JOIN clients c ON c.id = i.client_id
                WHERE i.user_id = ?';
        $params = [$userId];

        if ($status) {
            $sql .= ' AND i.status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY i.created_at DESC';

        return Database::fetchAll($sql, $params);
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        return Database::fetch(
            'SELECT i.*, c.company_name, c.email as client_email, c.address as client_address,
                    c.postal_code as client_postal_code, c.city as client_city, c.country as client_country,
                    c.vat_number as client_vat_number
             FROM invoices i
             JOIN clients c ON c.id = i.client_id
             WHERE i.id = ? AND i.user_id = ?',
            [$id, $userId]
        );
    }

    public static function generateNumber(int $userId): string
    {
        $year = date('Y');
        $prefix = 'FAC';

        $count = Database::fetch(
            'SELECT COUNT(*) as count FROM invoices WHERE user_id = ? AND YEAR(created_at) = ?',
            [$userId, $year]
        );

        $number = ((int) $count['count']) + 1;

        return sprintf('%s-%s-%04d', $prefix, $year, $number);
    }

    public static function getOverdue(int $userId): array
    {
        return Database::fetchAll(
            "SELECT i.*, c.company_name, c.email as client_email
             FROM invoices i
             JOIN clients c ON c.id = i.client_id
             WHERE i.user_id = ? AND i.status = 'pending' AND i.due_date < CURDATE()
             ORDER BY i.due_date ASC",
            [$userId]
        );
    }

    public static function markAsPaid(int $id, ?string $paidAt = null): int
    {
        return self::update($id, [
            'status' => self::STATUS_PAID,
            'paid_at' => $paidAt ?? date('Y-m-d H:i:s'),
        ]);
    }

    public static function getStats(int $userId): array
    {
        $currentMonth = date('Y-m');
        $currentYear = date('Y');

        $stats = Database::fetch(
            "SELECT
                COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'overdue' OR (status = 'pending' AND due_date < CURDATE()) THEN 1 END) as overdue_count,
                COALESCE(SUM(CASE WHEN status = 'paid' THEN total_amount ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN total_amount ELSE 0 END), 0) as total_pending,
                COALESCE(SUM(CASE WHEN status = 'paid' AND DATE_FORMAT(paid_at, '%Y-%m') = ? THEN total_amount ELSE 0 END), 0) as paid_this_month,
                COALESCE(SUM(CASE WHEN status = 'paid' AND YEAR(paid_at) = ? THEN total_amount ELSE 0 END), 0) as paid_this_year
             FROM invoices
             WHERE user_id = ?",
            [$currentMonth, $currentYear, $userId]
        );

        return $stats ?: [
            'paid_count' => 0,
            'pending_count' => 0,
            'overdue_count' => 0,
            'total_paid' => 0,
            'total_pending' => 0,
            'paid_this_month' => 0,
            'paid_this_year' => 0,
        ];
    }

    public static function getMonthlyRevenue(int $userId, int $months = 12): array
    {
        return Database::fetchAll(
            "SELECT
                DATE_FORMAT(paid_at, '%Y-%m') as month,
                SUM(total_amount) as revenue
             FROM invoices
             WHERE user_id = ? AND status = 'paid' AND paid_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
             GROUP BY DATE_FORMAT(paid_at, '%Y-%m')
             ORDER BY month ASC",
            [$userId, $months]
        );
    }

    public static function updateOverdueStatus(): int
    {
        return Database::query(
            "UPDATE invoices SET status = 'overdue' WHERE status = 'pending' AND due_date < CURDATE()"
        )->rowCount();
    }
}
