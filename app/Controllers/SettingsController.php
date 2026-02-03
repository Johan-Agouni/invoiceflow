<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Models\Settings;
use App\Models\User;

class SettingsController extends Controller
{
    public function index(): void
    {
        $settings = Settings::getForUser($this->userId());
        $user = User::find($this->userId());

        $this->view('settings.index', [
            'settings' => $settings,
            'user' => $user,
            'csrf_token' => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function updateCompany(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée.');
            $this->redirect('/settings');
        }

        Settings::updateForUser($this->userId(), [
            'company_name' => trim($this->input('company_name', '')),
            'company_address' => trim($this->input('company_address', '')),
            'company_postal_code' => trim($this->input('company_postal_code', '')),
            'company_city' => trim($this->input('company_city', '')),
            'company_country' => trim($this->input('company_country', 'France')),
            'company_email' => filter_var($this->input('company_email'), FILTER_SANITIZE_EMAIL),
            'company_phone' => trim($this->input('company_phone', '')),
            'company_siret' => trim($this->input('company_siret', '')),
            'company_vat_number' => trim($this->input('company_vat_number', '')),
        ]);

        $this->flash('success', 'Informations entreprise mises à jour.');
        $this->redirect('/settings');
    }

    public function updateInvoice(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée.');
            $this->redirect('/settings');
        }

        Settings::updateForUser($this->userId(), [
            'default_vat_rate' => (float) $this->input('default_vat_rate', 20),
            'payment_terms' => (int) $this->input('payment_terms', 30),
            'invoice_footer' => trim($this->input('invoice_footer', '')),
        ]);

        $this->flash('success', 'Paramètres de facturation mis à jour.');
        $this->redirect('/settings');
    }

    public function updateBank(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée.');
            $this->redirect('/settings');
        }

        Settings::updateForUser($this->userId(), [
            'bank_name' => trim($this->input('bank_name', '')),
            'bank_iban' => trim($this->input('bank_iban', '')),
            'bank_bic' => trim($this->input('bank_bic', '')),
        ]);

        $this->flash('success', 'Coordonnées bancaires mises à jour.');
        $this->redirect('/settings');
    }

    public function updateProfile(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée.');
            $this->redirect('/settings');
        }

        $name = trim($this->input('name', ''));
        $email = filter_var($this->input('email'), FILTER_SANITIZE_EMAIL);

        if (strlen($name) < 2) {
            $this->flash('error', 'Le nom doit contenir au moins 2 caractères.');
            $this->redirect('/settings');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Email invalide.');
            $this->redirect('/settings');
        }

        // Check if email is already used by another user
        $existingUser = User::findByEmail($email);
        if ($existingUser && $existingUser['id'] !== $this->userId()) {
            $this->flash('error', 'Cette adresse email est déjà utilisée.');
            $this->redirect('/settings');
        }

        User::update($this->userId(), [
            'name' => $name,
            'email' => $email,
        ]);

        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;

        $this->flash('success', 'Profil mis à jour.');
        $this->redirect('/settings');
    }

    public function updatePassword(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée.');
            $this->redirect('/settings');
        }

        $currentPassword = $this->input('current_password');
        $newPassword = $this->input('new_password');
        $confirmPassword = $this->input('new_password_confirmation');

        $user = User::find($this->userId());

        if (!User::verifyPassword($currentPassword, $user['password'])) {
            $this->flash('error', 'Mot de passe actuel incorrect.');
            $this->redirect('/settings');
        }

        if (strlen($newPassword) < 8) {
            $this->flash('error', 'Le nouveau mot de passe doit contenir au moins 8 caractères.');
            $this->redirect('/settings');
        }

        if ($newPassword !== $confirmPassword) {
            $this->flash('error', 'Les mots de passe ne correspondent pas.');
            $this->redirect('/settings');
        }

        User::updatePassword($this->userId(), $newPassword);

        $this->flash('success', 'Mot de passe modifié.');
        $this->redirect('/settings');
    }

    public function uploadLogo(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée.');
            $this->redirect('/settings');
        }

        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Erreur lors de l\'upload du logo.');
            $this->redirect('/settings');
        }

        $file = $_FILES['logo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB

        if (!in_array($file['type'], $allowedTypes)) {
            $this->flash('error', 'Format non supporté. Utilisez JPG, PNG ou GIF.');
            $this->redirect('/settings');
        }

        if ($file['size'] > $maxSize) {
            $this->flash('error', 'Le fichier est trop volumineux (max 2MB).');
            $this->redirect('/settings');
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'logo_' . $this->userId() . '_' . time() . '.' . $extension;
        $destination = __DIR__ . '/../../public/uploads/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            $this->flash('error', 'Erreur lors de l\'enregistrement du logo.');
            $this->redirect('/settings');
        }

        Settings::updateForUser($this->userId(), [
            'company_logo' => '/uploads/' . $filename,
        ]);

        $this->flash('success', 'Logo mis à jour.');
        $this->redirect('/settings');
    }
}
