-- One-time manual upgrade for API Access.
-- Import this only if `php spark migrate` cannot be run on the server.

ALTER TABLE `third_party_apis`
    ADD COLUMN `provider` VARCHAR(30) NOT NULL DEFAULT 'custom' AFTER `name`,
    ADD COLUMN `api_base_url` VARCHAR(255) DEFAULT NULL AFTER `provider`,
    ADD COLUMN `api_token` VARCHAR(255) DEFAULT NULL AFTER `api_base_url`;

-- Existing URL-template entries are retained but paused. They no longer create links automatically.
UPDATE `third_party_apis` SET `status` = 'paused' WHERE `provider` = 'custom';
