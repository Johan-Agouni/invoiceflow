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
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.testing');
$dotenv->safeLoad();

// If no .env.testing, use defaults
if (empty($_ENV['DB_DATABASE'])) {
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
