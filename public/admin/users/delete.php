<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.users.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url_for('admin/users/index.php'));
}

if (!verify_csrf_token(post_str('csrf_token'), 'user_delete')) {
    set_flash('danger', 'Token CSRF non valido.');
    redirect(url_for('admin/users/index.php'));
}

$id = post_int('id');

if ($id <= 0) {
    set_flash('danger', 'ID utente non valido.');
    redirect(url_for('admin/users/index.php'));
}

$repo = new UserRepository();
$user = $repo->find($id);

if ($user === null) {
    set_flash('danger', 'Utente non trovato.');
    redirect(url_for('admin/users/index.php'));
}

$repo->softDelete($id);

set_flash('success', 'Utente eliminato.');
redirect(url_for('admin/users/index.php'));
