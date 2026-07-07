-- MasterRent / UniAffitti L'Aquila - Milestone 1
-- File: sql/004_laquila_geo_seed.sql
-- Purpose: Seed data for L'Aquila neighborhoods and university poles.
-- Run order: After 003_laquila_geo_schema.sql

USE `masterrent`;

-- Popolamento dei quartieri studenteschi dell'Aquila
INSERT INTO `neighborhoods` (`code`, `name`, `description`)
VALUES
  ('pettino', 'Pettino', 'Zona residenziale molto vicina al polo didattico di Coppito e all\'ospedale. Molto servita da autobus e supermercati.'),
  ('coppito', 'Coppito (Paese)', 'Il borgo storico di Coppito, adiacente al polo universitario. Perfetto per chi desidera andare a lezione a piedi.'),
  ('sant_antonio', 'Sant\'Antonio', 'Zona collinare e tranquilla strategica, situata tra il polo di Coppito e il centro cittadino.'),
  ('piazza_armi', 'Piazza d\'Armi / Corrado IV', 'Area pianeggiante molto commerciale, vicina al terminal bus di Collemaggio e ben collegata con tutti i poli.'),
  ('centro_storico', 'Centro Storico', 'Il cuore culturale dell\'Aquila, vivace la sera e vicinissimo al polo di Scienze Umane ed Economia.'),
  ('torrione', 'Torrione / Santa Barbara', 'Quartiere residenziale storico, tranquillo e ben servito, comodo per raggiungere il centro o il polo di Acquasanta.'),
  ('pile', 'Pile / Stazione', 'Zona vicina alla stazione ferroviaria e al nucleo industriale, ricca di servizi e centri commerciali.'),
  ('centro', 'Centro', 'Il cuore culturale dell\'Aquila, vivace la sera e vicinissimo al polo di Scienze Umane ed Economia.'),
  ('torrione_strinella', 'Torrione/Strinella', 'Zona residenziale storica e molto richiesta, a due passi dal centro e con ottimi servizi.'),
  ('croce_rossa_santa_barbara', 'Croce Rossa/Santa Barbara', 'Quartiere ben servito e strategico, ottimi collegamenti verso il centro e la viabilita principale.'),
  ('pile_pettino', 'Pile/Pettino', 'Zona residenziale commerciale e studentesca, lungo l\'asse principale che collega ospedale e atenei.'),
  ('coppito_ospedale', 'Coppito/Universita/Ospedale', 'Adiacente al polo universitario scientifico e all\'ospedale regionale, perfetto per chi studia materie scientifiche.'),
  ('roio_ingegneria', 'Roio/Ingegneria', 'In prossimita del polo di Ingegneria a Monteluco di Roio, immerso nel verde.'),
  ('paganica_tempera', 'Paganica/Tempera', 'Frazioni ampie e autonome a est della citta, con costi piu contenuti e una dimensione di paese.'),
  ('bazzano_est', 'Bazzano/Est', 'Importante snodo commerciale a est dell\'Aquila, ben collegato dalla viabilita extraurbana.'),
  ('sassa_preturo', 'Sassa/Preturo', 'Area a ovest della citta, residenziale e tranquilla, vicina allo scalo aeroportuale.'),
  ('frazioni_gran_sasso', 'Frazioni/Gran Sasso', 'Localita limitrofe immerse nel verde e ai piedi della montagna, ideali per chi cerca tranquillita assoluta.')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`);

-- Popolamento dei poli universitari dell'Ateneo (UNIVAQ)
INSERT INTO `university_poles` (`code`, `name`, `description`)
VALUES
  ('polo_coppito', 'Polo Didattico di Coppito (Blocco 0 / 1 / 11)', 'Ospita i dipartimenti di Matematica, Fisica, Informatica, Chimica, Biologia e Medicina.'),
  ('polo_roio', 'Polo Didattico di Roio (Monteluco)', 'Sede storica e panoramica dei dipartimenti di Ingegneria Civile, Edile, Ambientale e Industriale.'),
  ('polo_centro', 'Polo del Centro Storico (DSU / Economia)', 'Ospita i dipartimenti di Scienze Umane, Lettere, Filosofia e il dipartimento di Economia.'),
  ('polo_acquasanta', 'Polo di Acquasanta / Colle dell\'Ancona', 'Ospita i corsi di Scienze Motorie e le relative strutture sportive.')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`);
