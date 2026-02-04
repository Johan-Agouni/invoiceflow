-- Stripe Payment Integration
-- Migration: 003_stripe_payments.sql

-- Add Stripe columns to invoices table
ALTER TABLE `invoices`
    ADD COLUMN `stripe_session_id` VARCHAR(255) NULL AFTER `last_reminder_at`,
    ADD COLUMN `stripe_payment_intent_id` VARCHAR(255) NULL AFTER `stripe_session_id`,
    ADD COLUMN `stripe_payment_id` VARCHAR(255) NULL AFTER `stripe_payment_intent_id`,
    ADD COLUMN `stripe_payment_status` ENUM('pending', 'processing', 'paid', 'failed', 'refunded') NULL AFTER `stripe_payment_id`;

-- Add index for Stripe lookups
ALTER TABLE `invoices`
    ADD INDEX `idx_stripe_session` (`stripe_session_id`),
    ADD INDEX `idx_stripe_payment_intent` (`stripe_payment_intent_id`);

-- Create payments log table for audit trail
CREATE TABLE IF NOT EXISTS `payment_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `invoice_id` INT UNSIGNED NOT NULL,
    `event_type` VARCHAR(100) NOT NULL,
    `stripe_event_id` VARCHAR(255) NULL,
    `amount` DECIMAL(12,2) NULL,
    `status` VARCHAR(50) NOT NULL,
    `raw_data` JSON NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_invoice_id` (`invoice_id`),
    INDEX `idx_event_type` (`event_type`),
    CONSTRAINT `fk_payment_logs_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
