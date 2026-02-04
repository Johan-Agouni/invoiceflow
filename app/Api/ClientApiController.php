<?php

declare(strict_types=1);

namespace App\Api;

use App\Models\Client;

/**
 * Client API Controller
 *
 * @api
 * @tag Clients
 */
class ClientApiController extends ApiController
{
    /**
     * List all clients
     *
     * @route GET /api/v1/clients
     * @param string $search Search by company name or email
     * @param int $page Page number (default: 1)
     * @param int $per_page Items per page (default: 20, max: 100)
     * @response 200 {"success": true, "data": [...], "meta": {...}}
     */
    public function index(): void
    {
        $this->requireAuth();

        $page = max(1, (int) $this->query('page', 1));
        $perPage = min(100, max(1, (int) $this->query('per_page', 20)));
        $search = $this->query('search', '');

        if ($search) {
            $clients = Client::search($this->userId(), $search);
            $total = count($clients);
        } else {
            $clients = Client::allForUser($this->userId());
            $total = count($clients);
        }

        // Manual pagination
        $offset = ($page - 1) * $perPage;
        $paginatedClients = array_slice($clients, $offset, $perPage);

        // Format response
        $data = array_map([$this, 'formatClient'], $paginatedClients);

        $this->json($this->paginate($data, $total, $page, $perPage));
    }

    /**
     * Get a single client
     *
     * @route GET /api/v1/clients/{id}
     * @param int $id Client ID
     * @response 200 {"success": true, "data": {...}}
     * @response 404 {"success": false, "message": "Client not found"}
     */
    public function show(int $id): void
    {
        $this->requireAuth();

        $client = Client::findForUser($id, $this->userId());

        if (!$client) {
            $this->notFound('Client not found');
        }

        // Get client stats
        $stats = Client::getWithStats($id, $this->userId());

        $this->success($this->formatClient($client, $stats));
    }

    /**
     * Create a new client
     *
     * @route POST /api/v1/clients
     * @body {"company_name": "string", "contact_name": "string", "email": "string", ...}
     * @response 201 {"success": true, "data": {...}}
     * @response 422 {"success": false, "errors": {...}}
     */
    public function store(): void
    {
        $this->requireAuth();

        $data = $this->validate([
            'company_name' => 'required|string|min:2|max:255',
            'contact_name' => 'string|max:255',
            'email' => 'email|max:255',
            'phone' => 'string|max:50',
            'address' => 'string|max:255',
            'postal_code' => 'string|max:20',
            'city' => 'string|max:100',
            'country' => 'string|max:100',
            'vat_number' => 'string|max:30',
            'notes' => 'string',
        ]);

        $data['user_id'] = $this->userId();

        $id = Client::create($data);
        $client = Client::find($id);

        $this->created($this->formatClient($client), 'Client created successfully');
    }

    /**
     * Update a client
     *
     * @route PUT /api/v1/clients/{id}
     * @param int $id Client ID
     * @body {"company_name": "string", ...}
     * @response 200 {"success": true, "data": {...}}
     * @response 404 {"success": false, "message": "Client not found"}
     */
    public function update(int $id): void
    {
        $this->requireAuth();

        $client = Client::findForUser($id, $this->userId());

        if (!$client) {
            $this->notFound('Client not found');
        }

        $data = $this->validate([
            'company_name' => 'string|min:2|max:255',
            'contact_name' => 'string|max:255',
            'email' => 'email|max:255',
            'phone' => 'string|max:50',
            'address' => 'string|max:255',
            'postal_code' => 'string|max:20',
            'city' => 'string|max:100',
            'country' => 'string|max:100',
            'vat_number' => 'string|max:30',
            'notes' => 'string',
        ]);

        // Remove null values
        $data = array_filter($data, fn($v) => $v !== null);

        Client::update($id, $data);
        $client = Client::find($id);

        $this->success($this->formatClient($client), 'Client updated successfully');
    }

    /**
     * Delete a client
     *
     * @route DELETE /api/v1/clients/{id}
     * @param int $id Client ID
     * @response 200 {"success": true, "message": "Client deleted"}
     * @response 404 {"success": false, "message": "Client not found"}
     */
    public function destroy(int $id): void
    {
        $this->requireAuth();

        $client = Client::findForUser($id, $this->userId());

        if (!$client) {
            $this->notFound('Client not found');
        }

        // Check if client has invoices
        $stats = Client::getWithStats($id, $this->userId());
        if ($stats && $stats['total_invoices'] > 0) {
            $this->error('Cannot delete client with existing invoices', 409);
        }

        Client::delete($id);

        $this->success(null, 'Client deleted successfully');
    }

    /**
     * Format client for API response
     */
    private function formatClient(array $client, ?array $stats = null): array
    {
        $formatted = [
            'id' => (int) $client['id'],
            'company_name' => $client['company_name'],
            'contact_name' => $client['contact_name'],
            'email' => $client['email'],
            'phone' => $client['phone'],
            'address' => [
                'street' => $client['address'],
                'postal_code' => $client['postal_code'],
                'city' => $client['city'],
                'country' => $client['country'] ?? 'France',
            ],
            'vat_number' => $client['vat_number'],
            'notes' => $client['notes'],
            'created_at' => $client['created_at'],
            'updated_at' => $client['updated_at'],
        ];

        if ($stats) {
            $formatted['stats'] = [
                'total_invoices' => (int) ($stats['total_invoices'] ?? 0),
                'total_amount' => (float) ($stats['total_amount'] ?? 0),
                'paid_amount' => (float) ($stats['paid_amount'] ?? 0),
                'pending_amount' => (float) ($stats['pending_amount'] ?? 0),
            ];
        }

        return $formatted;
    }
}
