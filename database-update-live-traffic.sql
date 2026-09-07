-- Manual database update for the Active Now dashboard card and Zode credentials.
-- Safe to import repeatedly. It does not remove existing records.

CREATE TABLE IF NOT EXISTS `live_traffic` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page` VARCHAR(30) NOT NULL DEFAULT 'embed',
    `visitor_key` VARCHAR(64) NOT NULL,
    `last_seen_at` DATETIME NOT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `live_traffic_page_visitor_key` (`page`, `visitor_key`),
    KEY `live_traffic_page_last_seen_at` (`page`, `last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`name`, `value`, `data_type`) VALUES
    ('zode_id', '', 'string'),
    ('zode_api_token', '', 'string');
