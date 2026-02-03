<?php

declare(strict_types=1);

namespace App\Middleware;

class CsrfMiddleware
{
    public function handle(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['_token'] ?? '';

            if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                http_response_code(403);
                echo 'Invalid CSRF token';
                exit;
            }
        }

        return true;
    }
}
