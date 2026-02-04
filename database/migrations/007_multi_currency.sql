-- Migration: Multi-Currency Support
-- Version: 1.7.0

-- Table des devises
CREATE TABLE IF NOT EXISTS `currencies` (
    `code` CHAR(3) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `symbol` VARCHAR(10) NOT NULL,
    `decimal_places` TINYINT UNSIGNED NOT NULL DEFAULT 2,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Devises par défaut
INSERT INTO `currencies` (`code`, `name`, `symbol`, `decimal_places`) VALUES
('EUR', 'Euro', '€', 2),
('USD', 'Dollar américain', '$', 2),
('GBP', 'Livre sterling', '£', 2),
('CHF', 'Franc suisse', 'CHF', 2),
('CAD', 'Dollar canadien', 'CA$', 2),
('JPY', 'Yen japonais', '¥', 0),
('AUD', 'Dollar australien', 'A$', 2),
('MAD', 'Dirham marocain', 'DH', 2),
('XOF', 'Franc CFA', 'CFA', 0),
('TND', 'Dinar tunisien', 'DT', 3);

-- Table des taux de change
CREATE TABLE IF NOT EXISTS `exchange_rates` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `base_currency` CHAR(3) NOT NULL DEFAULT 'EUR',
    `target_currency` CHAR(3) NOT NULL,
    `rate` DECIMAL(18,8) NOT NULL,
    `date` DATE NOT NULL,
    `source` VARCHAR(50) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_rate` (`base_currency`, `target_currency`, `date`),
    INDEX `idx_date` (`date`),
    CONSTRAINT `fk_exchange_base` FOREIGN KEY (`base_currency`) REFERENCES `currencies` (`code`),
    CONSTRAINT `fk_exchange_target` FOREIGN KEY (`target_currency`) REFERENCES `currencies` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ajout de la devise aux tables existantes
ALTER TABLE `settings`
    ADD COLUMN `default_currency` CHAR(3) NOT NULL DEFAULT 'EUR' AFTER `default_vat_rate`;

ALTER TABLE `clients`
    ADD COLUMN `currency` CHAR(3) NOT NULL DEFAULT 'EUR' AFTER `country`;

ALTER TABLE `invoices`
    ADD COLUMN `currency` CHAR(3) NOT NULL DEFAULT 'EUR' AFTER `total_amount`,
    ADD COLUMN `exchange_rate` DECIMAL(18,8) NULL AFTER `currency`,
    ADD COLUMN `total_amount_eur` DECIMAL(12,2) NULL AFTER `exchange_rate`;

ALTER TABLE `quotes`
    ADD COLUMN `currency` CHAR(3) NOT NULL DEFAULT 'EUR' AFTER `total_amount`,
    ADD COLUMN `exchange_rate` DECIMAL(18,8) NULL AFTER `currency`,
    ADD COLUMN `total_amount_eur` DECIMAL(12,2) NULL AFTER `exchange_rate`;

-- Mise à jour des factures existantes
UPDATE `invoices` SET `total_amount_eur` = `total_amount` WHERE `currency` = 'EUR';
UPDATE `quotes` SET `total_amount_eur` = `total_amount` WHERE `currency` = 'EUR';
