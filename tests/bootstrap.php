<?php

declare(strict_types=1);

/**
 * PHPUnit Bootstrap File
 *
 * Sets up the testing environment
 */

// Autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables for testing
if (file_exists(__DIR__ . '/../.env.testing')) {
    $dotenv = Dotenv\Dotenv::createMutable(__DIR__ . '/..', '.env.testing');
    $dotenv->load();
} else {
    // Fallback defaults when no .env.testing exists
    $_ENV['DB_HOST'] = 'localhost';
    $_ENV['DB_PORT'] = '3306';
    $_ENV['DB_DATABASE'] = 'invoiceflow_test';
    $_ENV['DB_USERNAME'] = 'root';
    $_ENV['DB_PASSWORD'] = '';
    $_ENV['APP_ENV'] = 'testing';
}

// Start session for tests that need it
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
