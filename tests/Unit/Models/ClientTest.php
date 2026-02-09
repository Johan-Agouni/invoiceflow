<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Client;
use Tests\TestCase;

/**
 * Client Model Tests
 *
 * @covers \App\Models\Client
 */
class ClientTest extends TestCase
{
    private array $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->createUser();
    }

    public function testCanCreateClient(): void
    {
        $clientData = [
            'user_id' => $this->user['id'],
            'company_name' => 'Acme Corp',
            'email' => 'contact@acme.com',
        ];

        $clientId = Client::create($clientData);

        $this->assertIsInt($clientId);
        $this->assertDatabaseHas('clients', [
            'id' => $clientId,
            'company_name' => 'Acme Corp',
        ]);
    }

    public function testCanGetAllClientsForUser(): void
    {
        // Create clients for our user
        $this->createClient($this->user['id'], ['company_name' => 'Client A']);
        $this->createClient($this->user['id'], ['company_name' => 'Client B']);

        // Create client for another user (should not be included)
        $otherUser = $this->createUser();
        $this->createClient($otherUser['id'], ['company_name' => 'Other Client']);

        $clients = Client::allForUser($this->user['id']);

        $this->assertCount(2, $clients);
        $companyNames = array_column($clients, 'company_name');
        $this->assertContains('Client A', $companyNames);
        $this->assertContains('Client B', $companyNames);
        $this->assertNotContains('Other Client', $companyNames);
    }

    public function testCanFindClientForUser(): void
    {
        $client = $this->createClient($this->user['id']);

        $found = Client::findForUser($client['id'], $this->user['id']);

        $this->assertNotNull($found);
        $this->assertEquals($client['id'], $found['id']);
    }

    public function testCannotFindClientBelongingToOtherUser(): void
    {
        $otherUser = $this->createUser();
        $client = $this->createClient($otherUser['id']);

        $found = Client::findForUser($client['id'], $this->user['id']);

        $this->assertNull($found);
    }

    public function testCanSearchClients(): void
    {
        $this->createClient($this->user['id'], ['company_name' => 'Tech Solutions']);
        $this->createClient($this->user['id'], ['company_name' => 'Marketing Pro']);
        $this->createClient($this->user['id'], ['email' => 'tech@example.com', 'company_name' => 'Another']);

        // Search by company name
        $results = Client::search($this->user['id'], 'Tech');
        $this->assertCount(2, $results);

        // Search by email
        $results = Client::search($this->user['id'], 'tech@example');
        $this->assertCount(1, $results);
    }

    public function testCanUpdateClient(): void
    {
        $client = $this->createClient($this->user['id'], ['company_name' => 'Old Name']);

        Client::update($client['id'], ['company_name' => 'New Name']);

        $updated = Client::find($client['id']);
        $this->assertEquals('New Name', $updated['company_name']);
    }

    public function testCanDeleteClient(): void
    {
        $client = $this->createClient($this->user['id']);

        Client::delete($client['id']);

        $this->assertDatabaseMissing('clients', ['id' => $client['id']]);
    }

    public function testCanCountClientsForUser(): void
    {
        $this->createClient($this->user['id']);
        $this->createClient($this->user['id']);
        $this->createClient($this->user['id']);

        $count = Client::countForUser($this->user['id']);

        $this->assertEquals(3, $count);
    }
}
