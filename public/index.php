<?php

declare(strict_types=1);

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Session configuration
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? '1' : '0');
ini_set('session.use_strict_mode', '1');

session_start();

// Security headers (OWASP compliant)
$securityHeaders = new \App\Middleware\SecurityHeadersMiddleware();
$securityHeaders->handle();

// Rate limiting for API routes
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
if (str_starts_with($requestUri, '/api/')) {
    $rateLimiter = new \App\Middleware\RateLimitMiddleware(
        maxRequests: (int) ($_ENV['API_RATE_LIMIT'] ?? 100),
        windowSeconds: (int) ($_ENV['API_RATE_WINDOW'] ?? 60)
    );
    $rateLimiter->handle();
}

// Load routes
$router = require __DIR__ . '/../routes/web.php';

// Load API routes
require __DIR__ . '/../routes/api.php';

// Dispatch request
$router->dispatch();
