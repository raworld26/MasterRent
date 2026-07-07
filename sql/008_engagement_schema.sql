-- MasterRent - Phase 1
-- File: sql/008_engagement_schema.sql
-- Purpose: favorites, visit requests, messages and reviews.
-- Run order: After 007_properties_seed.sql

USE `masterrent`;

CREATE TABLE IF NOT EXISTS `favorite_rooms` (
  `user_id` BIGINT UNSIGNED NOT NULL,
  `room_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`, `room_id`),
  KEY `idx_favorite_rooms_room` (`room_id`),
  CONSTRAINT `fk_favorite_rooms_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk_favorite_rooms_room`
    FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `booking_requests` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` INT UNSIGNED NOT NULL,
  `student_id` BIGINT UNSIGNED NOT NULL,
  `landlord_id` BIGINT UNSIGNED NOT NULL,
  `status` ENUM('visit_requested','approved_pending_deposit','rejected','withdrawn','deposit_paid') NOT NULL DEFAULT 'visit_requested',
  `visit_date` DATE DEFAULT NULL,
  `move_in_date` DATE DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `deposit_amount` DECIMAL(10,2) DEFAULT NULL,
  `deposit_paid_at` DATETIME DEFAULT NULL,
  `deposit_reference` VARCHAR(80) DEFAULT NULL,
  `payment_reference` VARCHAR(80) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_booking_requests_room` (`room_id`),
  KEY `idx_booking_requests_student` (`student_id`),
  KEY `idx_booking_requests_landlord` (`landlord_id`),
  KEY `idx_booking_requests_status` (`status`),
  KEY `idx_booking_requests_deposit_reference` (`deposit_reference`),
  UNIQUE KEY `uk_booking_requests_room_student` (`room_id`, `student_id`),
  CONSTRAINT `fk_booking_requests_room`
    FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk_booking_requests_student`
    FOREIGN KEY (`student_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk_booking_requests_landlord`
    FOREIGN KEY (`landlord_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS `request_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id` INT UNSIGNED NOT NULL,
  `sender_id` BIGINT UNSIGNED NOT NULL,
  `body` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_request_messages_request` (`request_id`),
  KEY `idx_request_messages_sender` (`sender_id`),
  CONSTRAINT `fk_request_messages_request`
    FOREIGN KEY (`request_id`) REFERENCES `booking_requests` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk_request_messages_sender`
    FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `room_id` INT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL,
  `title` VARCHAR(120) NOT NULL,
  `body` TEXT NOT NULL,
  `is_public` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_room` (`room_id`),
  KEY `idx_reviews_user` (`user_id`),
  CONSTRAINT `fk_reviews_room`
    FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `fk_reviews_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT `chk_reviews_rating` CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
