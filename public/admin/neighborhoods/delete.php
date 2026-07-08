<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.neighborhoods.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url_for('admin/neighborhoods/'));
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'delete_neighborhood')) {
    set_flash('danger', 'Token CSRF non valido.');
    redirect(url_for('admin/neighborhoods/'));
}

$id = post_int('id');
if ($id <= 0) {
    redirect(url_for('admin/neighborhoods/'));
}

$repo = new GeoRepository();

if ($repo->neighborhoodInUse($id)) {
    set_flash('warning', 'Impossibile eliminare: il quartiere è associato a degli immobili.');
    redirect(url_for('admin/neighborhoods/'));
}

$row = $repo->findNeighborhood($id);
if (!$row) {
    set_flash('danger', 'Quartiere non trovato.');
    redirect(url_for('admin/neighborhoods/'));
}

$repo->deleteNeighborhood($id);
set_flash('success', 'Quartiere eliminato.');
redirect(url_for('admin/neighborhoods/'));
