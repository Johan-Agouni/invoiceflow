#!/usr/bin/env php
<?php

/**
 * InvoiceFlow Cron Tasks
 *
 * This script handles scheduled tasks like sending invoice reminders.
 * Add to crontab: 0 9 * * * php /path/to/invoiceflow/bin/cron.php reminders
 *
 * Available commands:
 *   - reminders: Process and send invoice payment reminders
 *   - cleanup: Clean up old rate limit files and expired tokens
 */

declare(strict_types=1);

// Ensure running from CLI
if (php_sapi_name() !== 'cli') {
    die('This script must be run from the command line.');
}

// Bootstrap
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Get command
$command = $argv[1] ?? 'help';

switch ($command) {
    case 'reminders':
        runReminders();
        break;

    case 'cleanup':
        runCleanup();
        break;

    case 'help':
    default:
        showHelp();
        break;
}

/**
 * Process invoice reminders
 */
function runReminders(): void
{
    echo "[" . date('Y-m-d H:i:s') . "] Starting reminder processing...\n";

    try {
        $service = new \App\Services\ReminderService();
        $result = $service->processReminders();

        echo "Processed: {$result['processed']}\n";
        echo "Sent: {$result['sent']}\n";
        echo "Failed: {$result['failed']}\n";

        if (!empty($result['details'])) {
            echo "\nDetails:\n";
            foreach ($result['details'] as $detail) {
                $status = $detail['status'] === 'sent' ? 'OK' : 'FAIL';
                echo "  [{$status}] {$detail['invoice_number']}";
                if (isset($detail['error'])) {
                    echo " - {$detail['error']}";
                }
                echo "\n";
            }
        }

        echo "\n[" . date('Y-m-d H:i:s') . "] Reminder processing complete.\n";
    } catch (\Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Cleanup old files and expired data
 */
function runCleanup(): void
{
    echo "[" . date('Y-m-d H:i:s') . "] Starting cleanup...\n";

    // Clean rate limit files
    $rateLimiter = new \App\Services\RateLimiter();
    $deleted = $rateLimiter->cleanup();
    echo "Deleted {$deleted} old rate limit files.\n";

    // Clean expired API tokens
    $expiredTokens = \App\Database::query(
        "DELETE FROM api_tokens WHERE expires_at IS NOT NULL AND expires_at < NOW()"
    );
    echo "Deleted expired API tokens.\n";

    // Clean old password reset tokens
    \App\Database::query(
        "UPDATE users SET reset_token = NULL, reset_token_expires_at = NULL
         WHERE reset_token_expires_at IS NOT NULL AND reset_token_expires_at < NOW()"
    );
    echo "Cleaned expired password reset tokens.\n";

    echo "[" . date('Y-m-d H:i:s') . "] Cleanup complete.\n";
}

/**
 * Show help
 */
function showHelp(): void
{
    echo "InvoiceFlow Cron Tasks\n";
    echo "======================\n\n";
    echo "Usage: php cron.php <command>\n\n";
    echo "Available commands:\n";
    echo "  reminders  Process and send invoice payment reminders\n";
    echo "  cleanup    Clean up old rate limit files and expired tokens\n";
    echo "  help       Show this help message\n\n";
    echo "Example crontab entries:\n";
    echo "  # Send reminders every day at 9 AM\n";
    echo "  0 9 * * * php /path/to/invoiceflow/bin/cron.php reminders\n\n";
    echo "  # Cleanup every day at midnight\n";
    echo "  0 0 * * * php /path/to/invoiceflow/bin/cron.php cleanup\n";
}
