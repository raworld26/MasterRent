-- UniAffitti L'Aquila - Fase 2
-- File: sql/08_demo_engagement.sql
-- Dati demo allineati al seed pulito di sql/06_demo_seed.sql.

SET NAMES utf8mb4;
USE `uniaffitti`;

-- Reset dati di engagement per rendere il seed reimportabile senza duplicati.
DELETE FROM `messages`;
DELETE FROM `booking_status_history`;
DELETE FROM `bookings`;
DELETE FROM `favorites`;
DELETE FROM `reviews`;
ALTER TABLE `messages` AUTO_INCREMENT = 1;
ALTER TABLE `booking_status_history` AUTO_INCREMENT = 1;
ALTER TABLE `bookings` AUTO_INCREMENT = 1;
ALTER TABLE `reviews` AUTO_INCREMENT = 1;

-- Preferiti.
INSERT IGNORE INTO `favorites` (`user_id`, `room_id`) VALUES
  (4, 1), (4, 5), (4, 6),
  (5, 2), (5, 7);

-- Richieste demo: nessuna stanza viene marcata reserved, cosi tutte le case
-- della cartella Case restano visibili nella ricerca pubblica.
INSERT INTO `bookings` (`room_id`, `student_id`, `status`, `message`, `move_in_date`, `deposit_amount`, `deposit_paid_at`, `deposit_reference`) VALUES
  (1, 5, 'approved_pending_deposit', 'Buongiorno, il bilocale in Contrada Sant''Elia e ancora disponibile?', '2026-10-01', NULL, NULL, NULL),
  (7, 6, 'visit_requested',          'Ciao, vorrei informazioni sull''open space in Via Uruguay.', '2026-09-20', NULL, NULL, NULL),
  (5, 4, 'rejected',                 'Salve, mi piacerebbe visitare la camera doppia in Via Goriano Valle. Sono libero il pomeriggio.', '2026-09-25', NULL, NULL, NULL),
  (3, 4, 'withdrawn',                'Ciao, avevo chiesto informazioni sul mini appartamento ma ho risolto diversamente.', '2026-09-10', NULL, NULL, NULL);

INSERT INTO `booking_status_history` (`booking_id`, `status`, `note`, `changed_by`) VALUES
  (1, 'visit_requested',          'Richiesta di visita inviata', 5),
  (1, 'approved_pending_deposit', 'Richiesta approvata dopo la visita', 3),
  (2, 'visit_requested',          'Richiesta di visita inviata', 6),
  (3, 'visit_requested',          'Richiesta di visita inviata', 4),
  (3, 'rejected',                 'Camera non disponibile per quelle date', 3),
  (4, 'visit_requested',          'Richiesta di visita inviata', 4),
  (4, 'withdrawn',                'Richiesta ritirata dallo studente', 4);

INSERT INTO `messages` (`booking_id`, `sender_id`, `body`, `read_at`) VALUES
  (1, 5, 'Buongiorno, il bilocale in Contrada Sant''Elia e ancora disponibile?', NOW()),
  (1, 3, 'Salve Giulia, si e disponibile. Dopo la visita le confermo l''approvazione cosi puo versare la caparra.', NULL),
  (2, 6, 'Ciao, vorrei informazioni sull''open space in Via Uruguay.', NOW()),
  (3, 4, 'Salve, mi piacerebbe visitare la camera doppia in Via Goriano Valle. Sono libero il pomeriggio.', NOW()),
  (3, 3, 'Ciao Marco, mi dispiace ma per quelle date non posso confermare la richiesta.', NULL),
  (4, 4, 'Ciao, avevo chiesto informazioni sul mini appartamento ma ho risolto diversamente.', NOW());
