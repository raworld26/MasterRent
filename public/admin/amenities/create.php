<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.amenities.manage');

$repo = new AmenityRepository();
$id   = (int) query_str('id');
$edit = $id > 0;

$data = ['code' => '', 'name' => '', 'icon' => ''];

if ($edit) {
    $row = $repo->find($id);
    if (!$row) {
        set_flash('danger', 'Accessorio non trovato.');
        redirect(url_for('admin/amenities/'));
    }
    $data = $row;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'amenity_form')) {
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
    if ($data['code'] !== '' && $repo->codeExists($data['code'], $edit ? $id : 0)) {
        $errors[] = 'Il codice è già in uso.';
    }

    if (empty($errors)) {
        $icon = $data['icon'] !== '' ? $data['icon'] : null;
        if ($edit) {
            $repo->update($id, $data['code'], $data['name'], $icon);
            set_flash('success', 'Accessorio aggiornato.');
        } else {
            $repo->create($data['code'], $data['name'], $icon);
            set_flash('success', 'Accessorio creato.');
        }
        redirect(url_for('admin/amenities/'));
    }
}

$title      = $edit ? 'Modifica accessorio' : 'Nuovo accessorio';
$submitLabel = $edit ? 'Salva modifiche' : 'Crea accessorio';
$actionUrl  = $edit
    ? url_for('admin/amenities/create.php?id=' . $id)
    : url_for('admin/amenities/create.php');

$html = '';

if (!empty($errors)) {
    $html .= '<div class="alert alert-danger"><ul>';
    foreach ($errors as $err) {
        $html .= '<li>' . e($err) . '</li>';
    }
    $html .= '</ul></div>';
}

$html .= '<form class="admin-form" method="post" action="' . e($actionUrl) . '">';
$html .= csrf_field('amenity_form');

$html .= '<label>Codice<input type="text" name="code" value="' . e($data['code']) . '" required></label>';
$html .= '<label>Nome<input type="text" name="name" value="' . e($data['name']) . '" required></label>';
$html .= '<label>Icona (emoji o testo)<input type="text" name="icon" value="' . e($data['icon'] ?? '') . '"></label>';

$html .= '<div class="form-actions">';
$html .= '<button class="button" type="submit">' . e($submitLabel) . '</button> ';
$html .= '<a href="' . e(url_for('admin/amenities/')) . '">Annulla</a>';
$html .= '</div>';
$html .= '</form>';

render_page_backend($title, $html, [], 'admin.amenities.manage');
