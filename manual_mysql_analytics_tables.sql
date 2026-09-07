-- StreamAPI: MySQL tables for Audience (30 days) and Devices analytics.
-- Import this file in the same database used by the application via phpMyAdmin.
-- Safe to run more than once: existing tables are not replaced.

CREATE TABLE IF NOT EXISTS `live_traffic` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `page` VARCHAR(30) NOT NULL DEFAULT 'embed',
    `visitor_key` VARCHAR(64) NOT NULL,
    `last_seen_at` DATETIME NOT NULL,
    `created_at` DATETIME NULL DEFAULT NULL,
    `updated_at` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `live_traffic_page_visitor_key` (`page`, `visitor_key`),
    KEY `live_traffic_page_last_seen_at` (`page`, `last_seen_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `traffic_daily_visitors` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `visit_date` DATE NOT NULL,
    `visitor_key` VARCHAR(64) NOT NULL,
    `platform` VARCHAR(12) NOT NULL DEFAULT 'desktop',
    `created_at` DATETIME NULL DEFAULT NULL,
    `updated_at` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `traffic_daily_visitors_date_visitor_key` (`visit_date`, `visitor_key`),
    KEY `traffic_daily_visitors_date_platform` (`visit_date`, `platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `traffic_daily_player_metrics` (
    `visit_date` DATE NOT NULL,
    `impressions` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `play_clicks` INT(11) UNSIGNED NOT NULL DEFAULT 0,
    `created_at` DATETIME NULL DEFAULT NULL,
    `updated_at` DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (`visit_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
