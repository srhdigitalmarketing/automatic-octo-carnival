-- Pilih database website di phpMyAdmin sebelum Import.
-- Export backup third_party_apis terlebih dahulu.
-- Jika memakai prefix tabel, ganti semua third_party_apis dengan nama sebenarnya.
-- Tidak menghapus/mengganti data, mengubah kolom lama, atau menyentuh links.
-- Tidak memasukkan hostname/API key contoh ke database.

CREATE TABLE IF NOT EXISTS `third_party_apis` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(128) NOT NULL DEFAULT '',
  `provider` VARCHAR(30) NOT NULL DEFAULT 'custom',
  `api_base_url` VARCHAR(255) NULL DEFAULT NULL,
  `api_token` VARCHAR(255) NULL DEFAULT NULL,
  `embed_domains` VARCHAR(1000) NOT NULL DEFAULT '',
  `r2_account_id` VARCHAR(64) NULL DEFAULT NULL,
  `r2_access_key_id` VARCHAR(128) NULL DEFAULT NULL,
  `r2_secret_access_key` VARCHAR(255) NULL DEFAULT NULL,
  `r2_bucket` VARCHAR(255) NULL DEFAULT NULL,
  `r2_public_url` VARCHAR(255) NULL DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME NULL DEFAULT NULL,
  `updated_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'name'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `name` VARCHAR(128) NOT NULL DEFAULT '''''
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'provider'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `provider` VARCHAR(30) NOT NULL DEFAULT ''custom'''
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'api_base_url'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `api_base_url` VARCHAR(255) NULL DEFAULT NULL'
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'api_token'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `api_token` VARCHAR(255) NULL DEFAULT NULL'
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'embed_domains'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `embed_domains` VARCHAR(1000) NOT NULL DEFAULT '''''
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'r2_account_id'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `r2_account_id` VARCHAR(64) NULL DEFAULT NULL'
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'r2_access_key_id'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `r2_access_key_id` VARCHAR(128) NULL DEFAULT NULL'
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'r2_secret_access_key'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `r2_secret_access_key` VARCHAR(255) NULL DEFAULT NULL'
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'r2_bucket'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `r2_bucket` VARCHAR(255) NULL DEFAULT NULL'
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'r2_public_url'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `r2_public_url` VARCHAR(255) NULL DEFAULT NULL'
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'status'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT ''active'''
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'created_at'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `created_at` DATETIME NULL DEFAULT NULL'
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

SET @api_patch_sql = IF(
  EXISTS(SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis' AND COLUMN_NAME = 'updated_at'),
  'SELECT 1 AS already_present',
  'ALTER TABLE `third_party_apis` ADD COLUMN `updated_at` DATETIME NULL DEFAULT NULL'
);
PREPARE api_patch_stmt FROM @api_patch_sql;
EXECUTE api_patch_stmt;
DEALLOCATE PREPARE api_patch_stmt;

-- Metadata diagnosis only. No credentials or row contents are shown.
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'third_party_apis'
ORDER BY ORDINAL_POSITION;

-- An ENUM provider without vod_catalog, or old NOT NULL columns without defaults,
-- may still block VOD inserts. Review the metadata above; existing columns are not
-- automatically modified by this patch. Do not run all historical migrations.
