-- MasterRent - Slice 1
-- File: sql/002_auth_seed.sql
-- Purpose: base groups, services, service permissions and first administrator.
-- Compatibility: XAMPP with MySQL/MariaDB.
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
  ('admin', 'Administrators', 'Full management access: users, groups, services, system data and moderation.', 1),
  ('landlord', 'Landlords', 'Owners who publish and manage room listings.', 1),
  ('student', 'Students', 'Students who search rooms, save favorites and submit rental requests.', 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `is_system` = VALUES(`is_system`);

INSERT INTO `services`
  (`code`, `name`, `description`, `area`, `path`, `http_method`, `is_menu_item`, `menu_order`, `is_active`)
VALUES
  ('backend.dashboard', 'Admin dashboard', 'Access to the administration dashboard.', 'backend', '/admin/index.php', 'GET', 1, 10, 1),

  ('admin.users.index', 'List users', 'View user list in the administration backend.', 'backend', '/admin/users/index.php', 'GET', 1, 20, 1),
  ('admin.users.create', 'Create users', 'Create new users.', 'backend', '/admin/users/create.php', 'ALL', 0, 21, 1),
  ('admin.users.edit', 'Edit users', 'Update existing users.', 'backend', '/admin/users/edit.php', 'ALL', 0, 22, 1),
  ('admin.users.delete', 'Delete users', 'Disable or delete users.', 'backend', '/admin/users/delete.php', 'POST', 0, 23, 1),

  ('admin.groups.index', 'List groups', 'View user groups.', 'backend', '/admin/groups/index.php', 'GET', 1, 30, 1),
  ('admin.groups.create', 'Create groups', 'Create new user groups.', 'backend', '/admin/groups/create.php', 'ALL', 0, 31, 1),
  ('admin.groups.edit', 'Edit groups', 'Update user groups.', 'backend', '/admin/groups/edit.php', 'ALL', 0, 32, 1),
  ('admin.groups.delete', 'Delete groups', 'Delete non-system user groups.', 'backend', '/admin/groups/delete.php', 'POST', 0, 33, 1),

  ('admin.services.index', 'List services', 'View protected services and permissions.', 'backend', '/admin/services/index.php', 'GET', 1, 40, 1),
  ('admin.services.create', 'Create services', 'Create protected services.', 'backend', '/admin/services/create.php', 'ALL', 0, 41, 1),
  ('admin.services.edit', 'Edit services', 'Update protected services and permission metadata.', 'backend', '/admin/services/edit.php', 'ALL', 0, 42, 1),
  ('admin.services.delete', 'Delete services', 'Delete protected services.', 'backend', '/admin/services/delete.php', 'POST', 0, 43, 1),

  ('admin.amenities.index', 'List amenities', 'View room amenities in the administration backend.', 'backend', '/admin/amenities/index.php', 'GET', 1, 45, 1),
  ('admin.amenities.create', 'Create amenities', 'Create new room amenities.', 'backend', '/admin/amenities/create.php', 'ALL', 0, 46, 1),
  ('admin.amenities.edit', 'Edit amenities', 'Update room amenities.', 'backend', '/admin/amenities/edit.php', 'ALL', 0, 47, 1),
  ('admin.amenities.delete', 'Delete amenities', 'Delete room amenities.', 'backend', '/admin/amenities/delete.php', 'POST', 0, 48, 1),

  ('admin.properties.index', 'List properties', 'View property listings in the administration backend.', 'backend', '/admin/properties/index.php', 'GET', 1, 50, 1),
  ('admin.properties.create', 'Create properties', 'Create property listings for landlords.', 'backend', '/admin/properties/create.php', 'ALL', 0, 51, 1),
  ('admin.properties.edit', 'Edit properties', 'Update property listing metadata.', 'backend', '/admin/properties/edit.php', 'ALL', 0, 52, 1),
  ('admin.properties.delete', 'Delete properties', 'Delete property listings and related data.', 'backend', '/admin/properties/delete.php', 'POST', 0, 53, 1),

  ('admin.images.index', 'List property images', 'View property image gallery entries in the administration backend.', 'backend', '/admin/images/index.php', 'GET', 1, 54, 1),
  ('admin.images.create', 'Create property images', 'Upload new property images.', 'backend', '/admin/images/create.php', 'ALL', 0, 59, 1),
  ('admin.images.edit', 'Edit property images', 'Update property image captions, cover flag and file.', 'backend', '/admin/images/edit.php', 'ALL', 0, 60, 1),
  ('admin.images.delete', 'Delete property images', 'Delete property image records and generated uploads.', 'backend', '/admin/images/delete.php', 'POST', 0, 61, 1),

  ('admin.rooms.index', 'List rooms', 'View rooms and bed places in the administration backend.', 'backend', '/admin/rooms/index.php', 'GET', 1, 55, 1),
  ('admin.rooms.create', 'Create rooms', 'Create rooms for existing property listings.', 'backend', '/admin/rooms/create.php', 'ALL', 0, 56, 1),
  ('admin.rooms.edit', 'Edit rooms', 'Update room prices, availability and amenities.', 'backend', '/admin/rooms/edit.php', 'ALL', 0, 57, 1),
  ('admin.rooms.delete', 'Delete rooms', 'Delete rooms and related engagement data.', 'backend', '/admin/rooms/delete.php', 'POST', 0, 58, 1),

  ('admin.property_poles.index', 'List property pole distances', 'View links between property listings and university poles.', 'backend', '/admin/property_poles/index.php', 'GET', 1, 62, 1),
  ('admin.property_poles.create', 'Create property pole distances', 'Create links between property listings and university poles.', 'backend', '/admin/property_poles/create.php', 'ALL', 0, 63, 1),
  ('admin.property_poles.edit', 'Edit property pole distances', 'Update property-pole distance and transit type.', 'backend', '/admin/property_poles/edit.php', 'ALL', 0, 64, 1),
  ('admin.property_poles.delete', 'Delete property pole distances', 'Delete links between property listings and university poles.', 'backend', '/admin/property_poles/delete.php', 'POST', 0, 65, 1),

  ('admin.requests.index', 'List visit requests', 'View visit requests in the administration backend.', 'backend', '/admin/requests/index.php', 'GET', 1, 70, 1),
  ('admin.requests.create', 'Create visit requests', 'Create visit requests for students.', 'backend', '/admin/requests/create.php', 'ALL', 0, 71, 1),
  ('admin.requests.edit', 'Edit visit requests', 'Update visit request status, date and message.', 'backend', '/admin/requests/edit.php', 'ALL', 0, 72, 1),
  ('admin.requests.delete', 'Delete visit requests', 'Delete visit requests and related thread messages.', 'backend', '/admin/requests/delete.php', 'POST', 0, 73, 1),
  ('admin.bookings.index', 'List bookings', 'View booking requests, deposits and status lifecycle.', 'backend', '/admin/bookings/index.php', 'GET', 1, 70, 1),

  ('admin.reviews.index', 'List reviews', 'View student reviews in the administration backend.', 'backend', '/admin/reviews/index.php', 'GET', 1, 75, 1),
  ('admin.reviews.create', 'Create reviews', 'Create new student reviews.', 'backend', '/admin/reviews/create.php', 'ALL', 0, 76, 1),
  ('admin.reviews.edit', 'Edit reviews', 'Update student reviews and visibility.', 'backend', '/admin/reviews/edit.php', 'ALL', 0, 77, 1),
  ('admin.reviews.delete', 'Delete reviews', 'Delete student reviews.', 'backend', '/admin/reviews/delete.php', 'POST', 0, 78, 1),

  ('account.profile', 'Account profile', 'View and update personal account data.', 'frontend', '/account/profile.php', 'ALL', 0, 100, 1),
  ('account.home', 'Student account home', 'Student private dashboard.', 'frontend', '/account/index.php', 'GET', 0, 101, 1),
  ('account.favorites', 'Student favorites', 'View saved room favorites.', 'frontend', '/account/favorites.php', 'ALL', 0, 102, 1),
  ('account.bookings', 'Student bookings', 'View booking requests and deposits.', 'frontend', '/account/bookings.php', 'GET', 0, 103, 1),
  ('booking.view', 'Booking thread', 'Unified booking thread for students, landlords and admins.', 'frontend', '/booking.php', 'ALL', 0, 104, 1),
  ('deposit.pay', 'Deposit payment', 'Simulated deposit payment page.', 'frontend', '/deposit.php', 'ALL', 0, 105, 1),
  ('landlord.dashboard', 'Landlord dashboard', 'Base landlord private area.', 'frontend', '/landlord/dashboard.php', 'GET', 0, 110, 1),
  ('landlord.home', 'Landlord home', 'Landlord private dashboard.', 'frontend', '/landlord/index.php', 'GET', 0, 110, 1),
  ('landlord.bookings', 'Landlord bookings', 'View booking requests received by landlord.', 'frontend', '/landlord/bookings.php', 'GET', 0, 111, 1),
  ('landlord.property.create', 'Create property', 'Create a new property listing.', 'frontend', '/landlord/create_property.php', 'ALL', 0, 111, 1),
  ('landlord.room.create', 'Create room', 'Create rooms for a property.', 'frontend', '/landlord/add_room.php', 'ALL', 0, 112, 1),
  ('landlord.request.manage', 'Manage visit request', 'View request threads, answer students and update visit request status.', 'frontend', '/landlord/request.php', 'ALL', 0, 113, 1),
  ('landlord.property.edit', 'Edit own property', 'Update own property listings.', 'frontend', '/landlord/edit_property.php', 'ALL', 0, 114, 1),
  ('landlord.room.edit', 'Edit own room', 'Update own room details, price and availability.', 'frontend', '/landlord/edit_room.php', 'ALL', 0, 115, 1),
  ('landlord.images.manage', 'Manage own property images', 'Upload, delete and choose cover images for own listings.', 'frontend', '/landlord/images.php', 'ALL', 0, 116, 1),
  ('landlord.poles.manage', 'Manage own property pole links', 'Create and remove links between own listings and university poles.', 'frontend', '/landlord/poles.php', 'ALL', 0, 117, 1),
  ('landlord.room.release', 'Release own room', 'Put a reserved or unavailable room back in search.', 'frontend', '/landlord/property.php', 'POST', 0, 118, 1),
  ('student.dashboard', 'Student dashboard', 'Base student private area.', 'frontend', '/student/dashboard.php', 'GET', 0, 120, 1),
  ('student.request.view', 'Student visit request', 'View and answer own visit request threads.', 'frontend', '/student/request.php', 'ALL', 0, 121, 1),
  ('admin.requests.detail', 'Visit request detail', 'View request thread, deposit and status detail from the administration backend.', 'backend', '/admin/requests/detail.php', 'ALL', 0, 74, 1)
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `description` = VALUES(`description`),
  `area` = VALUES(`area`),
  `path` = VALUES(`path`),
  `http_method` = VALUES(`http_method`),
  `is_menu_item` = VALUES(`is_menu_item`),
  `menu_order` = VALUES(`menu_order`),
  `is_active` = VALUES(`is_active`);

-- Admin group gets all current services.
INSERT IGNORE INTO `services_has_groups` (`service_id`, `group_id`)
SELECT s.`id`, g.`id`
FROM `services` AS s
JOIN `user_groups` AS g ON g.`code` = 'admin'
WHERE s.`is_active` = 1;

-- Logged-in non-admin users get their own profile page.
INSERT IGNORE INTO `services_has_groups` (`service_id`, `group_id`)
SELECT s.`id`, g.`id`
FROM `services` AS s
JOIN `user_groups` AS g ON g.`code` IN ('landlord', 'student')
WHERE s.`code` IN ('account.profile', 'booking.view');

-- Role-specific private dashboards.
INSERT IGNORE INTO `services_has_groups` (`service_id`, `group_id`)
SELECT s.`id`, g.`id`
FROM `services` AS s
JOIN `user_groups` AS g ON g.`code` = 'landlord'
WHERE s.`code` IN (
  'landlord.dashboard',
  'landlord.home',
  'landlord.bookings',
  'landlord.property.create',
  'landlord.room.create',
  'landlord.request.manage',
  'landlord.property.edit',
  'landlord.room.edit',
  'landlord.images.manage',
  'landlord.poles.manage',
  'landlord.room.release'
);

INSERT IGNORE INTO `services_has_groups` (`service_id`, `group_id`)
SELECT s.`id`, g.`id`
FROM `services` AS s
JOIN `user_groups` AS g ON g.`code` = 'student'
WHERE s.`code` IN (
  'student.dashboard',
  'student.request.view',
  'account.home',
  'account.favorites',
  'account.bookings',
  'deposit.pay'
);

INSERT INTO `users`
  (`email`, `password_hash`, `first_name`, `last_name`, `phone`, `status`, `email_verified_at`)
VALUES
  ('admin@uniaffitti.local', '$2y$12$ZdnfvKa2yyD.Fe7ECUeFn.lzKqhoGIOwJr/yd6bsYfn2GfIwNF7fy', 'System', 'Administrator', NULL, 'active', NOW())
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
