<?php

declare(strict_types=1);

namespace App\Api;

use App\Database;
use App\Models\User;

/**
 * Authentication API Controller
 *
 * @api
 *
 * @tag Authentication
 */
class AuthApiController extends ApiController
{
    /**
     * Generate API token
     *
     * @route POST /api/v1/auth/token
     *
     * @body {"email": "user@example.com", "password": "secret"}
     *
     * @response 200 {"success": true, "data": {"token": "...", "expires_at": null}}
     * @response 401 {"success": false, "message": "Invalid credentials"}
     */
    public function token(): void
    {
        $data = $this->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::findByEmail($data['email']);

        if (!$user || !User::verifyPassword($data['password'], $user['password'])) {
            $this->unauthorized('Invalid credentials');
        }

        // Generate a new API token
        $token = bin2hex(random_bytes(32));
        $tokenName = $this->input('name', 'API Token');
        $expiresAt = $this->input('expires_at'); // Optional: ISO date

        // Store hashed token
        Database::query(
            'INSERT INTO api_tokens (user_id, name, token, expires_at, created_at)
             VALUES (?, ?, ?, ?, NOW())',
            [$user['id'], $tokenName, hash('sha256', $token), $expiresAt]
        );

        $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'name' => $tokenName,
            'expires_at' => $expiresAt,
            'usage' => 'Include in requests as: Authorization: Bearer ' . $token,
        ], 'API token created successfully');
    }

    /**
     * Revoke current API token
     *
     * @route DELETE /api/v1/auth/token
     *
     * @response 200 {"success": true, "message": "Token revoked"}
     */
    public function revokeToken(): void
    {
        $token = $this->getBearerTokenFromHeader();

        if (!$token) {
            $this->unauthorized('No token provided');
        }

        Database::query(
            'DELETE FROM api_tokens WHERE token = ?',
            [hash('sha256', $token)]
        );

        $this->success(null, 'Token revoked successfully');
    }

    /**
     * Revoke all API tokens
     *
     * @route DELETE /api/v1/auth/tokens
     *
     * @response 200 {"success": true, "message": "All tokens revoked"}
     */
    public function revokeAllTokens(): void
    {
        $this->requireAuth();

        Database::query(
            'DELETE FROM api_tokens WHERE user_id = ?',
            [$this->userId()]
        );

        $this->success(null, 'All tokens revoked successfully');
    }

    /**
     * List all API tokens
     *
     * @route GET /api/v1/auth/tokens
     *
     * @response 200 {"success": true, "data": [...]}
     */
    public function listTokens(): void
    {
        $this->requireAuth();

        $tokens = Database::fetchAll(
            'SELECT id, name, created_at, expires_at, last_used_at
             FROM api_tokens WHERE user_id = ? ORDER BY created_at DESC',
            [$this->userId()]
        );

        $this->success(array_map(fn ($t) => [
            'id' => (int) $t['id'],
            'name' => $t['name'],
            'created_at' => $t['created_at'],
            'expires_at' => $t['expires_at'],
            'last_used_at' => $t['last_used_at'],
        ], $tokens));
    }

    /**
     * Get current user info
     *
     * @route GET /api/v1/auth/me
     *
     * @response 200 {"success": true, "data": {...}}
     */
    public function me(): void
    {
        $this->requireAuth();

        $this->success([
            'id' => (int) $this->user['id'],
            'name' => $this->user['name'],
            'email' => $this->user['email'],
            'created_at' => $this->user['created_at'],
        ]);
    }

    /**
     * Get Bearer token from header
     */
    private function getBearerTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
