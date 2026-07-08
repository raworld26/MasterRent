<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.amenities.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url_for('admin/amenities/'));
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'delete_amenity')) {
    set_flash('danger', 'Token CSRF non valido.');
    redirect(url_for('admin/amenities/'));
}

$id = post_int('id');
if ($id <= 0) {
    redirect(url_for('admin/amenities/'));
}

$repo = new AmenityRepository();

$row = $repo->find($id);
if (!$row) {
    set_flash('danger', 'Accessorio non trovato.');
    redirect(url_for('admin/amenities/'));
}

$repo->delete($id);
set_flash('success', 'Accessorio eliminato.');
redirect(url_for('admin/amenities/'));
