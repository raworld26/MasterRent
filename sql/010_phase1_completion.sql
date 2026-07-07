-- MasterRent - Fase 1 completion
-- File: sql/010_phase1_completion.sql
-- Purpose: align Phase 1 logistics with Phase 2 routes, room states and booking lifecycle.
-- Run order: After 009_engagement_seed.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';

USE `masterrent`;

DELIMITER $$

DROP PROCEDURE IF EXISTS `masterrent_add_column_if_missing` $$
CREATE PROCEDURE `masterrent_add_column_if_missing`(
  IN table_name_param VARCHAR(64),
  IN column_name_param VARCHAR(64),
  IN column_sql_param TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name_param
      AND COLUMN_NAME = column_name_param
  ) THEN
    SET @alter_sql = CONCAT('ALTER TABLE `', table_name_param, '` ADD COLUMN ', column_sql_param);
    PREPARE alter_stmt FROM @alter_sql;
    EXECUTE alter_stmt;
    DEALLOCATE PREPARE alter_stmt;
  END IF;
END $$

CALL `masterrent_add_column_if_missing`('rooms', 'status', '`status` ENUM(''available'',''reserved'',''unavailable'') NOT NULL DEFAULT ''available'' AFTER `is_available`') $$
CALL `masterrent_add_column_if_missing`('booking_requests', 'move_in_date', '`move_in_date` DATE DEFAULT NULL AFTER `visit_date`') $$
CALL `masterrent_add_column_if_missing`('booking_requests', 'deposit_amount', '`deposit_amount` DECIMAL(10,2) DEFAULT NULL AFTER `message`') $$
CALL `masterrent_add_column_if_missing`('booking_requests', 'deposit_paid_at', '`deposit_paid_at` DATETIME DEFAULT NULL AFTER `deposit_amount`') $$
CALL `masterrent_add_column_if_missing`('booking_requests', 'deposit_reference', '`deposit_reference` VARCHAR(80) DEFAULT NULL AFTER `deposit_paid_at`') $$
CALL `masterrent_add_column_if_missing`('booking_requests', 'payment_reference', '`payment_reference` VARCHAR(80) DEFAULT NULL AFTER `deposit_reference`') $$

DROP PROCEDURE IF EXISTS `masterrent_add_column_if_missing` $$

DELIMITER ;

ALTER TABLE `booking_requests`
  MODIFY `status` ENUM('new','approved','rejected','withdrawn','deposit_paid','booked','closed','visit_requested','approved_pending_deposit') NOT NULL DEFAULT 'visit_requested';

UPDATE `booking_requests` SET `status` = 'visit_requested' WHERE `status` = 'new';
UPDATE `booking_requests` SET `status` = 'approved_pending_deposit' WHERE `status` = 'approved';
UPDATE `booking_requests` SET `status` = 'deposit_paid' WHERE `status` = 'booked';
UPDATE `booking_requests` SET `status` = 'withdrawn' WHERE `status` = 'closed';

ALTER TABLE `booking_requests`
  MODIFY `status` ENUM('visit_requested','approved_pending_deposit','rejected','withdrawn','deposit_paid') NOT NULL DEFAULT 'visit_requested';

UPDATE `booking_requests`
SET `deposit_reference` = `payment_reference`
WHERE (`deposit_reference` IS NULL OR `deposit_reference` = '')
  AND `payment_reference` IS NOT NULL
  AND `payment_reference` <> '';

UPDATE `booking_requests` AS br
JOIN `rooms` AS r ON r.`id` = br.`room_id`
SET br.`deposit_amount` = r.`price_monthly`
WHERE br.`status` = 'deposit_paid'
  AND (br.`deposit_amount` IS NULL OR br.`deposit_amount` = 0);

UPDATE `rooms` SET `status` = 'available' WHERE `is_available` = 1;
UPDATE `rooms` SET `status` = 'unavailable' WHERE `is_available` = 0;
UPDATE `rooms` AS r
SET r.`status` = 'reserved', r.`is_available` = 0
WHERE EXISTS (
  SELECT 1
  FROM `booking_requests` AS br
  WHERE br.`room_id` = r.`id`
    AND br.`status` = 'deposit_paid'
);

CREATE TABLE IF NOT EXISTS `booking_request_status_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(40) NOT NULL,
  `note` VARCHAR(255) DEFAULT NULL,
  `changed_by` BIGINT UNSIGNED DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking_request_status_history_request` (`request_id`),
  KEY `idx_booking_request_status_history_user` (`changed_by`),
  CONSTRAINT `fk_booking_request_status_history_request`
    FOREIGN KEY (`request_id`) REFERENCES `booking_requests` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk_booking_request_status_history_user`
    FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `booking_request_status_history` (`request_id`, `status`, `note`, `changed_by`, `created_at`)
SELECT br.`id`, br.`status`, 'Stato importato dalla migrazione Fase 1', NULL, br.`updated_at`
FROM `booking_requests` AS br
WHERE NOT EXISTS (
  SELECT 1
  FROM `booking_request_status_history` AS h
  WHERE h.`request_id` = br.`id`
);

DELIMITER $$

DROP PROCEDURE IF EXISTS `masterrent_add_index_if_missing` $$
CREATE PROCEDURE `masterrent_add_index_if_missing`(
  IN table_name_param VARCHAR(64),
  IN index_name_param VARCHAR(64),
  IN index_sql_param TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name_param
      AND INDEX_NAME = index_name_param
  ) THEN
    SET @index_sql = index_sql_param;
    PREPARE index_stmt FROM @index_sql;
    EXECUTE index_stmt;
    DEALLOCATE PREPARE index_stmt;
  END IF;
END $$

CALL `masterrent_add_index_if_missing`(
  'rooms',
  'idx_rooms_status',
  'CREATE INDEX `idx_rooms_status` ON `rooms` (`status`)'
) $$

CALL `masterrent_add_index_if_missing`(
  'booking_requests',
  'idx_booking_requests_deposit_reference',
  'CREATE INDEX `idx_booking_requests_deposit_reference` ON `booking_requests` (`deposit_reference`)'
) $$

CALL `masterrent_add_index_if_missing`(
  'booking_requests',
  'uk_booking_requests_room_student',
  'CREATE UNIQUE INDEX `uk_booking_requests_room_student` ON `booking_requests` (`room_id`, `student_id`)'
) $$

DROP PROCEDURE IF EXISTS `masterrent_add_index_if_missing` $$

DELIMITER ;
