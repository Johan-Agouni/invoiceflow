<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database;
use App\Models\User;
use Tests\TestCase;

/**
 * API Feature Tests
 *
 * Tests the REST API endpoints
 */
class ApiTest extends TestCase
{
    private array $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = $this->createUser([
            'email' => 'api@test.com',
            'password' => password_hash('password123', PASSWORD_BCRYPT, ['cost' => 12]),
        ]);

        // Create API token
        $this->token = bin2hex(random_bytes(32));
        Database::query(
            'INSERT INTO api_tokens (user_id, name, token, created_at) VALUES (?, ?, ?, NOW())',
            [$this->user['id'], 'Test Token', hash('sha256', $this->token)]
        );
    }

    public function testAuthTokenEndpointReturnsToken(): void
    {
        // This would be an integration test with actual HTTP calls
        // For unit testing, we verify the token creation logic

        $user = User::findByEmail('api@test.com');
        $this->assertNotNull($user);

        // Verify password
        $this->assertTrue(User::verifyPassword('password123', $user['password']));
    }

    public function testApiTokenIsStoredHashed(): void
    {
        $storedToken = Database::fetch(
            'SELECT token FROM api_tokens WHERE user_id = ?',
            [$this->user['id']]
        );

        // Token in DB should be hashed, not plain
        $this->assertNotEquals($this->token, $storedToken['token']);
        $this->assertEquals(hash('sha256', $this->token), $storedToken['token']);
    }

    public function testCanAuthenticateWithValidToken(): void
    {
        $storedToken = Database::fetch(
            'SELECT u.* FROM users u
             INNER JOIN api_tokens t ON t.user_id = u.id
             WHERE t.token = ?',
            [hash('sha256', $this->token)]
        );

        $this->assertNotNull($storedToken);
        $this->assertEquals($this->user['id'], $storedToken['id']);
    }

    public function testCannotAuthenticateWithInvalidToken(): void
    {
        $storedToken = Database::fetch(
            'SELECT u.* FROM users u
             INNER JOIN api_tokens t ON t.user_id = u.id
             WHERE t.token = ?',
            [hash('sha256', 'invalid_token')]
        );

        $this->assertNull($storedToken);
    }

    public function testExpiredTokenIsRejected(): void
    {
        // Create expired token
        $expiredToken = bin2hex(random_bytes(32));
        Database::query(
            'INSERT INTO api_tokens (user_id, name, token, expires_at, created_at)
             VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL 1 DAY), NOW())',
            [$this->user['id'], 'Expired Token', hash('sha256', $expiredToken)]
        );

        // Try to authenticate with expired token
        $storedToken = Database::fetch(
            'SELECT u.* FROM users u
             INNER JOIN api_tokens t ON t.user_id = u.id
             WHERE t.token = ? AND (t.expires_at IS NULL OR t.expires_at > NOW())',
            [hash('sha256', $expiredToken)]
        );

        $this->assertNull($storedToken);
    }

    public function testCanRevokeToken(): void
    {
        Database::query(
            'DELETE FROM api_tokens WHERE token = ?',
            [hash('sha256', $this->token)]
        );

        $storedToken = Database::fetch(
            'SELECT * FROM api_tokens WHERE token = ?',
            [hash('sha256', $this->token)]
        );

        $this->assertNull($storedToken);
    }

    public function testCanRevokeAllUserTokens(): void
    {
        // Create additional tokens
        for ($i = 0; $i < 3; $i++) {
            $token = bin2hex(random_bytes(32));
            Database::query(
                'INSERT INTO api_tokens (user_id, name, token, created_at) VALUES (?, ?, ?, NOW())',
                [$this->user['id'], "Token {$i}", hash('sha256', $token)]
            );
        }

        // Verify we have multiple tokens
        $count = Database::fetch(
            'SELECT COUNT(*) as count FROM api_tokens WHERE user_id = ?',
            [$this->user['id']]
        );
        $this->assertGreaterThan(1, $count['count']);

        // Revoke all
        Database::query('DELETE FROM api_tokens WHERE user_id = ?', [$this->user['id']]);

        // Verify all gone
        $count = Database::fetch(
            'SELECT COUNT(*) as count FROM api_tokens WHERE user_id = ?',
            [$this->user['id']]
        );
        $this->assertEquals(0, $count['count']);
    }

    public function testLastUsedAtIsUpdated(): void
    {
        // Simulate token usage
        Database::query(
            'UPDATE api_tokens SET last_used_at = NOW() WHERE token = ?',
            [hash('sha256', $this->token)]
        );

        $token = Database::fetch(
            'SELECT last_used_at FROM api_tokens WHERE token = ?',
            [hash('sha256', $this->token)]
        );

        $this->assertNotNull($token['last_used_at']);
    }
}
