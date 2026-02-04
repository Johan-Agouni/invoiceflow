<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Models\User;
use App\Models\Settings;
use App\Services\MailService;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->view('auth.login', [
            'csrf_token' => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function login(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée. Veuillez réessayer.');
            $this->redirect('/login');
        }

        $email = filter_var($this->input('email'), FILTER_SANITIZE_EMAIL);
        $password = $this->input('password');

        if (!$email || !$password) {
            $this->flash('error', 'Veuillez remplir tous les champs.');
            $this->redirect('/login');
        }

        $user = User::findByEmail($email);

        if (!$user || !User::verifyPassword($password, $user['password'])) {
            $this->flash('error', 'Email ou mot de passe incorrect.');
            $this->redirect('/login');
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];

        // Regenerate session ID for security
        session_regenerate_id(true);

        $this->redirect('/dashboard');
    }

    public function showRegister(): void
    {
        $this->view('auth.register', [
            'csrf_token' => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function register(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée. Veuillez réessayer.');
            $this->redirect('/register');
        }

        $name = trim($this->input('name', ''));
        $email = filter_var($this->input('email'), FILTER_SANITIZE_EMAIL);
        $password = $this->input('password');
        $passwordConfirm = $this->input('password_confirmation');

        // Validation
        $errors = [];

        if (strlen($name) < 2) {
            $errors[] = 'Le nom doit contenir au moins 2 caractères.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Adresse email invalide.';
        }

        if (User::findByEmail($email)) {
            $errors[] = 'Cette adresse email est déjà utilisée.';
        }

        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Les mots de passe ne correspondent pas.';
        }

        if (!empty($errors)) {
            $this->flash('error', implode('<br>', $errors));
            $this->redirect('/register');
        }

        // Create user
        $userId = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        // Create default settings
        Settings::updateForUser($userId, Settings::getDefaults($userId));

        // Auto-login
        $_SESSION['user_id'] = $userId;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        session_regenerate_id(true);

        $this->flash('success', 'Bienvenue sur InvoiceFlow ! Commencez par configurer vos informations entreprise.');
        $this->redirect('/settings');
    }

    public function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        $this->redirect('/login');
    }

    public function showForgotPassword(): void
    {
        $this->view('auth.forgot-password', [
            'csrf_token' => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function forgotPassword(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée. Veuillez réessayer.');
            $this->redirect('/forgot-password');
        }

        $email = filter_var($this->input('email'), FILTER_SANITIZE_EMAIL);
        $user = User::findByEmail($email);

        // Always show success message to prevent email enumeration
        $this->flash('success', 'Si cette adresse existe, vous recevrez un email avec les instructions.');

        if ($user) {
            $token = User::createPasswordResetToken($user['id']);

            // Send password reset email
            $mailService = new MailService();
            $sent = $mailService->sendPasswordReset($email, $user['name'], $token);

            if (!$sent) {
                error_log("Failed to send password reset email to {$email}");
            }
        }

        $this->redirect('/forgot-password');
    }

    public function showResetPassword(string $token): void
    {
        $user = User::findByResetToken($token);

        if (!$user) {
            $this->flash('error', 'Lien de réinitialisation invalide ou expiré.');
            $this->redirect('/login');
        }

        $this->view('auth.reset-password', [
            'csrf_token' => $this->csrfToken(),
            'token' => $token,
            'flash' => $this->getFlash(),
        ]);
    }

    public function resetPassword(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée. Veuillez réessayer.');
            $this->back();
        }

        $token = $this->input('token');
        $password = $this->input('password');
        $passwordConfirm = $this->input('password_confirmation');

        $user = User::findByResetToken($token);

        if (!$user) {
            $this->flash('error', 'Lien de réinitialisation invalide ou expiré.');
            $this->redirect('/login');
        }

        if (strlen($password) < 8) {
            $this->flash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
            $this->redirect("/reset-password/{$token}");
        }

        if ($password !== $passwordConfirm) {
            $this->flash('error', 'Les mots de passe ne correspondent pas.');
            $this->redirect("/reset-password/{$token}");
        }

        User::updatePassword($user['id'], $password);
        User::clearResetToken($user['id']);

        $this->flash('success', 'Mot de passe modifié avec succès. Vous pouvez maintenant vous connecter.');
        $this->redirect('/login');
    }
}
