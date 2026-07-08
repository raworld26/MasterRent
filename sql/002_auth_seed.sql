-- MasterRent - Slice 1
-- File: sql/002_auth_seed.sql
-- Purpose: gruppi, catalogo COMPLETO dei servizi (modello users-groups-services)
--          e amministratore iniziale.
--
-- Default administrator:
--   email:    admin@uniaffitti.local
--   password: Admin123!
--
-- Change the password after the first login.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

USE `masterrent`;

INSERT INTO `user_groups` (`code`, `name`, `description`, `is_system`)
VALUES
  ('admin', 'Amministratori', 'Accesso completo: utenti, gruppi, servizi, dati di sistema e moderazione.', 1),
  ('landlord', 'Proprietari', 'Proprietari che pubblicano e gestiscono annunci di stanze.', 1),
  ('student', 'Studenti', 'Studenti che cercano stanze, salvano preferiti e inviano richieste.', 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `is_system` = VALUES(`is_system`);

INSERT INTO `services`
  (`code`, `name`, `description`, `area`, `path`, `http_method`, `is_menu_item`, `menu_order`, `is_active`)
VALUES
  -- Backend: voci di menu (index) + azioni di gestione (manage)
  ('backend.dashboard',          'Dashboard',     'Pannello di amministrazione.',            'backend', '/admin/index.php',                'GET', 1, 10, 1),

  ('admin.users.index',          'Utenti',        'Elenco e gestione utenti.',               'backend', '/admin/users/index.php',          'GET', 1, 20, 1),
  ('admin.users.manage',         'Gestione utenti','Creazione, modifica ed eliminazione utenti.','backend','/admin/users/create.php',      'ALL', 0, 21, 1),

  ('admin.groups.index',         'Gruppi',        'Elenco e gestione gruppi.',               'backend', '/admin/groups/index.php',         'GET', 1, 30, 1),
  ('admin.groups.manage',        'Gestione gruppi','Creazione, modifica ed eliminazione gruppi.','backend','/admin/groups/create.php',     'ALL', 0, 31, 1),

  ('admin.services.index',       'Servizi',       'Elenco e permessi dei servizi.',          'backend', '/admin/services/index.php',       'GET', 1, 40, 1),
  ('admin.services.manage',      'Gestione servizi','Creazione, modifica e permessi dei servizi.','backend','/admin/services/create.php',  'ALL', 0, 41, 1),

  ('admin.neighborhoods.index',  'Quartieri',     'Elenco e gestione quartieri.',            'backend', '/admin/neighborhoods/index.php',  'GET', 1, 50, 1),
  ('admin.neighborhoods.manage', 'Gestione quartieri','CRUD quartieri.',                     'backend', '/admin/neighborhoods/create.php', 'ALL', 0, 51, 1),

  ('admin.poles.index',          'Poli didattici','Elenco e gestione poli universitari.',    'backend', '/admin/poles/index.php',          'GET', 1, 60, 1),
  ('admin.poles.manage',         'Gestione poli', 'CRUD poli universitari.',                 'backend', '/admin/poles/create.php',         'ALL', 0, 61, 1),

  ('admin.amenities.index',      'Servizi/Accessori','Elenco accessori delle stanze.',       'backend', '/admin/amenities/index.php',      'GET', 1, 70, 1),
  ('admin.amenities.manage',     'Gestione accessori','CRUD accessori.',                     'backend', '/admin/amenities/create.php',     'ALL', 0, 71, 1),

  ('admin.properties.index',     'Annunci',       'Supervisione immobili, stanze, immagini e distanze.', 'backend', '/admin/properties/index.php', 'GET', 1, 80, 1),
  ('admin.properties.manage',    'Gestione annunci','CRUD admin per immobili, stanze, immagini e distanze dai poli.','backend','/admin/properties/view.php', 'ALL', 0, 81, 1),

  ('admin.bookings.index',       'Richieste',     'Supervisione richieste di visita e prenotazioni.', 'backend', '/admin/bookings/index.php',   'GET', 1, 90, 1),

  ('admin.reviews.index',        'Recensioni',    'Moderazione recensioni.',                 'backend', '/admin/reviews/index.php',        'GET', 1, 100, 1),
  ('admin.reviews.manage',       'Moderazione recensioni','Nascondi o elimina recensioni.',  'backend', '/admin/reviews/index.php',        'POST', 0, 101, 1),

  -- Frontend: aree private per ruolo
  ('account.home',      'Area Studente',     'Area privata dello studente.',                'frontend', '/account/index.php',     'GET', 0, 200, 1),
  ('account.profile',   'Profilo',           'Dati personali dello studente.',              'frontend', '/account/profile.php',   'ALL', 0, 201, 1),
  ('account.favorites', 'Preferiti',         'Lista delle stanze salvate.',                 'frontend', '/account/favorites.php', 'ALL', 0, 202, 1),
  ('account.bookings',  'Le mie richieste',  'Richieste di visita, approvazioni e caparre.', 'frontend', '/account/bookings.php',  'GET', 0, 203, 1),
  ('account.my_house',  'La mia casa',       'Casa o stanza attualmente associata allo studente.', 'frontend', '/account/my-house.php', 'ALL', 0, 204, 1),

  ('landlord.home',     'Area Proprietario', 'Area privata del proprietario.',              'frontend', '/landlord/index.php',    'GET', 0, 300, 1),
  ('landlord.bookings', 'Richieste ricevute','Richieste degli studenti sui propri annunci.','frontend', '/landlord/bookings.php', 'GET', 0, 301, 1),
  ('landlord.room.release', 'Libera stanza', 'Riporta una stanza reserved/unavailable ad available.', 'frontend', '/landlord/property.php', 'POST', 0, 302, 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `area` = VALUES(`area`),
  `path` = VALUES(`path`),
  `http_method` = VALUES(`http_method`),
  `is_menu_item` = VALUES(`is_menu_item`),
  `menu_order` = VALUES(`menu_order`),
  `is_active` = VALUES(`is_active`);

-- L'admin riceve tutti i servizi attivi.
INSERT IGNORE INTO `services_has_groups` (`service_id`, `group_id`)
SELECT s.`id`, g.`id`
FROM `services` AS s
JOIN `user_groups` AS g ON g.`code` = 'admin'
WHERE s.`is_active` = 1;

-- Il proprietario riceve i servizi landlord.*
INSERT IGNORE INTO `services_has_groups` (`service_id`, `group_id`)
SELECT s.`id`, g.`id`
FROM `services` AS s
JOIN `user_groups` AS g ON g.`code` = 'landlord'
WHERE s.`code` LIKE 'landlord.%';

-- Lo studente riceve i servizi account.*
INSERT IGNORE INTO `services_has_groups` (`service_id`, `group_id`)
SELECT s.`id`, g.`id`
FROM `services` AS s
JOIN `user_groups` AS g ON g.`code` = 'student'
WHERE s.`code` LIKE 'account.%';

-- Amministratore iniziale.
INSERT INTO `users`
  (`email`, `password_hash`, `first_name`, `last_name`, `phone`, `status`, `email_verified_at`)
VALUES
  ('admin@uniaffitti.local', '$2y$12$ZdnfvKa2yyD.Fe7ECUeFn.lzKqhoGIOwJr/yd6bsYfn2GfIwNF7fy', 'Amministratore', 'Sistema', NULL, 'active', NOW())
ON DUPLICATE KEY UPDATE
  `password_hash` = VALUES(`password_hash`),
  `first_name` = VALUES(`first_name`),
  `last_name` = VALUES(`last_name`),
  `status` = VALUES(`status`),
  `email_verified_at` = COALESCE(`email_verified_at`, VALUES(`email_verified_at`));

INSERT IGNORE INTO `users_has_groups` (`user_id`, `group_id`)
SELECT u.`id`, g.`id`
FROM `users` AS u
JOIN `user_groups` AS g ON g.`code` = 'admin'
WHERE u.`email` = 'admin@uniaffitti.local';
