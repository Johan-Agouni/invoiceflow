<?php

declare(strict_types=1);

namespace App\Models;

use App\Database;
use App\Model;

class Settings extends Model
{
    protected static string $table = 'settings';

    public static function getForUser(int $userId): array
    {
        $settings = Database::fetch(
            'SELECT * FROM settings WHERE user_id = ?',
            [$userId]
        );

        return $settings ?: self::getDefaults($userId);
    }

    public static function getDefaults(int $userId): array
    {
        return [
            'user_id' => $userId,
            'company_name' => '',
            'company_address' => '',
            'company_postal_code' => '',
            'company_city' => '',
            'company_country' => 'France',
            'company_email' => '',
            'company_phone' => '',
            'company_siret' => '',
            'company_vat_number' => '',
            'company_logo' => null,
            'default_vat_rate' => 20.00,
            'payment_terms' => 30,
            'invoice_footer' => 'En cas de retard de paiement, une pénalité de 3 fois le taux d\'intérêt légal sera appliquée, ainsi qu\'une indemnité forfaitaire de 40€ pour frais de recouvrement.',
            'bank_name' => '',
            'bank_iban' => '',
            'bank_bic' => '',
        ];
    }

    public static function updateForUser(int $userId, array $data): void
    {
        $existing = Database::fetch(
            'SELECT id FROM settings WHERE user_id = ?',
            [$userId]
        );

        if ($existing) {
            self::update($existing['id'], $data);
        } else {
            $data['user_id'] = $userId;
            self::create($data);
        }
    }
}
