<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Services\RateLimiter;

/**
 * Rate Limit Middleware
 *
 * Protects API endpoints from abuse by limiting the number of
 * requests per time window. Returns appropriate headers and
 * 429 status code when limit is exceeded.
 */
class RateLimitMiddleware
{
    private RateLimiter $limiter;

    private int $maxRequests;

    private int $windowSeconds;

    public function __construct(int $maxRequests = 100, int $windowSeconds = 60)
    {
        $this->limiter = new RateLimiter();
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;

        $this->limiter->setLimits($maxRequests, $windowSeconds);
    }

    /**
     * Handle the rate limit check
     *
     * @param string|null $identifier Custom identifier (defaults to IP or token)
     *
     * @return bool True if request is allowed
     */
    public function handle(?string $identifier = null): bool
    {
        $key = $identifier ?? $this->getIdentifier();
        $result = $this->limiter->attempt($key);

        // Set rate limit headers
        $this->setHeaders($result);

        if (!$result['allowed']) {
            $this->sendTooManyRequestsResponse($result);

            return false;
        }

        return true;
    }

    /**
     * Get identifier for rate limiting
     * Prefers API token, falls back to IP address
     */
    private function getIdentifier(): string
    {
        // Try to get API token from Authorization header
        $token = $this->getBearerToken();
        if ($token) {
            return 'token_' . substr(hash('sha256', $token), 0, 16);
        }

        // Fall back to IP address
        return 'ip_' . $this->getClientIp();
    }

    /**
     * Get Bearer token from Authorization header
     */
    private function getBearerToken(): ?string
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get client IP address
     */
    private function getClientIp(): string
    {
        // Check for proxy headers (be careful in production)
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
            'HTTP_X_FORWARDED_FOR',      // Standard proxy
            'HTTP_X_REAL_IP',            // Nginx proxy
            'REMOTE_ADDR',               // Direct connection
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];

                // Handle comma-separated IPs (X-Forwarded-For)
                if (str_contains($ip, ',')) {
                    $ip = trim(explode(',', $ip)[0]);
                }

                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Set rate limit response headers
     */
    private function setHeaders(array $result): void
    {
        header("X-RateLimit-Limit: {$result['limit']}");
        header("X-RateLimit-Remaining: {$result['remaining']}");
        header("X-RateLimit-Reset: {$result['reset']}");
    }

    /**
     * Send 429 Too Many Requests response
     */
    private function sendTooManyRequestsResponse(array $result): void
    {
        http_response_code(429);
        header('Content-Type: application/json; charset=utf-8');
        header('Retry-After: ' . ($result['reset'] - time()));

        $response = [
            'success' => false,
            'message' => 'Too many requests. Please slow down.',
            'error' => [
                'code' => 'RATE_LIMIT_EXCEEDED',
                'retry_after' => $result['reset'] - time(),
                'limit' => $result['limit'],
                'window_seconds' => $this->windowSeconds,
            ],
        ];

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }
}
