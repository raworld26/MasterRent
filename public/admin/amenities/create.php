<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

require_login();
require_service('admin.amenities.manage');

$id = (int) query_str('id');
$edit = $id > 0;
$data = ['code' => '', 'name' => '', 'icon' => ''];

if ($edit) {
    $row = amenity_find($id);
    if ($row === null) {
        set_flash('danger', 'Accessorio non trovato.');
        redirect(url_for('admin/amenities/index.php'));
    }
    $data = $row;
}

$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'amenity_form')) {
        $errors[] = 'Token CSRF non valido.';
    }
    $data['code'] = post_str('code');
    $data['name'] = post_str('name');
    $data['icon'] = post_str('icon');

    if ($data['code'] === '') {
        $errors[] = 'Il codice è obbligatorio.';
    }
    if ($data['name'] === '') {
        $errors[] = 'Il nome è obbligatorio.';
    }
    if ($data['code'] !== '' && amenity_code_exists($data['code'], $edit ? $id : 0)) {
        $errors[] = 'Il codice è già in uso.';
    }

    if ($errors === []) {
        $icon = $data['icon'] !== '' ? $data['icon'] : null;
        if ($edit) {
            amenity_update($id, $data['code'], $data['name'], $icon);
            set_flash('success', 'Accessorio aggiornato.');
        } else {
            amenity_create($data['code'], $data['name'], $icon);
            set_flash('success', 'Accessorio creato.');
        }
        redirect(url_for('admin/amenities/index.php'));
    }
}

$actionUrl = $edit ? url_for('admin/amenities/create.php?id=' . $id) : url_for('admin/amenities/create.php');

$html = '<div class="admin-toolbar"><h1>' . ($edit ? 'Modifica accessorio' : 'Nuovo accessorio') . '</h1></div>';
if ($errors !== []) {
    $html .= '<div class="alert alert-danger"><ul>';
    foreach ($errors as $err) {
        $html .= '<li>' . e($err) . '</li>';
    }
    $html .= '</ul></div>';
}
$html .= '<section class="panel form-panel"><form class="admin-form" method="post" action="' . e($actionUrl) . '">'
    . csrf_field('amenity_form')
    . '<label>Codice<input type="text" name="code" value="' . e((string) $data['code']) . '" required></label>'
    . '<label>Nome<input type="text" name="name" value="' . e((string) $data['name']) . '" required></label>'
    . '<label>Icona (emoji o testo)<input type="text" name="icon" value="' . e((string) ($data['icon'] ?? '')) . '"></label>'
    . '<div class="form-actions"><button class="button-primary" type="submit">' . ($edit ? 'Salva modifiche' : 'Crea accessorio') . '</button> '
    . '<a href="' . e(url_for('admin/amenities/index.php')) . '">Annulla</a></div>'
    . '</form></section>';

render_admin_page($edit ? 'Modifica accessorio' : 'Nuovo accessorio', $html, 'admin.amenities.index');
