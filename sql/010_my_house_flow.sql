-- MasterRent - Phase 1
-- Migration: La mia casa, disdetta studente e chiusura rapporto proprietario.

SET NAMES utf8mb4;
SET time_zone = '+00:00';
USE `masterrent`;

ALTER TABLE `bookings`
  MODIFY `status` ENUM('visit_requested','approved_pending_deposit','rejected','deposit_paid','cancellation_requested','completed','withdrawn') NOT NULL DEFAULT 'visit_requested';

ALTER TABLE `booking_status_history`
  MODIFY `status` ENUM('visit_requested','approved_pending_deposit','rejected','deposit_paid','cancellation_requested','completed','withdrawn') NOT NULL;

INSERT INTO `services`
  (`code`, `name`, `description`, `area`, `path`, `http_method`, `is_menu_item`, `menu_order`, `is_active`)
VALUES
  ('account.my_house', 'La mia casa', 'Casa o stanza attualmente associata allo studente.', 'frontend', '/account/my-house.php', 'ALL', 0, 204, 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `area` = VALUES(`area`),
  `path` = VALUES(`path`),
  `http_method` = VALUES(`http_method`),
  `is_menu_item` = VALUES(`is_menu_item`),
  `menu_order` = VALUES(`menu_order`),
  `is_active` = VALUES(`is_active`);

INSERT IGNORE INTO `services_has_groups` (`service_id`, `group_id`)
SELECT s.`id`, g.`id`
FROM `services` AS s
JOIN `user_groups` AS g ON g.`code` = 'student'
WHERE s.`code` = 'account.my_house';
