<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

require_login();
require_service('admin.amenities.manage');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirect(url_for('admin/amenities/index.php'));
}
if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'delete_amenity')) {
    set_flash('danger', 'Token CSRF non valido.');
    redirect(url_for('admin/amenities/index.php'));
}

$id = post_int('id');
if ($id > 0 && amenity_find($id) !== null) {
    amenity_delete($id);
    set_flash('success', 'Accessorio eliminato.');
} else {
    set_flash('danger', 'Accessorio non trovato.');
}
redirect(url_for('admin/amenities/index.php'));
