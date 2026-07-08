<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.groups.manage');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(url_for('admin/groups/index.php'));
}

if (!verify_csrf_token(post_str('csrf_token'), 'group_delete')) {
    set_flash('danger', 'Token CSRF non valido.');
    redirect(url_for('admin/groups/index.php'));
}

$id = post_int('id');

if ($id <= 0) {
    set_flash('danger', 'ID gruppo non valido.');
    redirect(url_for('admin/groups/index.php'));
}

$repo  = new GroupRepository();
$group = $repo->find($id);

if ($group === null) {
    set_flash('danger', 'Gruppo non trovato.');
    redirect(url_for('admin/groups/index.php'));
}

if ((int) $group['is_system'] === 1) {
    set_flash('danger', 'Impossibile eliminare un gruppo di sistema.');
    redirect(url_for('admin/groups/index.php'));
}

$repo->delete($id);

set_flash('success', 'Gruppo eliminato.');
redirect(url_for('admin/groups/index.php'));
