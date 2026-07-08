<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.services.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url_for('admin/services/index.php'));
}

if (!verify_csrf_token(post_str('csrf_token'), 'service_delete')) {
    set_flash('danger', 'Token CSRF non valido.');
    redirect(url_for('admin/services/index.php'));
}

$id = post_int('id');

if ($id <= 0) {
    set_flash('danger', 'ID servizio non valido.');
    redirect(url_for('admin/services/index.php'));
}

$repo    = new ServiceRepository();
$service = $repo->find($id);

if ($service === null) {
    set_flash('danger', 'Servizio non trovato.');
    redirect(url_for('admin/services/index.php'));
}

$repo->delete($id);

set_flash('success', 'Servizio eliminato.');
redirect(url_for('admin/services/index.php'));
