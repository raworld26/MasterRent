-- MasterRent - Phase 1
-- File: sql/009_engagement_seed.sql
-- Purpose: demo student, favorites, visit request and review.
-- Run order: After 008_engagement_schema.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';

USE `masterrent`;

INSERT INTO `users` (`email`, `password_hash`, `first_name`, `last_name`, `status`, `email_verified_at`)
VALUES
  ('studente@uniaffitti.local', '$2y$12$ZdnfvKa2yyD.Fe7ECUeFn.lzKqhoGIOwJr/yd6bsYfn2GfIwNF7fy', 'Marco', 'Rossi', 'active', NOW()),
  ('giulia@uniaffitti.local', '$2y$12$ZdnfvKa2yyD.Fe7ECUeFn.lzKqhoGIOwJr/yd6bsYfn2GfIwNF7fy', 'Giulia', 'Verdi', 'active', NOW()),
  ('luca@uniaffitti.local', '$2y$12$ZdnfvKa2yyD.Fe7ECUeFn.lzKqhoGIOwJr/yd6bsYfn2GfIwNF7fy', 'Luca', 'Neri', 'active', NOW())
ON DUPLICATE KEY UPDATE
  `first_name` = VALUES(`first_name`),
  `last_name` = VALUES(`last_name`),
  `status` = VALUES(`status`);

INSERT IGNORE INTO `users_has_groups` (`user_id`, `group_id`)
SELECT u.`id`, g.`id`
FROM `users` AS u
JOIN `user_groups` AS g ON g.`code` = 'student'
WHERE u.`email` IN ('studente@uniaffitti.local', 'giulia@uniaffitti.local', 'luca@uniaffitti.local');

DELETE FROM `request_messages`;
DELETE FROM `booking_requests`;
DELETE FROM `favorite_rooms`;
DELETE FROM `reviews`;
ALTER TABLE `booking_requests` AUTO_INCREMENT = 1;
ALTER TABLE `request_messages` AUTO_INCREMENT = 1;
ALTER TABLE `reviews` AUTO_INCREMENT = 1;

INSERT IGNORE INTO `favorite_rooms` (`user_id`, `room_id`)
SELECT u.`id`, r.`id`
FROM `users` AS u
JOIN `rooms` AS r ON r.`id` IN (1, 5, 6)
WHERE u.`email` = 'studente@uniaffitti.local';

INSERT IGNORE INTO `favorite_rooms` (`user_id`, `room_id`)
SELECT u.`id`, r.`id`
FROM `users` AS u
JOIN `rooms` AS r ON r.`id` IN (2, 7)
WHERE u.`email` = 'giulia@uniaffitti.local';

INSERT INTO `booking_requests` (`room_id`, `student_id`, `landlord_id`, `status`, `move_in_date`, `message`)
SELECT r.`id`, s.`id`, p.`landlord_id`, 'visit_requested', DATE_ADD(CURRENT_DATE, INTERVAL 20 DAY), 'Vorrei visitare questa stanza la prossima settimana.'
FROM `rooms` AS r
JOIN `properties` AS p ON p.`id` = r.`property_id`
JOIN `users` AS s ON s.`email` = 'studente@uniaffitti.local'
WHERE r.`id` = 1;

INSERT INTO `booking_requests` (`room_id`, `student_id`, `landlord_id`, `status`, `move_in_date`, `message`, `deposit_amount`, `deposit_paid_at`, `deposit_reference`, `payment_reference`)
SELECT r.`id`, s.`id`, p.`landlord_id`, 'deposit_paid', DATE_SUB(CURRENT_DATE, INTERVAL 4 DAY),
       'Richiesta approvata e caparra simulata gia versata nel seed demo.',
       r.`price_monthly`, DATE_SUB(NOW(), INTERVAL 3 DAY), 'DEMO-PAY-000005', 'DEMO-PAY-000005'
FROM `rooms` AS r
JOIN `properties` AS p ON p.`id` = r.`property_id`
JOIN `users` AS s ON s.`email` = 'giulia@uniaffitti.local'
WHERE r.`id` = 5;

UPDATE `rooms` SET `is_available` = 0, `status` = 'reserved' WHERE `id` = 5;

INSERT INTO `request_messages` (`request_id`, `sender_id`, `body`)
SELECT br.`id`, br.`student_id`, br.`message`
FROM `booking_requests` AS br
WHERE NOT EXISTS (
  SELECT 1
  FROM `request_messages` AS rm
  WHERE rm.`request_id` = br.`id`
    AND rm.`sender_id` = br.`student_id`
);

INSERT INTO `booking_request_status_history` (`request_id`, `status`, `note`, `changed_by`, `created_at`)
SELECT br.`id`, br.`status`, 'Stato demo iniziale', br.`student_id`, br.`created_at`
FROM `booking_requests` AS br;

INSERT INTO `reviews` (`room_id`, `user_id`, `rating`, `title`, `body`, `is_public`)
SELECT 5, u.`id`, 5, 'Posizione comoda', 'Stanza semplice ma vicina alle lezioni in centro.', 1
FROM `users` AS u
WHERE u.`email` = 'giulia@uniaffitti.local';
