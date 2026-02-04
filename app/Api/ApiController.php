<?php

declare(strict_types=1);

namespace App\Api;

use App\Database;

/**
 * Base API Controller
 *
 * Provides common functionality for all API endpoints including
 * authentication, response formatting, and error handling.
 */
abstract class ApiController
{
    protected ?array $user = null;

    /**
     * Authenticate request using Bearer token
     */
    protected function authenticate(): bool
    {
        $token = $this->getBearerToken();

        if (!$token) {
            return false;
        }

        $this->user = Database::fetch(
            "SELECT u.* FROM users u
             INNER JOIN api_tokens t ON t.user_id = u.id
             WHERE t.token = ? AND (t.expires_at IS NULL OR t.expires_at > NOW())",
            [hash('sha256', $token)]
        );

        if ($this->user) {
            // Update last used timestamp
            Database::query(
                "UPDATE api_tokens SET last_used_at = NOW() WHERE token = ?",
                [hash('sha256', $token)]
            );
        }

        return $this->user !== null;
    }

    /**
     * Require authentication or return 401
     */
    protected function requireAuth(): void
    {
        if (!$this->authenticate()) {
            $this->unauthorized('Invalid or missing API token');
        }
    }

    /**
     * Get Bearer token from Authorization header
     */
    private function getBearerToken(): ?string
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get authenticated user ID
     */
    protected function userId(): int
    {
        return (int) $this->user['id'];
    }

    /**
     * Get JSON input from request body
     */
    protected function input(?string $key = null, mixed $default = null): mixed
    {
        static $data = null;

        if ($data === null) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?? [];
        }

        if ($key === null) {
            return $data;
        }

        return $data[$key] ?? $default;
    }

    /**
     * Get query parameter
     */
    protected function query(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Send JSON response
     */
    protected function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send success response
     */
    protected function success(mixed $data = null, string $message = 'Success', int $status = 200): never
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        $this->json($response, $status);
    }

    /**
     * Send created response (201)
     */
    protected function created(mixed $data, string $message = 'Resource created'): never
    {
        $this->success($data, $message, 201);
    }

    /**
     * Send error response
     */
    protected function error(string $message, int $status = 400, ?array $errors = null): never
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        $this->json($response, $status);
    }

    /**
     * Send 401 Unauthorized response
     */
    protected function unauthorized(string $message = 'Unauthorized'): never
    {
        header('WWW-Authenticate: Bearer');
        $this->error($message, 401);
    }

    /**
     * Send 403 Forbidden response
     */
    protected function forbidden(string $message = 'Forbidden'): never
    {
        $this->error($message, 403);
    }

    /**
     * Send 404 Not Found response
     */
    protected function notFound(string $message = 'Resource not found'): never
    {
        $this->error($message, 404);
    }

    /**
     * Send 422 Validation Error response
     */
    protected function validationError(array $errors): never
    {
        $this->error('Validation failed', 422, $errors);
    }

    /**
     * Validate required fields
     */
    protected function validate(array $rules): array
    {
        $errors = [];
        $data = $this->input();

        foreach ($rules as $field => $ruleSet) {
            $ruleList = is_string($ruleSet) ? explode('|', $ruleSet) : $ruleSet;
            $value = $data[$field] ?? null;

            foreach ($ruleList as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$rule, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                }

                $error = $this->validateRule($field, $value, $rule, $params);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }

        if (!empty($errors)) {
            $this->validationError($errors);
        }

        return $data;
    }

    /**
     * Validate a single rule
     */
    private function validateRule(string $field, mixed $value, string $rule, array $params): ?string
    {
        $label = str_replace('_', ' ', $field);

        return match ($rule) {
            'required' => empty($value) && $value !== '0' ? "The {$label} field is required" : null,
            'string' => !is_string($value) && $value !== null ? "The {$label} must be a string" : null,
            'integer' => !is_numeric($value) && $value !== null ? "The {$label} must be an integer" : null,
            'numeric' => !is_numeric($value) && $value !== null ? "The {$label} must be a number" : null,
            'email' => $value && !filter_var($value, FILTER_VALIDATE_EMAIL) ? "The {$label} must be a valid email" : null,
            'min' => strlen((string) $value) < (int) $params[0] ? "The {$label} must be at least {$params[0]} characters" : null,
            'max' => strlen((string) $value) > (int) $params[0] ? "The {$label} must not exceed {$params[0]} characters" : null,
            'date' => $value && !strtotime($value) ? "The {$label} must be a valid date" : null,
            'in' => $value && !in_array($value, $params) ? "The {$label} must be one of: " . implode(', ', $params) : null,
            default => null,
        };
    }

    /**
     * Format pagination response
     */
    protected function paginate(array $items, int $total, int $page, int $perPage): array
    {
        return [
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil($total / $perPage),
                'has_more' => ($page * $perPage) < $total,
            ],
        ];
    }
}
