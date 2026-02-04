-- Migration: Two-Factor Authentication
-- Version: 1.4.0

-- Ajout des colonnes 2FA à la table users
ALTER TABLE `users`
    ADD COLUMN `two_factor_secret` VARCHAR(255) NULL AFTER `reset_token_expires_at`,
    ADD COLUMN `two_factor_enabled` TINYINT(1) NOT NULL DEFAULT 0 AFTER `two_factor_secret`,
    ADD COLUMN `two_factor_confirmed_at` DATETIME NULL AFTER `two_factor_enabled`,
    ADD COLUMN `two_factor_recovery_codes` TEXT NULL AFTER `two_factor_confirmed_at`;

-- Index pour optimiser les requêtes de vérification 2FA
ALTER TABLE `users`
    ADD INDEX `idx_two_factor_enabled` (`two_factor_enabled`);

-- Table pour les sessions 2FA validées (remember device)
CREATE TABLE IF NOT EXISTS `two_factor_trusted_devices` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `device_token` VARCHAR(64) NOT NULL,
    `device_name` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` VARCHAR(512) NULL,
    `last_used_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_device_token` (`device_token`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_expires_at` (`expires_at`),
    CONSTRAINT `fk_trusted_devices_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
