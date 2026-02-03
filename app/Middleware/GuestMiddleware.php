<?php

declare(strict_types=1);

namespace App\Middleware;

class GuestMiddleware
{
    public function handle(): bool
    {
        if (isset($_SESSION['user_id'])) {
            header('Location: /dashboard');
            exit;
        }

        return true;
    }
}
