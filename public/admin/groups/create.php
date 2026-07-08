<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.groups.manage');

$repo     = new GroupRepository();
$id       = (int) query_str('id');
$editing  = $id > 0;
$group    = null;
$isSystem = false;

if ($editing) {
    $group = $repo->find($id);
    if ($group === null) {
        set_flash('danger', 'Gruppo non trovato.');
        redirect(url_for('admin/groups/index.php'));
    }
    $isSystem = (int) $group['is_system'] === 1;
}

$errors = [];

/* ── POST: salvataggio ──────────────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token(post_str('csrf_token'), 'group_form')) {
        $errors[] = 'Token CSRF non valido.';
    }

    $code = post_str('code');
    $name = post_str('name');
    $desc = post_str('description');

    if ($code === '') $errors[] = 'Il codice è obbligatorio.';
    if ($name === '') $errors[] = 'Il nome è obbligatorio.';

    // Unicità codice
    if ($code !== '' && $repo->codeExists($code, $editing ? $id : 0)) {
        $errors[] = 'Questo codice è già in uso.';
    }

    // Per i gruppi di sistema il codice non si modifica
    if ($editing && $isSystem) {
        $code = (string) $group['code'];
    }

    if ($errors === []) {
        $descVal = $desc !== '' ? $desc : null;

        if ($editing) {
            $repo->update($id, $code, $name, $descVal);
            set_flash('success', 'Gruppo aggiornato.');
        } else {
            $repo->create($code, $name, $descVal);
            set_flash('success', 'Gruppo creato.');
        }

        redirect(url_for('admin/groups/index.php'));
    }

    // Ripopola
    $group = $group ?? [];
    $group['code']        = $code;
    $group['name']        = $name;
    $group['description'] = $desc;
}

/* ── Valori per il form ─────────────────────────────────────────── */

$v = [
    'code'        => $group['code'] ?? '',
    'name'        => $group['name'] ?? '',
    'description' => $group['description'] ?? '',
];

/* ── HTML ────────────────────────────────────────────────────────── */

$title = $editing ? 'Modifica gruppo' : 'Nuovo gruppo';

$html = '';
if ($errors !== []) {
    $html .= '<div class="alert alert-danger"><ul>';
    foreach ($errors as $err) {
        $html .= '<li>' . e($err) . '</li>';
    }
    $html .= '</ul></div>';
}

$html .= '<form class="admin-form" method="post">';
$html .= csrf_field('group_form');

$html .= '<div class="form-group">';
$html .= '<label for="code">Codice</label>';
$html .= '<input type="text" id="code" name="code" value="' . e($v['code']) . '"'
    . ($editing && $isSystem ? ' readonly' : '') . ' required>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="name">Nome</label>';
$html .= '<input type="text" id="name" name="name" value="' . e($v['name']) . '" required>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="description">Descrizione</label>';
$html .= '<textarea id="description" name="description" rows="3">' . e($v['description']) . '</textarea>';
$html .= '</div>';

$html .= '<div class="form-actions">';
$html .= '<button class="button" type="submit">Salva</button> ';
$html .= '<a href="' . e(url_for('admin/groups/index.php')) . '">Annulla</a>';
$html .= '</div>';

$html .= '</form>';

render_page_backend($title, $html, [], 'admin.groups.index');
