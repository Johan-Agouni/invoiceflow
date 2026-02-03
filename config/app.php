<?php

declare(strict_types=1);

return [
    'name' => $_ENV['APP_NAME'] ?? 'InvoiceFlow',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'key' => $_ENV['APP_KEY'] ?? '',

    'timezone' => 'Europe/Paris',
    'locale' => 'fr_FR',

    'debug' => ($_ENV['APP_ENV'] ?? 'production') === 'development',

    // Invoice settings
    'invoice' => [
        'prefix' => 'FAC',
        'quote_prefix' => 'DEV',
        'date_format' => 'd/m/Y',
        'currency' => 'EUR',
        'currency_symbol' => '€',
        'payment_terms' => 30, // days
    ],

    // Reminder settings
    'reminders' => [
        'first_reminder_days' => 7,   // days after due date
        'second_reminder_days' => 14,
        'final_reminder_days' => 30,
    ],
];
