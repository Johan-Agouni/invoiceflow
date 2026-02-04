-- API Tokens table for REST API authentication
-- Migration: 002_api_tokens.sql

CREATE TABLE IF NOT EXISTS `api_tokens` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(255) NOT NULL DEFAULT 'API Token',
    `token` VARCHAR(64) NOT NULL UNIQUE,
    `expires_at` DATETIME NULL,
    `last_used_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_token` (`token`),
    INDEX `idx_user_id` (`user_id`),
    CONSTRAINT `fk_api_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
