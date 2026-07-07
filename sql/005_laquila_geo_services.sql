-- MasterRent / UniAffitti L'Aquila - Milestone 1
-- File: sql/005_laquila_geo_services.sql
-- Purpose: Register new services for neighborhoods and university poles in the admin area.
-- Run order: After 004_laquila_geo_seed.sql

USE `masterrent`;

-- Inserimento dei nuovi servizi in tabella
INSERT INTO `services` 
  (`code`, `name`, `description`, `area`, `path`, `http_method`, `is_menu_item`, `menu_order`, `is_active`)
VALUES
  ('admin.neighborhoods.index', 'Gestione Quartieri', 'Visualizza e gestisce i quartieri universitari dell\'Aquila.', 'backend', '/admin/neighborhoods/index.php', 'GET', 1, 50, 1),
  ('admin.neighborhoods.create', 'Nuovo Quartiere', 'Crea un nuovo quartiere studentesco.', 'backend', '/admin/neighborhoods/create.php', 'ALL', 0, 51, 1),
  ('admin.neighborhoods.edit', 'Modifica Quartiere', 'Modifica le informazioni di un quartiere esistente.', 'backend', '/admin/neighborhoods/edit.php', 'ALL', 0, 52, 1),
  ('admin.neighborhoods.delete', 'Elimina Quartiere', 'Elimina un quartiere studentesco.', 'backend', '/admin/neighborhoods/delete.php', 'POST', 0, 53, 1),

  ('admin.poles.index', 'Gestione Poli Didattici', 'Visualizza e gestisce i poli universitari UNIVAQ.', 'backend', '/admin/poles/index.php', 'GET', 1, 60, 1),
  ('admin.poles.create', 'Nuovo Polo Didattico', 'Crea un nuovo polo didattico.', 'backend', '/admin/poles/create.php', 'ALL', 0, 61, 1),
  ('admin.poles.edit', 'Modifica Polo Didattico', 'Modifica un polo didattico esistente.', 'backend', '/admin/poles/edit.php', 'ALL', 0, 62, 1),
  ('admin.poles.delete', 'Elimina Polo Didattico', 'Elimina un polo didattico.', 'backend', '/admin/poles/delete.php', 'POST', 0, 63, 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `area` = VALUES(`area`),
  `path` = VALUES(`path`),
  `http_method` = VALUES(`http_method`),
  `is_menu_item` = VALUES(`is_menu_item`),
  `menu_order` = VALUES(`menu_order`),
  `is_active` = VALUES(`is_active`);

-- Associazione di questi nuovi servizi al gruppo degli amministratori (admin)
INSERT IGNORE INTO `services_has_groups` (`service_id`, `group_id`)
SELECT s.`id`, g.`id`
FROM `services` AS s
JOIN `user_groups` AS g ON g.`code` = 'admin'
WHERE s.`code` LIKE 'admin.neighborhoods.%' OR s.`code` LIKE 'admin.poles.%';
