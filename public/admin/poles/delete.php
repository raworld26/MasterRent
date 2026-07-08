<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.poles.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url_for('admin/poles/'));
}

if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'delete_pole')) {
    set_flash('danger', 'Token CSRF non valido.');
    redirect(url_for('admin/poles/'));
}

$id = post_int('id');
if ($id <= 0) {
    redirect(url_for('admin/poles/'));
}

$repo = new GeoRepository();

if ($repo->poleInUse($id)) {
    set_flash('warning', 'Impossibile eliminare: il polo è associato a degli immobili.');
    redirect(url_for('admin/poles/'));
}

$row = $repo->findPole($id);
if (!$row) {
    set_flash('danger', 'Polo non trovato.');
    redirect(url_for('admin/poles/'));
}

$repo->deletePole($id);
set_flash('success', 'Polo eliminato.');
redirect(url_for('admin/poles/'));
