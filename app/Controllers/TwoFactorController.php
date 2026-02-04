<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Services\TwoFactorAuthService;
use App\Models\User;

/**
 * Contrôleur pour la gestion de l'authentification à deux facteurs
 */
class TwoFactorController extends Controller
{
    private TwoFactorAuthService $twoFactorService;

    public function __construct()
    {
        $this->twoFactorService = new TwoFactorAuthService();
    }

    /**
     * Affiche la page de configuration 2FA
     */
    public function index(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }

        $userId = $this->userId();
        $isEnabled = $this->twoFactorService->isEnabled($userId);
        $trustedDevices = [];
        $recoveryCodesCount = 0;

        if ($isEnabled) {
            $trustedDevices = $this->twoFactorService->getTrustedDevices($userId);
            $recoveryCodesCount = count($this->twoFactorService->getRecoveryCodes($userId));
        }

        $this->view('settings.two-factor', [
            'isEnabled' => $isEnabled,
            'trustedDevices' => $trustedDevices,
            'recoveryCodesCount' => $recoveryCodesCount,
            'flash' => $this->getFlash(),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    /**
     * Affiche le formulaire d'activation 2FA avec QR code
     */
    public function setup(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }

        $userId = $this->userId();

        if ($this->twoFactorService->isEnabled($userId)) {
            $this->flash('error', 'L\'authentification à deux facteurs est déjà activée.');
            $this->redirect('/settings/two-factor');
        }

        $user = User::find($userId);
        $secret = $this->twoFactorService->generateSecret();

        // Stocker temporairement le secret en session
        $_SESSION['two_factor_setup_secret'] = $secret;

        $qrCode = $this->twoFactorService->generateQrCode($user['email'], $secret);

        $this->view('settings.two-factor-setup', [
            'secret' => $secret,
            'qrCode' => $qrCode,
            'flash' => $this->getFlash(),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    /**
     * Confirme l'activation du 2FA
     */
    public function enable(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token CSRF invalide.');
            $this->redirect('/settings/two-factor/setup');
        }

        $userId = $this->userId();
        $code = $this->input('code');
        $secret = $_SESSION['two_factor_setup_secret'] ?? null;

        if (!$secret) {
            $this->flash('error', 'Session expirée. Veuillez recommencer.');
            $this->redirect('/settings/two-factor/setup');
        }

        if (!$code || !$this->twoFactorService->verifyCode($secret, $code)) {
            $this->flash('error', 'Code invalide. Veuillez réessayer.');
            $this->redirect('/settings/two-factor/setup');
        }

        if ($this->twoFactorService->enable($userId, $secret)) {
            unset($_SESSION['two_factor_setup_secret']);

            // Récupérer les codes de récupération pour les afficher
            $recoveryCodes = $this->twoFactorService->getRecoveryCodes($userId);
            $_SESSION['show_recovery_codes'] = $recoveryCodes;

            $this->flash('success', 'L\'authentification à deux facteurs a été activée.');
            $this->redirect('/settings/two-factor/recovery-codes');
        }

        $this->flash('error', 'Une erreur est survenue. Veuillez réessayer.');
        $this->redirect('/settings/two-factor/setup');
    }

    /**
     * Affiche les codes de récupération
     */
    public function recoveryCodes(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }

        $userId = $this->userId();

        if (!$this->twoFactorService->isEnabled($userId)) {
            $this->redirect('/settings/two-factor');
        }

        // Codes à afficher après activation
        $showCodes = $_SESSION['show_recovery_codes'] ?? null;
        unset($_SESSION['show_recovery_codes']);

        $this->view('settings.two-factor-recovery', [
            'recoveryCodes' => $showCodes,
            'codesCount' => count($this->twoFactorService->getRecoveryCodes($userId)),
            'flash' => $this->getFlash(),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    /**
     * Régénère les codes de récupération
     */
    public function regenerateCodes(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token CSRF invalide.');
            $this->redirect('/settings/two-factor');
        }

        $userId = $this->userId();

        if (!$this->twoFactorService->isEnabled($userId)) {
            $this->redirect('/settings/two-factor');
        }

        $codes = $this->twoFactorService->regenerateRecoveryCodes($userId);
        $_SESSION['show_recovery_codes'] = $codes;

        $this->flash('success', 'Nouveaux codes de récupération générés.');
        $this->redirect('/settings/two-factor/recovery-codes');
    }

    /**
     * Désactive le 2FA
     */
    public function disable(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token CSRF invalide.');
            $this->redirect('/settings/two-factor');
        }

        $userId = $this->userId();
        $password = $this->input('password');

        // Vérifier le mot de passe
        $user = User::find($userId);

        if (!$user || !User::verifyPassword($password, $user['password'])) {
            $this->flash('error', 'Mot de passe incorrect.');
            $this->redirect('/settings/two-factor');
        }

        if ($this->twoFactorService->disable($userId)) {
            $this->flash('success', 'L\'authentification à deux facteurs a été désactivée.');
        } else {
            $this->flash('error', 'Une erreur est survenue.');
        }

        $this->redirect('/settings/two-factor');
    }

    /**
     * Page de vérification 2FA lors de la connexion
     */
    public function challenge(): void
    {
        if (!isset($_SESSION['two_factor_user_id'])) {
            $this->redirect('/login');
        }

        $this->view('auth.two-factor-challenge', [
            'flash' => $this->getFlash(),
            'csrf_token' => $this->csrfToken(),
        ]);
    }

    /**
     * Vérifie le code 2FA lors de la connexion
     */
    public function verify(): void
    {
        if (!isset($_SESSION['two_factor_user_id'])) {
            $this->redirect('/login');
        }

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token CSRF invalide.');
            $this->redirect('/two-factor/challenge');
        }

        $userId = $_SESSION['two_factor_user_id'];
        $code = $this->input('code');
        $trustDevice = (bool) $this->input('trust_device');

        // Essayer d'abord comme code TOTP
        $secret = $this->twoFactorService->getSecret($userId);

        if ($secret && $this->twoFactorService->verifyCode($secret, $code)) {
            $this->completeLogin($userId, $trustDevice);

            return;
        }

        // Essayer comme code de récupération
        if ($this->twoFactorService->useRecoveryCode($userId, $code)) {
            $this->flash('warning', 'Code de récupération utilisé. Pensez à régénérer vos codes.');
            $this->completeLogin($userId, false);

            return;
        }

        $this->flash('error', 'Code invalide. Veuillez réessayer.');
        $this->redirect('/two-factor/challenge');
    }

    /**
     * Révoque un appareil de confiance
     */
    public function revokeDevice(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token CSRF invalide.');
            $this->redirect('/settings/two-factor');
        }

        $userId = $this->userId();
        $deviceId = (int) $this->input('device_id');

        if ($this->twoFactorService->revokeTrustedDevice($userId, $deviceId)) {
            $this->flash('success', 'Appareil révoqué.');
        } else {
            $this->flash('error', 'Appareil non trouvé.');
        }

        $this->redirect('/settings/two-factor');
    }

    /**
     * Révoque tous les appareils de confiance
     */
    public function revokeAllDevices(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }

        if (!$this->validateCsrf()) {
            $this->flash('error', 'Token CSRF invalide.');
            $this->redirect('/settings/two-factor');
        }

        $userId = $this->userId();
        $count = $this->twoFactorService->revokeAllTrustedDevices($userId);

        $this->flash('success', "{$count} appareil(s) révoqué(s).");
        $this->redirect('/settings/two-factor');
    }

    /**
     * Finalise la connexion après vérification 2FA
     */
    private function completeLogin(int $userId, bool $trustDevice): void
    {
        // Nettoyer la session temporaire
        unset($_SESSION['two_factor_user_id']);

        // Établir la session utilisateur
        $_SESSION['user_id'] = $userId;

        // Créer un cookie d'appareil de confiance si demandé
        if ($trustDevice) {
            $token = $this->twoFactorService->trustDevice($userId);
            $expires = time() + (30 * 24 * 60 * 60); // 30 jours

            setcookie(
                'trusted_device',
                $token,
                [
                    'expires' => $expires,
                    'path' => '/',
                    'secure' => isset($_SERVER['HTTPS']),
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]
            );
        }

        $this->redirect('/dashboard');
    }
}
