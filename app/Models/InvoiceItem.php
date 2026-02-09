<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use App\Model;

class InvoiceItem extends Model
{
    protected static string $table = 'invoice_items';

    public static function getForInvoice(int $invoiceId): array
    {
        return Database::fetchAll(
            'SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY id ASC',
            [$invoiceId]
        );
    }

    public static function createMany(int $invoiceId, array $items): void
    {
        foreach ($items as $item) {
            self::create([
                'invoice_id' => $invoiceId,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'vat_rate' => $item['vat_rate'] ?? 20,
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }
    }

    public static function deleteForInvoice(int $invoiceId): int
    {
        return Database::delete('invoice_items', 'invoice_id = ?', [$invoiceId]);
    }

    public static function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $vatAmount = 0;

        foreach ($items as $item) {
            $lineTotal = $item['quantity'] * $item['unit_price'];
            $subtotal += $lineTotal;
            $vatAmount += $lineTotal * ($item['vat_rate'] / 100);
        }

        return [
            'subtotal' => round($subtotal, 2),
            'vat_amount' => round($vatAmount, 2),
            'total' => round($subtotal + $vatAmount, 2),
        ];
    }
}
