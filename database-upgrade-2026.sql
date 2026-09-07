-- Run with `php spark migrate`. This SQL is provided for hosting panels that
-- cannot run CodeIgniter migrations. It changes schema only; it does not
-- import the untrusted legacy data dump.
ALTER TABLE `links`
  ADD COLUMN `host_priority` SMALLINT UNSIGNED NOT NULL DEFAULT 100 AFTER `type`,
  ADD COLUMN `failure_count` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `host_priority`,
  ADD COLUMN `last_checked_at` DATETIME NULL AFTER `failure_count`,
  ADD COLUMN `last_success_at` DATETIME NULL AFTER `last_checked_at`,
  ADD COLUMN `last_failure_at` DATETIME NULL AFTER `last_success_at`,
  ADD COLUMN `last_served_at` DATETIME NULL AFTER `last_failure_at`,
  ADD COLUMN `last_error` VARCHAR(255) NULL AFTER `last_served_at`,
  ADD COLUMN `upnshare_video_id` VARCHAR(128) NULL AFTER `last_error`,
  ADD INDEX `links_movie_id_type_is_broken_last_served_at` (`movie_id`, `type`, `is_broken`, `last_served_at`);
