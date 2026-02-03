<?php

declare(strict_types=1);

namespace App;

class Controller
{
    protected function view(string $name, array $data = []): void
    {
        extract($data);
        $viewPath = __DIR__ . '/Views/' . str_replace('.', '/', $name) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$name}");
        }

        require $viewPath;
    }

    protected function redirect(string $url, int $status = 302): void
    {
        header("Location: {$url}", true, $status);
        exit;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function back(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer);
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type] = $message;
    }

    protected function getFlash(): array
    {
        $flash = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return $flash;
    }

    protected function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function validateCsrf(): bool
    {
        $token = $this->input('_token');
        return $token && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    }

    protected function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']);
    }

    protected function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
}
