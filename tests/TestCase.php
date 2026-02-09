<?php

declare(strict_types=1);

namespace Tests;

use App\Database;
use PHPUnit\Framework\TestCase as BaseTestCase;

/**
 * Base Test Case
 *
 * Provides common testing utilities and database helpers
 */
abstract class TestCase extends BaseTestCase
{
    protected static bool $migrated = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Run migrations once per test suite
        if (!self::$migrated) {
            $this->runMigrations();
            self::$migrated = true;
        }

        // Start transaction for test isolation
        $this->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction to restore database state
        $this->rollbackTransaction();

        parent::tearDown();
    }

    /**
     * Run database migrations for testing
     */
    protected function runMigrations(): void
    {
        $migrationsPath = __DIR__ . '/../database/migrations';
        $files = glob($migrationsPath . '/*.sql');
        sort($files);

        foreach ($files as $file) {
            $sql = file_get_contents($file);
            // Split by semicolons and execute each statement
            $statements = array_filter(array_map('trim', explode(';', $sql)));

            foreach ($statements as $statement) {
                if (!empty($statement) && !str_starts_with($statement, '--')) {
                    try {
                        Database::query($statement);
                    } catch (\Exception $e) {
                        // Ignore errors for CREATE TABLE IF NOT EXISTS, etc.
                        if (!str_contains($e->getMessage(), 'already exists')) {
                            throw $e;
                        }
                    }
                }
            }
        }
    }

    /**
     * Begin a database transaction
     */
    protected function beginTransaction(): void
    {
        Database::query('START TRANSACTION');
    }

    /**
     * Rollback the database transaction
     */
    protected function rollbackTransaction(): void
    {
        Database::query('ROLLBACK');
    }

    /**
     * Create a test user and return user data
     */
    protected function createUser(array $attributes = []): array
    {
        $defaults = [
            'name' => 'Test User',
            'email' => 'test' . uniqid() . '@example.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT),
        ];

        $data = array_merge($defaults, $attributes);

        $id = Database::insert('users', $data);

        return array_merge(['id' => $id], $data);
    }

    /**
     * Create a test client and return client data
     */
    protected function createClient(int $userId, array $attributes = []): array
    {
        $defaults = [
            'user_id' => $userId,
            'company_name' => 'Test Company ' . uniqid(),
            'email' => 'client' . uniqid() . '@example.com',
            'contact_name' => 'John Doe',
            'phone' => '0123456789',
            'address' => '123 Test Street',
            'postal_code' => '75001',
            'city' => 'Paris',
            'country' => 'France',
        ];

        $data = array_merge($defaults, $attributes);

        $id = Database::insert('clients', $data);

        return array_merge(['id' => $id], $data);
    }

    /**
     * Create a test invoice and return invoice data
     */
    protected function createInvoice(int $userId, int $clientId, array $attributes = []): array
    {
        $defaults = [
            'user_id' => $userId,
            'client_id' => $clientId,
            'number' => 'FAC-TEST-' . uniqid(),
            'status' => 'draft',
            'issue_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => 1000.00,
            'vat_amount' => 200.00,
            'total_amount' => 1200.00,
        ];

        $data = array_merge($defaults, $attributes);

        $id = Database::insert('invoices', $data);

        return array_merge(['id' => $id], $data);
    }

    /**
     * Create a test quote and return quote data
     */
    protected function createQuote(int $userId, int $clientId, array $attributes = []): array
    {
        $defaults = [
            'user_id' => $userId,
            'client_id' => $clientId,
            'number' => 'DEV-TEST-' . uniqid(),
            'status' => 'draft',
            'issue_date' => date('Y-m-d'),
            'valid_until' => date('Y-m-d', strtotime('+30 days')),
            'subtotal' => 1000.00,
            'vat_amount' => 200.00,
            'total_amount' => 1200.00,
        ];

        $data = array_merge($defaults, $attributes);

        $id = Database::insert('quotes', $data);

        return array_merge(['id' => $id], $data);
    }

    /**
     * Assert that an array has all expected keys
     */
    protected function assertArrayHasKeys(array $keys, array $array): void
    {
        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $array, "Missing key: {$key}");
        }
    }

    /**
     * Assert that a database record exists
     */
    protected function assertDatabaseHas(string $table, array $conditions): void
    {
        $where = implode(' AND ', array_map(fn ($k) => "{$k} = ?", array_keys($conditions)));
        $result = Database::fetch("SELECT * FROM {$table} WHERE {$where}", array_values($conditions));

        $this->assertNotNull($result, "Failed asserting that table [{$table}] has matching record");
    }

    /**
     * Assert that a database record does not exist
     */
    protected function assertDatabaseMissing(string $table, array $conditions): void
    {
        $where = implode(' AND ', array_map(fn ($k) => "{$k} = ?", array_keys($conditions)));
        $result = Database::fetch("SELECT * FROM {$table} WHERE {$where}", array_values($conditions));

        $this->assertNull($result, "Failed asserting that table [{$table}] is missing record");
    }
}
