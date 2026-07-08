-- MasterRent / UniAffitti L'Aquila
-- File: sql/004_laquila_geo_seed.sql
-- Purpose: quartieri studenteschi dell'Aquila e poli universitari UNIVAQ
--          (macro-zone allineate a src/ZoneEstimates.php).
-- Run order: After 003_laquila_geo_schema.sql

SET NAMES utf8mb4;
USE `masterrent`;

SET FOREIGN_KEY_CHECKS=0;
TRUNCATE TABLE `neighborhoods`;

INSERT INTO `neighborhoods` (`code`, `name`, `description`)
VALUES
  ('centro', 'Centro', 'Il cuore culturale dell''Aquila, vivace la sera e vicinissimo al polo di Scienze Umane ed Economia.'),
  ('torrione_strinella', 'Torrione/Strinella', 'Zona residenziale storica e molto richiesta, a due passi dal centro e con ottimi servizi.'),
  ('croce_rossa_santa_barbara', 'Croce Rossa/Santa Barbara', 'Quartiere ben servito e strategico, ottimi collegamenti verso il centro e la viabilità principale.'),
  ('pile_pettino', 'Pile/Pettino', 'Zona residenziale commerciale e studentesca, lungo l''asse principale che collega ospedale e atenei.'),
  ('coppito_ospedale', 'Coppito/Università/Ospedale', 'Adiacente al polo universitario scientifico e all''ospedale regionale, perfetto per chi studia materie scientifiche.'),
  ('roio_ingegneria', 'Roio/Ingegneria', 'In prossimità del polo di Ingegneria a Monteluco di Roio, immerso nel verde.'),
  ('paganica_tempera', 'Paganica/Tempera', 'Frazioni ampie e autonome a est della città, con costi più contenuti e una dimensione di paese.'),
  ('bazzano_est', 'Bazzano/Est', 'Importante snodo commerciale a est dell''Aquila, ben collegato dalla viabilità extraurbana.'),
  ('sassa_preturo', 'Sassa/Preturo', 'Area a ovest della città, residenziale e tranquilla, vicina allo scalo aeroportuale.'),
  ('frazioni_gran_sasso', 'Frazioni/Gran Sasso', 'Località limitrofe immerse nel verde e ai piedi della montagna, ideali per chi cerca tranquillità assoluta.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

SET FOREIGN_KEY_CHECKS=1;

INSERT INTO `university_poles` (`code`, `name`, `description`)
VALUES
  ('polo_coppito', 'Polo Didattico di Coppito (Blocco 0/1/11)', 'Matematica, Fisica, Informatica, Chimica, Biologia e Medicina.'),
  ('polo_roio', 'Polo Didattico di Roio (Monteluco)', 'Ingegneria Civile, Edile, Ambientale e Industriale.'),
  ('polo_centro', 'Polo del Centro Storico (DSU / Economia)', 'Scienze Umane, Lettere, Filosofia ed Economia.')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);
