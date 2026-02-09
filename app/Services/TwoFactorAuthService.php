<?php

declare(strict_types=1);

namespace App\Services;

use App\Database;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

/**
 * Service de gestion de l'authentification à deux facteurs (2FA)
 *
 * Utilise TOTP (Time-based One-Time Password) compatible avec
 * Google Authenticator, Authy, 1Password, etc.
 */
class TwoFactorAuthService
{
    private Google2FA $google2fa;

    private string $issuer;

    private int $trustedDeviceDays;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
        $this->issuer = $_ENV['APP_NAME'] ?? 'InvoiceFlow';
        $this->trustedDeviceDays = (int) ($_ENV['TWO_FACTOR_TRUSTED_DAYS'] ?? 30);
    }

    /**
     * Génère un nouveau secret 2FA pour un utilisateur
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * Génère le QR code SVG pour l'application d'authentification
     */
    public function generateQrCode(string $email, string $secret): string
    {
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $this->issuer,
            $email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        return $writer->writeString($qrCodeUrl);
    }

    /**
     * Vérifie un code TOTP
     */
    public function verifyCode(string $secret, string $code): bool
    {
        // Tolérance de 1 période (30 secondes) avant/après
        return $this->google2fa->verifyKey($secret, $code, 1);
    }

    /**
     * Active le 2FA pour un utilisateur
     */
    public function enable(int $userId, string $secret): bool
    {
        $recoveryCodes = $this->generateRecoveryCodes();

        $result = Database::query(
            'UPDATE users SET
                two_factor_secret = ?,
                two_factor_enabled = 1,
                two_factor_confirmed_at = NOW(),
                two_factor_recovery_codes = ?
             WHERE id = ?',
            [
                $this->encryptSecret($secret),
                json_encode($recoveryCodes),
                $userId,
            ]
        );

        return $result->rowCount() > 0;
    }

    /**
     * Désactive le 2FA pour un utilisateur
     */
    public function disable(int $userId): bool
    {
        // Supprimer aussi les appareils de confiance
        Database::query(
            'DELETE FROM two_factor_trusted_devices WHERE user_id = ?',
            [$userId]
        );

        $result = Database::query(
            'UPDATE users SET
                two_factor_secret = NULL,
                two_factor_enabled = 0,
                two_factor_confirmed_at = NULL,
                two_factor_recovery_codes = NULL
             WHERE id = ?',
            [$userId]
        );

        return $result->rowCount() > 0;
    }

    /**
     * Vérifie si un utilisateur a le 2FA activé
     */
    public function isEnabled(int $userId): bool
    {
        $user = Database::fetch(
            'SELECT two_factor_enabled FROM users WHERE id = ?',
            [$userId]
        );

        return $user && (bool) $user['two_factor_enabled'];
    }

    /**
     * Récupère le secret 2FA d'un utilisateur
     */
    public function getSecret(int $userId): ?string
    {
        $user = Database::fetch(
            'SELECT two_factor_secret FROM users WHERE id = ?',
            [$userId]
        );

        if (!$user || !$user['two_factor_secret']) {
            return null;
        }

        return $this->decryptSecret($user['two_factor_secret']);
    }

    /**
     * Génère des codes de récupération
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];

        for ($i = 0; $i < $count; $i++) {
            // Format: XXXX-XXXX-XXXX
            $codes[] = sprintf(
                '%s-%s-%s',
                strtoupper(bin2hex(random_bytes(2))),
                strtoupper(bin2hex(random_bytes(2))),
                strtoupper(bin2hex(random_bytes(2)))
            );
        }

        return $codes;
    }

    /**
     * Vérifie et consomme un code de récupération
     */
    public function useRecoveryCode(int $userId, string $code): bool
    {
        $user = Database::fetch(
            'SELECT two_factor_recovery_codes FROM users WHERE id = ?',
            [$userId]
        );

        if (!$user || !$user['two_factor_recovery_codes']) {
            return false;
        }

        $codes = json_decode($user['two_factor_recovery_codes'], true);

        if (!is_array($codes)) {
            return false;
        }

        // Recherche le code (insensible à la casse)
        $normalizedCode = strtoupper(trim($code));
        $key = array_search($normalizedCode, array_map('strtoupper', $codes), true);

        if ($key === false) {
            return false;
        }

        // Supprime le code utilisé
        unset($codes[$key]);
        $codes = array_values($codes);

        // Met à jour les codes restants
        Database::query(
            'UPDATE users SET two_factor_recovery_codes = ? WHERE id = ?',
            [json_encode($codes), $userId]
        );

        return true;
    }

    /**
     * Récupère les codes de récupération restants
     */
    public function getRecoveryCodes(int $userId): array
    {
        $user = Database::fetch(
            'SELECT two_factor_recovery_codes FROM users WHERE id = ?',
            [$userId]
        );

        if (!$user || !$user['two_factor_recovery_codes']) {
            return [];
        }

        return json_decode($user['two_factor_recovery_codes'], true) ?: [];
    }

    /**
     * Régénère les codes de récupération
     */
    public function regenerateRecoveryCodes(int $userId): array
    {
        $codes = $this->generateRecoveryCodes();

        Database::query(
            'UPDATE users SET two_factor_recovery_codes = ? WHERE id = ?',
            [json_encode($codes), $userId]
        );

        return $codes;
    }

    /**
     * Ajoute un appareil de confiance
     */
    public function trustDevice(int $userId, ?string $deviceName = null): string
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->trustedDeviceDays} days"));

        Database::query(
            'INSERT INTO two_factor_trusted_devices
                (user_id, device_token, device_name, ip_address, user_agent, expires_at)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                $userId,
                hash('sha256', $token),
                $deviceName ?? $this->detectDeviceName(),
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 512),
                $expiresAt,
            ]
        );

        return $token;
    }

    /**
     * Vérifie si un appareil est de confiance
     */
    public function isTrustedDevice(int $userId, string $token): bool
    {
        $hashedToken = hash('sha256', $token);

        $device = Database::fetch(
            'SELECT id FROM two_factor_trusted_devices
             WHERE user_id = ? AND device_token = ? AND expires_at > NOW()',
            [$userId, $hashedToken]
        );

        if ($device) {
            // Met à jour le last_used_at
            Database::query(
                'UPDATE two_factor_trusted_devices SET last_used_at = NOW() WHERE id = ?',
                [$device['id']]
            );

            return true;
        }

        return false;
    }

    /**
     * Révoque un appareil de confiance
     */
    public function revokeTrustedDevice(int $userId, int $deviceId): bool
    {
        $result = Database::query(
            'DELETE FROM two_factor_trusted_devices WHERE id = ? AND user_id = ?',
            [$deviceId, $userId]
        );

        return $result->rowCount() > 0;
    }

    /**
     * Révoque tous les appareils de confiance
     */
    public function revokeAllTrustedDevices(int $userId): int
    {
        $result = Database::query(
            'DELETE FROM two_factor_trusted_devices WHERE user_id = ?',
            [$userId]
        );

        return $result->rowCount();
    }

    /**
     * Liste les appareils de confiance d'un utilisateur
     */
    public function getTrustedDevices(int $userId): array
    {
        return Database::fetchAll(
            'SELECT id, device_name, ip_address, last_used_at, created_at, expires_at
             FROM two_factor_trusted_devices
             WHERE user_id = ? AND expires_at > NOW()
             ORDER BY last_used_at DESC',
            [$userId]
        );
    }

    /**
     * Nettoie les appareils expirés
     */
    public function cleanupExpiredDevices(): int
    {
        $result = Database::query(
            'DELETE FROM two_factor_trusted_devices WHERE expires_at < NOW()'
        );

        return $result->rowCount();
    }

    /**
     * Chiffre le secret 2FA pour le stockage
     */
    private function encryptSecret(string $secret): string
    {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($secret, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Déchiffre le secret 2FA
     */
    private function decryptSecret(string $encrypted): string
    {
        $key = $this->getEncryptionKey();
        $data = base64_decode($encrypted, true);
        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);

        return openssl_decrypt($ciphertext, 'AES-256-CBC', $key, 0, $iv);
    }

    /**
     * Récupère la clé de chiffrement
     */
    private function getEncryptionKey(): string
    {
        $key = $_ENV['APP_KEY'] ?? '';

        if (empty($key)) {
            throw new \RuntimeException('APP_KEY is not set in environment');
        }

        return hash('sha256', $key, true);
    }

    /**
     * Détecte le nom de l'appareil à partir du User-Agent
     */
    private function detectDeviceName(): string
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        // Détection basique
        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $os = 'Mac';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'iPhone')) {
            $os = 'iPhone';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } else {
            $os = 'Unknown';
        }

        if (str_contains($userAgent, 'Chrome')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edge')) {
            $browser = 'Edge';
        } else {
            $browser = 'Browser';
        }

        return "{$browser} on {$os}";
    }
}
