<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

/**
 * Service d'export FEC (Fichier des Écritures Comptables)
 *
 * Le FEC est obligatoire en France pour toute entreprise tenant une comptabilité informatisée.
 * Format réglementé par l'article A.47 A-1 du Livre des Procédures Fiscales.
 *
 * @see https://www.legifrance.gouv.fr/codes/article_lc/LEGIARTI000027804775
 */
class FecExportService
{
    // Comptes comptables par défaut (Plan Comptable Général)
    private const COMPTE_CLIENT = '411000';        // Clients
    private const COMPTE_VENTES_SERVICES = '706000'; // Prestations de services
    private const COMPTE_VENTES_PRODUITS = '707000'; // Ventes de marchandises
    private const COMPTE_TVA_COLLECTEE_20 = '445710'; // TVA collectée 20%
    private const COMPTE_TVA_COLLECTEE_10 = '445711'; // TVA collectée 10%
    private const COMPTE_TVA_COLLECTEE_5_5 = '445712'; // TVA collectée 5.5%
    private const COMPTE_BANQUE = '512000';        // Banque

    private int $userId;

    private array $settings;

    private string $siret;

    public function __construct(int $userId)
    {
        $this->userId = $userId;
        $this->settings = $this->loadSettings();
        $this->siret = $this->settings['company_siret'] ?? '';
    }

    /**
     * Génère le fichier FEC pour une période donnée
     */
    public function generate(string $dateFrom, string $dateTo): string
    {
        $invoices = $this->getInvoicesForPeriod($dateFrom, $dateTo);

        if (empty($invoices)) {
            throw new \RuntimeException('Aucune facture trouvée pour cette période.');
        }

        $lines = [];
        $lines[] = $this->getHeader();

        $pieceNumber = 1;

        foreach ($invoices as $invoice) {
            $entries = $this->generateEntriesForInvoice($invoice, $pieceNumber);

            foreach ($entries as $entry) {
                $lines[] = $this->formatLine($entry);
            }

            $pieceNumber++;
        }

        return implode("\r\n", $lines);
    }

    /**
     * Exporte le FEC en téléchargement
     */
    public function download(string $dateFrom, string $dateTo): void
    {
        $content = $this->generate($dateFrom, $dateTo);
        $filename = $this->generateFilename($dateFrom, $dateTo);

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($content));

        echo $content;
        exit;
    }

    /**
     * Génère le nom de fichier normalisé
     * Format: SIREN + FEC + Date de clôture
     */
    private function generateFilename(string $dateFrom, string $dateTo): string
    {
        $siren = substr(preg_replace('/\D/', '', $this->siret), 0, 9);
        $dateClose = str_replace('-', '', $dateTo);

        return "{$siren}FEC{$dateClose}.txt";
    }

    /**
     * En-tête obligatoire du FEC (18 colonnes)
     */
    private function getHeader(): string
    {
        return implode("\t", [
            'JournalCode',      // Code journal
            'JournalLib',       // Libellé journal
            'EcritureNum',      // Numéro de l'écriture
            'EcritureDate',     // Date de l'écriture
            'CompteNum',        // Numéro de compte
            'CompteLib',        // Libellé du compte
            'CompAuxNum',       // Numéro de compte auxiliaire
            'CompAuxLib',       // Libellé compte auxiliaire
            'PieceRef',         // Référence de la pièce
            'PieceDate',        // Date de la pièce
            'EcritureLib',      // Libellé de l'écriture
            'Debit',            // Montant débit
            'Credit',           // Montant crédit
            'EcritureLet',      // Lettre de lettrage
            'DateLet',          // Date de lettrage
            'ValidDate',        // Date de validation
            'Montantdevise',    // Montant en devise
            'Idevise',          // Identifiant devise
        ]);
    }

    /**
     * Génère les écritures comptables pour une facture
     */
    private function generateEntriesForInvoice(array $invoice, int $pieceNumber): array
    {
        $entries = [];
        $date = date('Ymd', strtotime($invoice['issue_date']));
        $paidDate = $invoice['paid_at'] ? date('Ymd', strtotime($invoice['paid_at'])) : '';
        $ecritureNum = sprintf('%06d', $pieceNumber);

        // 1. Écriture client (débit)
        $entries[] = [
            'JournalCode' => 'VE',
            'JournalLib' => 'Ventes',
            'EcritureNum' => $ecritureNum,
            'EcritureDate' => $date,
            'CompteNum' => self::COMPTE_CLIENT,
            'CompteLib' => 'Clients',
            'CompAuxNum' => 'CLI' . sprintf('%06d', $invoice['client_id']),
            'CompAuxLib' => $this->sanitize($invoice['client_name'] ?? ''),
            'PieceRef' => $invoice['number'],
            'PieceDate' => $date,
            'EcritureLib' => 'Facture ' . $invoice['number'],
            'Debit' => $this->formatAmount($invoice['total_amount']),
            'Credit' => '0,00',
            'EcritureLet' => $invoice['status'] === 'paid' ? 'A' : '',
            'DateLet' => $paidDate,
            'ValidDate' => $date,
            'Montantdevise' => '',
            'Idevise' => 'EUR',
        ];

        // 2. Écriture ventes HT (crédit)
        $entries[] = [
            'JournalCode' => 'VE',
            'JournalLib' => 'Ventes',
            'EcritureNum' => $ecritureNum,
            'EcritureDate' => $date,
            'CompteNum' => self::COMPTE_VENTES_SERVICES,
            'CompteLib' => 'Prestations de services',
            'CompAuxNum' => '',
            'CompAuxLib' => '',
            'PieceRef' => $invoice['number'],
            'PieceDate' => $date,
            'EcritureLib' => 'Facture ' . $invoice['number'] . ' - HT',
            'Debit' => '0,00',
            'Credit' => $this->formatAmount($invoice['subtotal']),
            'EcritureLet' => '',
            'DateLet' => '',
            'ValidDate' => $date,
            'Montantdevise' => '',
            'Idevise' => 'EUR',
        ];

        // 3. Écriture TVA collectée (crédit) si TVA > 0
        if ((float) $invoice['vat_amount'] > 0) {
            $compteVat = $this->getVatAccount($invoice);

            $entries[] = [
                'JournalCode' => 'VE',
                'JournalLib' => 'Ventes',
                'EcritureNum' => $ecritureNum,
                'EcritureDate' => $date,
                'CompteNum' => $compteVat,
                'CompteLib' => 'TVA collectée',
                'CompAuxNum' => '',
                'CompAuxLib' => '',
                'PieceRef' => $invoice['number'],
                'PieceDate' => $date,
                'EcritureLib' => 'Facture ' . $invoice['number'] . ' - TVA',
                'Debit' => '0,00',
                'Credit' => $this->formatAmount($invoice['vat_amount']),
                'EcritureLet' => '',
                'DateLet' => '',
                'ValidDate' => $date,
                'Montantdevise' => '',
                'Idevise' => 'EUR',
            ];
        }

        // 4. Si facture payée, écriture de règlement
        if ($invoice['status'] === 'paid' && $invoice['paid_at']) {
            $paidDate = date('Ymd', strtotime($invoice['paid_at']));
            $ecritureNumPaiement = sprintf('%06d', $pieceNumber + 10000);

            // Débit banque
            $entries[] = [
                'JournalCode' => 'BQ',
                'JournalLib' => 'Banque',
                'EcritureNum' => $ecritureNumPaiement,
                'EcritureDate' => $paidDate,
                'CompteNum' => self::COMPTE_BANQUE,
                'CompteLib' => 'Banque',
                'CompAuxNum' => '',
                'CompAuxLib' => '',
                'PieceRef' => $invoice['number'],
                'PieceDate' => $paidDate,
                'EcritureLib' => 'Règlement ' . $invoice['number'],
                'Debit' => $this->formatAmount($invoice['total_amount']),
                'Credit' => '0,00',
                'EcritureLet' => '',
                'DateLet' => '',
                'ValidDate' => $paidDate,
                'Montantdevise' => '',
                'Idevise' => 'EUR',
            ];

            // Crédit client
            $entries[] = [
                'JournalCode' => 'BQ',
                'JournalLib' => 'Banque',
                'EcritureNum' => $ecritureNumPaiement,
                'EcritureDate' => $paidDate,
                'CompteNum' => self::COMPTE_CLIENT,
                'CompteLib' => 'Clients',
                'CompAuxNum' => 'CLI' . sprintf('%06d', $invoice['client_id']),
                'CompAuxLib' => $this->sanitize($invoice['client_name'] ?? ''),
                'PieceRef' => $invoice['number'],
                'PieceDate' => $paidDate,
                'EcritureLib' => 'Règlement ' . $invoice['number'],
                'Debit' => '0,00',
                'Credit' => $this->formatAmount($invoice['total_amount']),
                'EcritureLet' => 'A',
                'DateLet' => $paidDate,
                'ValidDate' => $paidDate,
                'Montantdevise' => '',
                'Idevise' => 'EUR',
            ];
        }

        return $entries;
    }

    /**
     * Formate une ligne du FEC
     */
    private function formatLine(array $entry): string
    {
        return implode("\t", [
            $entry['JournalCode'],
            $entry['JournalLib'],
            $entry['EcritureNum'],
            $entry['EcritureDate'],
            $entry['CompteNum'],
            $entry['CompteLib'],
            $entry['CompAuxNum'],
            $entry['CompAuxLib'],
            $entry['PieceRef'],
            $entry['PieceDate'],
            $entry['EcritureLib'],
            $entry['Debit'],
            $entry['Credit'],
            $entry['EcritureLet'],
            $entry['DateLet'],
            $entry['ValidDate'],
            $entry['Montantdevise'],
            $entry['Idevise'],
        ]);
    }

    /**
     * Formate un montant au format FEC (virgule décimale)
     */
    private function formatAmount(float|string $amount): string
    {
        return number_format((float) $amount, 2, ',', '');
    }

    /**
     * Détermine le compte de TVA selon le taux
     */
    private function getVatAccount(array $invoice): string
    {
        // Calcul du taux de TVA effectif
        if ((float) $invoice['subtotal'] > 0) {
            $rate = ((float) $invoice['vat_amount'] / (float) $invoice['subtotal']) * 100;
        } else {
            $rate = 20;
        }

        if ($rate >= 19 && $rate <= 21) {
            return self::COMPTE_TVA_COLLECTEE_20;
        } elseif ($rate >= 9 && $rate <= 11) {
            return self::COMPTE_TVA_COLLECTEE_10;
        } elseif ($rate >= 5 && $rate <= 6) {
            return self::COMPTE_TVA_COLLECTEE_5_5;
        }

        return self::COMPTE_TVA_COLLECTEE_20;
    }

    /**
     * Nettoie une chaîne pour le FEC
     */
    private function sanitize(string $value): string
    {
        // Supprime les caractères de contrôle et tabulations
        $value = preg_replace('/[\x00-\x1F\x7F\t]/', ' ', $value);
        // Limite la longueur
        return mb_substr(trim($value), 0, 100);
    }

    /**
     * Récupère les factures pour une période
     */
    private function getInvoicesForPeriod(string $dateFrom, string $dateTo): array
    {
        return Database::fetchAll(
            "SELECT i.*, c.company_name as client_name
             FROM invoices i
             JOIN clients c ON c.id = i.client_id
             WHERE i.user_id = ?
               AND i.status != 'draft'
               AND i.issue_date BETWEEN ? AND ?
             ORDER BY i.issue_date ASC, i.id ASC",
            [$this->userId, $dateFrom, $dateTo]
        );
    }

    /**
     * Charge les paramètres utilisateur
     */
    private function loadSettings(): array
    {
        return Database::fetch(
            "SELECT * FROM settings WHERE user_id = ?",
            [$this->userId]
        ) ?: [];
    }

    /**
     * Valide les données avant export
     */
    public function validate(string $dateFrom, string $dateTo): array
    {
        $errors = [];

        if (empty($this->siret)) {
            $errors[] = 'Le numéro SIRET doit être renseigné dans les paramètres.';
        } elseif (strlen(preg_replace('/\D/', '', $this->siret)) < 9) {
            $errors[] = 'Le numéro SIRET est invalide.';
        }

        $invoices = $this->getInvoicesForPeriod($dateFrom, $dateTo);

        if (empty($invoices)) {
            $errors[] = 'Aucune facture trouvée pour cette période.';
        }

        // Vérification de la séquentialité des numéros
        $numbers = array_column($invoices, 'number');
        // Note: vérification basique, à améliorer selon les besoins

        return $errors;
    }
}
