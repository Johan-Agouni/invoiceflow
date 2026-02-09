<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controller;
use App\Models\Client;

class ClientController extends Controller
{
    public function index(): void
    {
        $clients = Client::getWithStats($this->userId());

        $this->view('clients.index', [
            'clients' => $clients,
            'flash' => $this->getFlash(),
        ]);
    }

    public function create(): void
    {
        $this->view('clients.create', [
            'csrf_token' => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function store(): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée. Veuillez réessayer.');
            $this->redirect('/clients/create');
        }

        $data = $this->validateClientData();

        if ($data === null) {
            $this->redirect('/clients/create');

            return;
        }

        $data['user_id'] = $this->userId();

        Client::create($data);

        $this->flash('success', 'Client créé avec succès.');
        $this->redirect('/clients');
    }

    public function show(string $id): void
    {
        $client = Client::findForUser((int) $id, $this->userId());

        if (!$client) {
            $this->flash('error', 'Client non trouvé.');
            $this->redirect('/clients');
        }

        $this->view('clients.show', [
            'client' => $client,
            'flash' => $this->getFlash(),
        ]);
    }

    public function edit(string $id): void
    {
        $client = Client::findForUser((int) $id, $this->userId());

        if (!$client) {
            $this->flash('error', 'Client non trouvé.');
            $this->redirect('/clients');
        }

        $this->view('clients.edit', [
            'client' => $client,
            'csrf_token' => $this->csrfToken(),
            'flash' => $this->getFlash(),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->validateCsrf()) {
            $this->flash('error', 'Session expirée. Veuillez réessayer.');
            $this->redirect("/clients/{$id}/edit");
        }

        $client = Client::findForUser((int) $id, $this->userId());

        if (!$client) {
            $this->flash('error', 'Client non trouvé.');
            $this->redirect('/clients');
        }

        $data = $this->validateClientData();

        if ($data === null) {
            $this->redirect("/clients/{$id}/edit");

            return;
        }

        Client::update((int) $id, $data);

        $this->flash('success', 'Client mis à jour avec succès.');
        $this->redirect('/clients');
    }

    public function destroy(string $id): void
    {
        $client = Client::findForUser((int) $id, $this->userId());

        if (!$client) {
            $this->flash('error', 'Client non trouvé.');
            $this->redirect('/clients');
        }

        Client::delete((int) $id);

        $this->flash('success', 'Client supprimé avec succès.');
        $this->redirect('/clients');
    }

    private function validateClientData(): ?array
    {
        $companyName = trim($this->input('company_name', ''));
        $contactName = trim($this->input('contact_name', ''));
        $email = filter_var($this->input('email'), FILTER_SANITIZE_EMAIL);

        if (empty($companyName)) {
            $this->flash('error', 'Le nom de l\'entreprise est obligatoire.');

            return null;
        }

        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Adresse email invalide.');

            return null;
        }

        return [
            'company_name' => $companyName,
            'contact_name' => $contactName,
            'email' => $email,
            'phone' => trim($this->input('phone', '')),
            'address' => trim($this->input('address', '')),
            'postal_code' => trim($this->input('postal_code', '')),
            'city' => trim($this->input('city', '')),
            'country' => trim($this->input('country', 'France')),
            'vat_number' => trim($this->input('vat_number', '')),
            'notes' => trim($this->input('notes', '')),
        ];
    }
}
