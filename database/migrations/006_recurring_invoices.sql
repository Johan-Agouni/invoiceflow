-- Migration: Recurring Invoices (Factures récurrentes)
-- Version: 1.6.0

-- Table des modèles de factures récurrentes
CREATE TABLE IF NOT EXISTS `recurring_invoices` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `client_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `frequency` ENUM('weekly', 'monthly', 'quarterly', 'yearly') NOT NULL DEFAULT 'monthly',
    `day_of_month` TINYINT UNSIGNED NULL DEFAULT 1,
    `day_of_week` TINYINT UNSIGNED NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NULL,
    `next_invoice_date` DATE NOT NULL,
    `last_invoice_date` DATE NULL,
    `last_invoice_id` INT UNSIGNED NULL,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `vat_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT NULL,
    `auto_send` TINYINT(1) NOT NULL DEFAULT 0,
    `status` ENUM('active', 'paused', 'completed', 'cancelled') DEFAULT 'active',
    `invoices_generated` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_client_id` (`client_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_next_invoice_date` (`next_invoice_date`),
    CONSTRAINT `fk_recurring_invoices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_recurring_invoices_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Items des factures récurrentes
CREATE TABLE IF NOT EXISTS `recurring_invoice_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `recurring_invoice_id` INT UNSIGNED NOT NULL,
    `description` TEXT NOT NULL,
    `quantity` DECIMAL(10,2) NOT NULL DEFAULT 1.00,
    `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `vat_rate` DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_recurring_invoice_id` (`recurring_invoice_id`),
    CONSTRAINT `fk_recurring_items_recurring` FOREIGN KEY (`recurring_invoice_id`) REFERENCES `recurring_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
