<?php

declare(strict_types=1);

/**
 * Script de migration pour Railway
 * Exécute les migrations SQL si les tables n'existent pas
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_DATABASE'] ?? 'invoiceflow';
$username = $_ENV['DB_USERNAME'] ?? 'root';
$password = $_ENV['DB_PASSWORD'] ?? '';

echo "Connecting to database {$database} on {$host}:{$port}...\n";

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "Connected successfully!\n";
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage() . "\n");
}

// Check if tables exist
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

if (in_array('users', $tables)) {
    echo "Tables already exist. Skipping migration.\n";
    exit(0);
}

echo "Running migrations...\n";

// Migration files in order
$migrations = [
    'init.sql',
    '002_api_tokens.sql',
    '003_stripe_payments.sql',
    '004_two_factor_auth.sql',
    '005_audit_log.sql',
    '006_recurring_invoices.sql',
    '007_multi_currency.sql',
];

$migrationsPath = __DIR__ . '/../database/migrations/';

foreach ($migrations as $migration) {
    $file = $migrationsPath . $migration;
    if (!file_exists($file)) {
        echo "Warning: Migration file not found: {$migration}\n";
        continue;
    }

    echo "Running {$migration}...\n";
    $sql = file_get_contents($file);

    try {
        $pdo->exec($sql);
        echo "  OK\n";
    } catch (PDOException $e) {
        echo "  Error: " . $e->getMessage() . "\n";
        // Continue with other migrations
    }
}

echo "Migrations completed!\n";
