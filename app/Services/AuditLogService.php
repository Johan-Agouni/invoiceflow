<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;

/**
 * Service de journalisation des actions (Audit Trail)
 *
 * Enregistre toutes les actions importantes pour la traçabilité
 * et la conformité réglementaire.
 */
class AuditLogService
{
    // Types d'actions
    public const ACTION_CREATE = 'create';
    public const ACTION_UPDATE = 'update';
    public const ACTION_DELETE = 'delete';
    public const ACTION_VIEW = 'view';
    public const ACTION_EXPORT = 'export';
    public const ACTION_SEND = 'send';
    public const ACTION_LOGIN = 'login';
    public const ACTION_LOGOUT = 'logout';
    public const ACTION_LOGIN_FAILED = 'login_failed';
    public const ACTION_PASSWORD_RESET = 'password_reset';
    public const ACTION_TWO_FACTOR_ENABLED = '2fa_enabled';
    public const ACTION_TWO_FACTOR_DISABLED = '2fa_disabled';
    public const ACTION_PAYMENT_RECEIVED = 'payment_received';
    public const ACTION_PAYMENT_REFUNDED = 'payment_refunded';
    public const ACTION_STATUS_CHANGE = 'status_change';

    // Types d'entités
    public const ENTITY_USER = 'user';
    public const ENTITY_CLIENT = 'client';
    public const ENTITY_INVOICE = 'invoice';
    public const ENTITY_QUOTE = 'quote';
    public const ENTITY_SETTINGS = 'settings';
    public const ENTITY_API_TOKEN = 'api_token';

    private static ?int $currentUserId = null;

    /**
     * Définit l'utilisateur courant pour les logs
     */
    public static function setCurrentUser(?int $userId): void
    {
        self::$currentUserId = $userId;
    }

    /**
     * Enregistre une action dans le journal d'audit
     */
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): int {
        // Filtrer les données sensibles
        $oldValues = self::filterSensitiveData($oldValues);
        $newValues = self::filterSensitiveData($newValues);

        $result = Database::query(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, metadata)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                self::$currentUserId ?? $_SESSION['user_id'] ?? null,
                $action,
                $entityType,
                $entityId,
                $oldValues ? json_encode($oldValues) : null,
                $newValues ? json_encode($newValues) : null,
                self::getClientIp(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512),
                $metadata ? json_encode($metadata) : null,
            ]
        );

        return (int) Database::lastInsertId();
    }

    /**
     * Raccourci pour logger une création
     */
    public static function logCreate(string $entityType, int $entityId, array $data): int
    {
        return self::log(self::ACTION_CREATE, $entityType, $entityId, null, $data);
    }

    /**
     * Raccourci pour logger une mise à jour
     */
    public static function logUpdate(string $entityType, int $entityId, array $oldData, array $newData): int
    {
        // Ne logger que les champs modifiés
        $changes = self::computeChanges($oldData, $newData);

        if (empty($changes['old']) && empty($changes['new'])) {
            return 0; // Pas de changement réel
        }

        return self::log(self::ACTION_UPDATE, $entityType, $entityId, $changes['old'], $changes['new']);
    }

    /**
     * Raccourci pour logger une suppression
     */
    public static function logDelete(string $entityType, int $entityId, ?array $data = null): int
    {
        return self::log(self::ACTION_DELETE, $entityType, $entityId, $data);
    }

    /**
     * Raccourci pour logger un changement de statut
     */
    public static function logStatusChange(
        string $entityType,
        int $entityId,
        string $oldStatus,
        string $newStatus,
        ?array $metadata = null
    ): int {
        return self::log(
            self::ACTION_STATUS_CHANGE,
            $entityType,
            $entityId,
            ['status' => $oldStatus],
            ['status' => $newStatus],
            $metadata
        );
    }

    /**
     * Raccourci pour logger une connexion
     */
    public static function logLogin(int $userId, bool $success = true, ?string $reason = null): int
    {
        return self::log(
            $success ? self::ACTION_LOGIN : self::ACTION_LOGIN_FAILED,
            self::ENTITY_USER,
            $userId,
            null,
            null,
            $reason ? ['reason' => $reason] : null
        );
    }

    /**
     * Raccourci pour logger une déconnexion
     */
    public static function logLogout(int $userId): int
    {
        return self::log(self::ACTION_LOGOUT, self::ENTITY_USER, $userId);
    }

    /**
     * Récupère les logs pour un utilisateur
     */
    public static function getLogsForUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return Database::fetchAll(
            'SELECT * FROM audit_logs
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?',
            [$userId, $limit, $offset]
        );
    }

    /**
     * Récupère les logs pour une entité
     */
    public static function getLogsForEntity(string $entityType, int $entityId, int $limit = 50): array
    {
        return Database::fetchAll(
            'SELECT al.*, u.name as user_name, u.email as user_email
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.entity_type = ? AND al.entity_id = ?
             ORDER BY al.created_at DESC
             LIMIT ?',
            [$entityType, $entityId, $limit]
        );
    }

    /**
     * Recherche dans les logs
     */
    public static function search(array $filters, int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT al.*, u.name as user_name, u.email as user_email
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= ' AND al.user_id = ?';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= ' AND al.action = ?';
            $params[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $sql .= ' AND al.entity_type = ?';
            $params[] = $filters['entity_type'];
        }

        if (!empty($filters['entity_id'])) {
            $sql .= ' AND al.entity_id = ?';
            $params[] = $filters['entity_id'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND al.created_at >= ?';
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= ' AND al.created_at <= ?';
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['ip_address'])) {
            $sql .= ' AND al.ip_address = ?';
            $params[] = $filters['ip_address'];
        }

        $sql .= ' ORDER BY al.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        return Database::fetchAll($sql, $params);
    }

    /**
     * Compte les logs selon les filtres
     */
    public static function count(array $filters = []): int
    {
        $sql = 'SELECT COUNT(*) as count FROM audit_logs WHERE 1=1';
        $params = [];

        if (!empty($filters['user_id'])) {
            $sql .= ' AND user_id = ?';
            $params[] = $filters['user_id'];
        }

        if (!empty($filters['action'])) {
            $sql .= ' AND action = ?';
            $params[] = $filters['action'];
        }

        if (!empty($filters['entity_type'])) {
            $sql .= ' AND entity_type = ?';
            $params[] = $filters['entity_type'];
        }

        $result = Database::fetch($sql, $params);

        return (int) ($result['count'] ?? 0);
    }

    /**
     * Nettoie les anciens logs
     */
    public static function cleanup(int $daysToKeep = 365): int
    {
        $result = Database::query(
            'DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)',
            [$daysToKeep]
        );

        return $result->rowCount();
    }

    /**
     * Exporte les logs au format CSV
     */
    public static function exportToCsv(array $filters, string $filename): void
    {
        $logs = self::search($filters, 10000, 0);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // BOM UTF-8 pour Excel
        fwrite($output, "\xEF\xBB\xBF");

        // En-têtes
        fputcsv($output, [
            'Date',
            'Utilisateur',
            'Action',
            'Type entité',
            'ID entité',
            'Adresse IP',
            'Anciennes valeurs',
            'Nouvelles valeurs',
        ], ';');

        foreach ($logs as $log) {
            fputcsv($output, [
                $log['created_at'],
                $log['user_name'] ?? $log['user_id'] ?? 'Système',
                $log['action'],
                $log['entity_type'],
                $log['entity_id'] ?? '',
                $log['ip_address'] ?? '',
                $log['old_values'] ?? '',
                $log['new_values'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Filtre les données sensibles des logs
     */
    private static function filterSensitiveData(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sensitiveFields = [
            'password',
            'password_hash',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'reset_token',
            'api_token',
            'token',
            'secret',
            'bank_iban',
            'bank_bic',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }

    /**
     * Calcule les différences entre deux jeux de données
     */
    private static function computeChanges(array $old, array $new): array
    {
        $oldChanges = [];
        $newChanges = [];

        foreach ($new as $key => $value) {
            if (!array_key_exists($key, $old) || $old[$key] !== $value) {
                $oldChanges[$key] = $old[$key] ?? null;
                $newChanges[$key] = $value;
            }
        }

        // Champs supprimés
        foreach ($old as $key => $value) {
            if (!array_key_exists($key, $new)) {
                $oldChanges[$key] = $value;
                $newChanges[$key] = null;
            }
        }

        return ['old' => $oldChanges, 'new' => $newChanges];
    }

    /**
     * Récupère l'IP du client
     */
    private static function getClientIp(): ?string
    {
        // Headers de proxy de confiance
        $headers = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // X-Forwarded-For peut contenir plusieurs IPs
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return null;
    }
}
