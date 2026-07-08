-- MasterRent - Fase 1
-- File: sql/007_properties_seed.sql
-- Purpose: seed demo allineato alla Fase 2. Gli annunci provengono solo dalle
--          sottocartelle di Case (una unità affittabile per immobile).
-- Run order: After 006_properties_schema.sql

SET NAMES utf8mb4;
SET time_zone = '+00:00';
USE `masterrent`;

INSERT INTO `amenities` (`code`, `name`, `icon`)
VALUES
  ('wifi', 'Wi-Fi Fibra', 'wifi'),
  ('washing_machine', 'Lavatrice', 'washing'),
  ('dishwasher', 'Lavastoviglie', 'dishwasher'),
  ('balcony', 'Balcone / Terrazza', 'balcony'),
  ('parking', 'Posto Auto Riservato', 'parking'),
  ('desk', 'Scrivania in camera', 'desk'),
  ('private_bathroom', 'Bagno in camera', 'bath'),
  ('air_conditioning', 'Aria Condizionata', 'ac'),
  ('furnished', 'Arredato', 'furnished')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `icon` = VALUES(`icon`);

-- Utenti demo (password per tutti: Admin123!)
INSERT INTO `users` (`email`, `password_hash`, `first_name`, `last_name`, `phone`, `status`, `email_verified_at`)
VALUES
  ('odo@uniaffitti.local',       '$2y$12$ZdnfvKa2yyD.Fe7ECUeFn.lzKqhoGIOwJr/yd6bsYfn2GfIwNF7fy', 'Davide', 'Odoardi', '3401112233', 'active', NOW()),
  ('laura@uniaffitti.local',     '$2y$12$ZdnfvKa2yyD.Fe7ECUeFn.lzKqhoGIOwJr/yd6bsYfn2GfIwNF7fy', 'Laura',  'Bianchi', '3404445566', 'active', NOW()),
  ('studente@uniaffitti.local',  '$2y$12$ZdnfvKa2yyD.Fe7ECUeFn.lzKqhoGIOwJr/yd6bsYfn2GfIwNF7fy', 'Marco',  'Rossi',   '3407778899', 'active', NOW()),
  ('giulia@uniaffitti.local',    '$2y$12$ZdnfvKa2yyD.Fe7ECUeFn.lzKqhoGIOwJr/yd6bsYfn2GfIwNF7fy', 'Giulia', 'Verdi',   NULL,         'active', NOW()),
  ('luca@uniaffitti.local',      '$2y$12$ZdnfvKa2yyD.Fe7ECUeFn.lzKqhoGIOwJr/yd6bsYfn2GfIwNF7fy', 'Luca',   'Neri',    NULL,         'active', NOW())
ON DUPLICATE KEY UPDATE `first_name` = VALUES(`first_name`), `last_name` = VALUES(`last_name`);

INSERT IGNORE INTO `users_has_groups` (`user_id`, `group_id`)
SELECT u.`id`, g.`id` FROM `users` AS u JOIN `user_groups` AS g ON g.`code` = 'landlord'
WHERE u.`email` IN ('odo@uniaffitti.local', 'laura@uniaffitti.local');

INSERT IGNORE INTO `users_has_groups` (`user_id`, `group_id`)
SELECT u.`id`, g.`id` FROM `users` AS u JOIN `user_groups` AS g ON g.`code` = 'student'
WHERE u.`email` IN ('studente@uniaffitti.local', 'giulia@uniaffitti.local', 'luca@uniaffitti.local');

-- Reset del catalogo demo: elimina gli annunci precedenti e i dati collegati
-- tramite cascade, poi riparte da ID deterministici per i seed successivi.
DELETE FROM `properties`;
ALTER TABLE `properties` AUTO_INCREMENT = 1;
ALTER TABLE `rooms` AUTO_INCREMENT = 1;
ALTER TABLE `property_images` AUTO_INCREMENT = 1;

-- ---------------------------------------------------------------------------
-- Immobili realizzati esclusivamente dalle sottocartelle di Case.
-- ---------------------------------------------------------------------------
INSERT INTO `properties` (`landlord_id`, `neighborhood_id`, `title`, `description`, `address`, `house_number`, `postal_code`, `total_rooms`, `has_elevator`, `heating_type`)
VALUES
  ((SELECT id FROM users WHERE email='laura@uniaffitti.local'), (SELECT id FROM neighborhoods WHERE code='torrione_strinella'),
   'Bilocale arredato',
   'Bilocale moderno in Contrada Sant''Elia, adatto a uno studente o a una coppia di studenti che cercano autonomia senza allontanarsi dai servizi. La zona giorno con cucina a vista e divano rende l''ambiente pratico per studio e quotidianita.',
   'Contrada Sant''Elia', '4', '67100', 2, 0, 'autonomous'),

  ((SELECT id FROM users WHERE email='odo@uniaffitti.local'), (SELECT id FROM neighborhoods WHERE code='centro'),
   'Appartamento condiviso',
   'Soluzione centrale in Corso Vittorio Emanuele II, pensata per chi vuole vivere nel cuore dell''Aquila e muoversi a piedi tra lezioni, biblioteche e servizi. Camera singola arredata, cucina abitabile e ambienti funzionali.',
   'Corso Vittorio Emanuele II', NULL, '67100', 3, 0, 'centralized'),

  ((SELECT id FROM users WHERE email='laura@uniaffitti.local'), (SELECT id FROM neighborhoods WHERE code='croce_rossa_santa_barbara'),
   'Mini appartamento',
   'Mini appartamento arredato in Via Delle Nocelle, con camera luminosa, angolo cucina e bagno dedicato. Una soluzione riservata e ordinata per studenti che preferiscono spazi autonomi e costi prevedibili.',
   'Via Delle Nocelle', '85', '67100', 2, 0, 'autonomous'),

  ((SELECT id FROM users WHERE email='odo@uniaffitti.local'), (SELECT id FROM neighborhoods WHERE code='croce_rossa_santa_barbara'),
   'Appartamento compatto',
   'Appartamento luminoso e ordinato in Via Gennaro Manna, con zona giorno essenziale e spazi facili da gestire. Indicato per studenti che cercano una casa completa in un contesto residenziale ben collegato.',
   'Via Gennaro Manna', '33', '67100', 2, 1, 'autonomous'),

  ((SELECT id FROM users WHERE email='laura@uniaffitti.local'), (SELECT id FROM neighborhoods WHERE code='centro'),
   'Casa condivisa',
   'Casa condivisa in Via Goriano Valle, con zona giorno ampia, area pranzo e camera doppia arredata. Ideale per studenti che cercano una soluzione centrale, semplice da condividere e pronta per l''anno accademico.',
   'Via Goriano Valle', '47', '67100', 3, 0, 'autonomous'),

  ((SELECT id FROM users WHERE email='odo@uniaffitti.local'), (SELECT id FROM neighborhoods WHERE code='pile_pettino'),
   'Mansarda arredata',
   'Mansarda arredata in Via Nicola Lombardi, con zona giorno luminosa, camera spaziosa e bagno dedicato. Soluzione adatta a studenti che vogliono indipendenza in una zona tranquilla e ben servita.',
   'Via Nicola Lombardi', '12', '67100', 2, 0, 'autonomous'),

  ((SELECT id FROM users WHERE email='laura@uniaffitti.local'), (SELECT id FROM neighborhoods WHERE code='pile_pettino'),
   'Open space moderno',
   'Open space arredato in Via Uruguay, con cucina compatta, divano, zona notte e bagno moderno. Una proposta curata per studenti che cercano un appartamento autonomo, luminoso e facile da mantenere.',
   'Via Uruguay', '6', '67100', 2, 0, 'autonomous');

-- Distanze indicative dai poli universitari.
INSERT INTO `property_has_poles` (`property_id`, `pole_id`, `distance_minutes`, `transit_type`) VALUES
  (1, (SELECT id FROM university_poles WHERE code='polo_centro'), 13, 'bus'),
  (2, (SELECT id FROM university_poles WHERE code='polo_centro'), 3, 'foot'),
  (2, (SELECT id FROM university_poles WHERE code='polo_coppito'), 22, 'bus'),
  (3, (SELECT id FROM university_poles WHERE code='polo_coppito'), 14, 'bus'),
  (3, (SELECT id FROM university_poles WHERE code='polo_centro'), 17, 'bus'),
  (4, (SELECT id FROM university_poles WHERE code='polo_centro'), 10, 'bus'),
  (4, (SELECT id FROM university_poles WHERE code='polo_roio'), 16, 'bus'),
  (5, (SELECT id FROM university_poles WHERE code='polo_centro'), 6, 'foot'),
  (5, (SELECT id FROM university_poles WHERE code='polo_coppito'), 21, 'bus'),
  (6, (SELECT id FROM university_poles WHERE code='polo_coppito'), 9, 'bus'),
  (6, (SELECT id FROM university_poles WHERE code='polo_centro'), 17, 'bus'),
  (7, (SELECT id FROM university_poles WHERE code='polo_roio'), 13, 'bus'),
  (7, (SELECT id FROM university_poles WHERE code='polo_centro'), 15, 'bus');

-- Unita affittabili: una sola unita per ogni sottocartella Case.
INSERT INTO `rooms` (`property_id`, `name`, `type`, `price_monthly`, `deposit_months`, `expenses_included`, `contract_type`, `is_available`, `status`) VALUES
  (1, 'Camera singola luminosa',      'single',           250.00, 1, 0, 'Transitorio Studenti', 1, 'available'),
  (2, 'Camera singola centrale',      'single',           310.00, 1, 0, 'Transitorio Studenti', 1, 'available'),
  (3, 'Camera singola tranquilla',    'single',           220.00, 1, 0, 'Transitorio Studenti', 1, 'available'),
  (4, 'Camera singola ben collegata', 'single',           280.00, 1, 0, 'Transitorio Studenti', 1, 'available'),
  (5, 'Camera doppia condivisa',      'double',           200.00, 1, 1, 'Transitorio Studenti', 1, 'available'),
  (6, 'Camera singola spaziosa',      'single',           260.00, 1, 0, 'Transitorio Studenti', 1, 'available'),
  (7, 'Camera singola moderna',       'single',           290.00, 1, 0, 'Transitorio Studenti', 1, 'available');

UPDATE `rooms` SET `created_at` = DATE_SUB(NOW(), INTERVAL (
  CASE WHEN id IN (1, 4, 7) THEN 3
       WHEN id IN (2, 5) THEN 12
       ELSE 28 + (id * 3) END) DAY);

-- Gallerie: ogni immobile usa solo le immagini della propria sottocartella Case.
INSERT INTO `property_images` (`property_id`, `filename`, `is_cover`, `caption`) VALUES
  (1, 'case/contrada_santelia_4_zona_giorno.avif', 1, 'Zona giorno con cucina a vista e divano'),
  (1, 'case/contrada_santelia_4_camera.avif', 0, 'Camera arredata dell''appartamento'),
  (1, 'case/contrada_santelia_4_bagno.avif', 0, 'Bagno con doccia'),
  (2, 'case/corso_vittorio_emanuele_ii_cucina.avif', 1, 'Cucina abitabile nel centro storico'),
  (2, 'case/corso_vittorio_emanuele_ii_camera.avif', 0, 'Camera singola arredata'),
  (2, 'case/corso_vittorio_emanuele_ii_bagno.avif', 0, 'Ambiente bagno e lavanderia'),
  (3, 'case/via_delle_nocelle_85_camera.avif', 1, 'Camera singola con scrivania'),
  (3, 'case/via_delle_nocelle_85_cucina.avif', 0, 'Cucina compatta'),
  (3, 'case/via_delle_nocelle_85_bagno.avif', 0, 'Bagno finestrato'),
  (4, 'case/via_gennaro_manna_33_living.avif', 1, 'Soggiorno con zona pranzo'),
  (4, 'case/via_gennaro_manna_33_pranzo.avif', 0, 'Ingresso e ambiente living'),
  (4, 'case/via_gennaro_manna_33_bagno.avif', 0, 'Bagno con doccia'),
  (5, 'case/via_goriano_valle_47_living.avif', 1, 'Zona living della casa condivisa'),
  (5, 'case/via_goriano_valle_47_camera_doppia.avif', 0, 'Camera doppia arredata'),
  (5, 'case/via_goriano_valle_47_bagno.avif', 0, 'Bagno ristrutturato'),
  (5, 'case/via_goriano_valle_47_pranzo.avif', 0, 'Zona pranzo condivisa'),
  (6, 'case/via_nicola_lombardi_12_zona_giorno.avif', 1, 'Zona giorno luminosa'),
  (6, 'case/via_nicola_lombardi_12_camera.avif', 0, 'Camera spaziosa arredata'),
  (6, 'case/via_nicola_lombardi_12_bagno.avif', 0, 'Bagno dell''appartamento'),
  (7, 'case/via_uruguay_6_open_space.avif', 1, 'Open space con cucina e divano'),
  (7, 'case/via_uruguay_6_zona_notte.avif', 0, 'Zona notte arredata'),
  (7, 'case/via_uruguay_6_bagno.avif', 0, 'Bagno moderno con lavanderia');

-- Accessori per stanza.
INSERT IGNORE INTO `room_has_amenities` (`room_id`, `amenity_id`)
SELECT r.id, a.id FROM rooms r JOIN amenities a ON a.code IN ('wifi','washing_machine','furnished')
WHERE r.id IN (1,7);

INSERT IGNORE INTO `room_has_amenities` (`room_id`, `amenity_id`)
SELECT r.id, a.id FROM rooms r JOIN amenities a ON a.code IN ('wifi','desk','furnished')
WHERE r.id IN (2,3);

INSERT IGNORE INTO `room_has_amenities` (`room_id`, `amenity_id`)
SELECT r.id, a.id FROM rooms r JOIN amenities a ON a.code IN ('wifi','furnished')
WHERE r.id IN (4,5,6);

INSERT IGNORE INTO `room_has_amenities` (`room_id`, `amenity_id`)
SELECT r.id, a.id FROM rooms r JOIN amenities a ON a.code='private_bathroom'
WHERE r.id IN (1,3,6,7);

INSERT IGNORE INTO `room_has_amenities` (`room_id`, `amenity_id`)
SELECT r.id, a.id FROM rooms r JOIN amenities a ON a.code='air_conditioning'
WHERE r.id IN (2,7);
