<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use App\Model;

class Client extends Model
{
    protected static string $table = 'clients';

    public static function allForUser(int $userId): array
    {
        return Database::fetchAll(
            'SELECT * FROM clients WHERE user_id = ? ORDER BY company_name ASC',
            [$userId]
        );
    }

    public static function findForUser(int $id, int $userId): ?array
    {
        return Database::fetch(
            'SELECT * FROM clients WHERE id = ? AND user_id = ?',
            [$id, $userId]
        );
    }

    public static function search(int $userId, string $query): array
    {
        $like = "%{$query}%";

        return Database::fetchAll(
            'SELECT * FROM clients WHERE user_id = ? AND (company_name LIKE ? OR email LIKE ? OR contact_name LIKE ?) ORDER BY company_name ASC',
            [$userId, $like, $like, $like]
        );
    }

    public static function getWithStats(int $userId): array
    {
        return Database::fetchAll(
            "SELECT c.*,
                COUNT(DISTINCT i.id) as total_invoices,
                COALESCE(SUM(CASE WHEN i.status = 'paid' THEN i.total_amount ELSE 0 END), 0) as total_paid,
                COALESCE(SUM(CASE WHEN i.status = 'pending' THEN i.total_amount ELSE 0 END), 0) as total_pending
             FROM clients c
             LEFT JOIN invoices i ON i.client_id = c.id
             WHERE c.user_id = ?
             GROUP BY c.id
             ORDER BY c.company_name ASC",
            [$userId]
        );
    }

    public static function countForUser(int $userId): int
    {
        return self::count('user_id = ?', [$userId]);
    }
}
