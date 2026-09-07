-- Manual database update for the managed Popup Ads feature.
-- Safe to import more than once: no existing table or data is removed.

CREATE TABLE IF NOT EXISTS `popup_ad_units` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page` VARCHAR(30) NOT NULL DEFAULT 'embed',
    `provider` VARCHAR(30) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `ad_code` LONGTEXT NOT NULL,
    `weight` INT(3) UNSIGNED NOT NULL DEFAULT 1,
    `status` VARCHAR(10) NOT NULL DEFAULT 'paused',
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `popup_ad_units_page_status` (`page`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
