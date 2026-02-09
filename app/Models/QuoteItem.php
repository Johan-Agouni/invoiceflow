<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use App\Model;

class QuoteItem extends Model
{
    protected static string $table = 'quote_items';

    public static function getForQuote(int $quoteId): array
    {
        return Database::fetchAll(
            'SELECT * FROM quote_items WHERE quote_id = ? ORDER BY id ASC',
            [$quoteId]
        );
    }

    public static function createMany(int $quoteId, array $items): void
    {
        foreach ($items as $item) {
            self::create([
                'quote_id' => $quoteId,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'vat_rate' => $item['vat_rate'] ?? 20,
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }
    }

    public static function deleteForQuote(int $quoteId): int
    {
        return Database::delete('quote_items', 'quote_id = ?', [$quoteId]);
    }
}
