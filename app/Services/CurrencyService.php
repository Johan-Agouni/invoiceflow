<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

/**
 * Service de gestion des devises et taux de change
 *
 * Permet la gestion multi-devises pour les factures et devis
 * avec conversion automatique vers EUR pour la comptabilité.
 */
class CurrencyService
{
    private const DEFAULT_CURRENCY = 'EUR';

    private const ECB_API_URL = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    /**
     * Récupère toutes les devises actives
     */
    public function getActiveCurrencies(): array
    {
        return Database::fetchAll(
            "SELECT * FROM currencies WHERE is_active = 1 ORDER BY code"
        );
    }

    /**
     * Récupère une devise par son code
     */
    public function getCurrency(string $code): ?array
    {
        return Database::fetch(
            "SELECT * FROM currencies WHERE code = ?",
            [strtoupper($code)]
        );
    }

    /**
     * Récupère le taux de change le plus récent
     */
    public function getExchangeRate(string $fromCurrency, string $toCurrency, ?string $date = null): ?float
    {
        $fromCurrency = strtoupper($fromCurrency);
        $toCurrency = strtoupper($toCurrency);

        if ($fromCurrency === $toCurrency) {
            return 1.0;
        }

        $date = $date ?? date('Y-m-d');

        // Chercher le taux direct
        $rate = $this->findRate($fromCurrency, $toCurrency, $date);

        if ($rate !== null) {
            return $rate;
        }

        // Chercher le taux inverse
        $inverseRate = $this->findRate($toCurrency, $fromCurrency, $date);

        if ($inverseRate !== null && $inverseRate > 0) {
            return 1 / $inverseRate;
        }

        // Conversion via EUR
        if ($fromCurrency !== self::DEFAULT_CURRENCY && $toCurrency !== self::DEFAULT_CURRENCY) {
            $fromToEur = $this->getExchangeRate($fromCurrency, self::DEFAULT_CURRENCY, $date);
            $eurToTarget = $this->getExchangeRate(self::DEFAULT_CURRENCY, $toCurrency, $date);

            if ($fromToEur !== null && $eurToTarget !== null) {
                return $fromToEur * $eurToTarget;
            }
        }

        return null;
    }

    /**
     * Convertit un montant d'une devise à une autre
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency, ?string $date = null): ?float
    {
        $rate = $this->getExchangeRate($fromCurrency, $toCurrency, $date);

        if ($rate === null) {
            return null;
        }

        return round($amount * $rate, $this->getDecimalPlaces($toCurrency));
    }

    /**
     * Formate un montant avec le symbole de la devise
     */
    public function format(float $amount, string $currencyCode, ?string $locale = 'fr_FR'): string
    {
        $currency = $this->getCurrency($currencyCode);

        if (!$currency) {
            return number_format($amount, 2, ',', ' ') . ' ' . $currencyCode;
        }

        $decimals = $currency['decimal_places'];
        $formatted = number_format($amount, $decimals, ',', ' ');

        // Position du symbole selon la devise
        $symbolAfter = in_array($currencyCode, ['EUR', 'CHF', 'MAD', 'TND', 'XOF']);

        if ($symbolAfter) {
            return $formatted . ' ' . $currency['symbol'];
        }

        return $currency['symbol'] . $formatted;
    }

    /**
     * Met à jour les taux de change depuis la BCE
     */
    public function updateRatesFromEcb(): array
    {
        $xml = @file_get_contents(self::ECB_API_URL);

        if ($xml === false) {
            throw new \RuntimeException('Impossible de récupérer les taux de la BCE');
        }

        $data = simplexml_load_string($xml);

        if ($data === false) {
            throw new \RuntimeException('Format XML invalide');
        }

        $updated = [];
        $date = date('Y-m-d');

        foreach ($data->Cube->Cube->Cube as $rate) {
            $currency = (string) $rate['currency'];
            $rateValue = (float) $rate['rate'];

            if ($rateValue > 0) {
                $this->saveRate(self::DEFAULT_CURRENCY, $currency, $rateValue, $date, 'ECB');
                $updated[] = $currency;
            }
        }

        return $updated;
    }

    /**
     * Enregistre un taux de change
     */
    public function saveRate(
        string $baseCurrency,
        string $targetCurrency,
        float $rate,
        string $date,
        ?string $source = null
    ): bool {
        $result = Database::query(
            "INSERT INTO exchange_rates (base_currency, target_currency, rate, date, source)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE rate = VALUES(rate), source = VALUES(source)",
            [
                strtoupper($baseCurrency),
                strtoupper($targetCurrency),
                $rate,
                $date,
                $source,
            ]
        );

        return $result->rowCount() > 0;
    }

    /**
     * Récupère l'historique des taux pour une paire de devises
     */
    public function getRateHistory(string $fromCurrency, string $toCurrency, int $days = 30): array
    {
        $dateFrom = date('Y-m-d', strtotime("-{$days} days"));

        return Database::fetchAll(
            "SELECT date, rate FROM exchange_rates
             WHERE base_currency = ? AND target_currency = ? AND date >= ?
             ORDER BY date ASC",
            [strtoupper($fromCurrency), strtoupper($toCurrency), $dateFrom]
        );
    }

    /**
     * Ajoute une nouvelle devise
     */
    public function addCurrency(string $code, string $name, string $symbol, int $decimalPlaces = 2): bool
    {
        $result = Database::query(
            "INSERT IGNORE INTO currencies (code, name, symbol, decimal_places) VALUES (?, ?, ?, ?)",
            [strtoupper($code), $name, $symbol, $decimalPlaces]
        );

        return $result->rowCount() > 0;
    }

    /**
     * Active/désactive une devise
     */
    public function toggleCurrency(string $code, bool $active): bool
    {
        $result = Database::query(
            "UPDATE currencies SET is_active = ? WHERE code = ?",
            [$active ? 1 : 0, strtoupper($code)]
        );

        return $result->rowCount() > 0;
    }

    /**
     * Récupère le nombre de décimales pour une devise
     */
    private function getDecimalPlaces(string $currencyCode): int
    {
        $currency = $this->getCurrency($currencyCode);

        return $currency ? (int) $currency['decimal_places'] : 2;
    }

    /**
     * Cherche un taux de change dans la base
     */
    private function findRate(string $baseCurrency, string $targetCurrency, string $date): ?float
    {
        // Chercher le taux exact pour la date
        $rate = Database::fetch(
            "SELECT rate FROM exchange_rates
             WHERE base_currency = ? AND target_currency = ? AND date = ?",
            [$baseCurrency, $targetCurrency, $date]
        );

        if ($rate) {
            return (float) $rate['rate'];
        }

        // Chercher le taux le plus récent avant la date
        $rate = Database::fetch(
            "SELECT rate FROM exchange_rates
             WHERE base_currency = ? AND target_currency = ? AND date <= ?
             ORDER BY date DESC LIMIT 1",
            [$baseCurrency, $targetCurrency, $date]
        );

        return $rate ? (float) $rate['rate'] : null;
    }
}
