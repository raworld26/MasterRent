-- UniAffitti L'Aquila — Fase 2
-- File: sql/03_properties_schema.sql
-- Annunci: immobili, stanze (unità affittabile), accessori, relazioni, immagini.

SET NAMES utf8mb4;
USE `uniaffitti`;

CREATE TABLE IF NOT EXISTS `properties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `landlord_id` BIGINT UNSIGNED NOT NULL,
  `neighborhood_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `address` VARCHAR(190) NOT NULL,
  `house_number` VARCHAR(20) DEFAULT NULL,
  `postal_code` VARCHAR(10) NOT NULL DEFAULT '67100',
  `total_rooms` INT UNSIGNED NOT NULL DEFAULT 1,
  `has_elevator` TINYINT(1) NOT NULL DEFAULT 0,
  `heating_type` ENUM('autonomous','centralized') NOT NULL DEFAULT 'autonomous',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_properties_landlord` (`landlord_id`),
  KEY `idx_properties_neighborhood` (`neighborhood_id`),
  CONSTRAINT `fk_properties_landlord` FOREIGN KEY (`landlord_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_properties_neighborhood` FOREIGN KEY (`neighborhood_id`) REFERENCES `neighborhoods` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `type` ENUM('single','double','bed_space','entire_apartment') NOT NULL DEFAULT 'single',
  `price_monthly` DECIMAL(10,2) NOT NULL DEFAULT '0.00',
  `deposit_months` INT UNSIGNED NOT NULL DEFAULT 2,
  `expenses_included` TINYINT(1) NOT NULL DEFAULT 0,
  `contract_type` VARCHAR(100) NOT NULL DEFAULT 'Transitorio Studenti',
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  -- Stato del ciclo di prenotazione dell'unità:
  --   available   → pubblicabile e prenotabile
  --   reserved    → caparra pagata, non più prenotabile
  --   unavailable → ritirata/non prenotabile (scelta del proprietario)
  `status` ENUM('available','reserved','unavailable') NOT NULL DEFAULT 'available',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rooms_property` (`property_id`),
  KEY `idx_rooms_availability` (`is_available`),
  CONSTRAINT `fk_rooms_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `amenities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(50) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `icon` VARCHAR(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_amenities_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `room_has_amenities` (
  `room_id` INT UNSIGNED NOT NULL,
  `amenity_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`room_id`, `amenity_id`),
  KEY `idx_rha_amenity` (`amenity_id`),
  CONSTRAINT `fk_rha_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_rha_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `property_has_poles` (
  `property_id` INT UNSIGNED NOT NULL,
  `pole_id` INT UNSIGNED NOT NULL,
  `distance_minutes` INT UNSIGNED NOT NULL,
  `transit_type` ENUM('foot','bus','car') NOT NULL DEFAULT 'foot',
  PRIMARY KEY (`property_id`, `pole_id`),
  KEY `idx_php_pole` (`pole_id`),
  CONSTRAINT `fk_php_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `fk_php_pole` FOREIGN KEY (`pole_id`) REFERENCES `university_poles` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `property_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `property_id` INT UNSIGNED NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `is_cover` TINYINT(1) NOT NULL DEFAULT 0,
  `caption` VARCHAR(150) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_property_images_property` (`property_id`),
  CONSTRAINT `fk_property_images_property` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
