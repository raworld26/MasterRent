<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.neighborhoods.manage');

$repo = new GeoRepository();
$id   = (int) query_str('id');
$edit = $id > 0;

$data = ['code' => '', 'name' => '', 'description' => ''];

if ($edit) {
    $row = $repo->findNeighborhood($id);
    if (!$row) {
        set_flash('danger', 'Quartiere non trovato.');
        redirect(url_for('admin/neighborhoods/'));
    }
    $data = $row;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null, 'neighborhood_form')) {
        $errors[] = 'Token CSRF non valido.';
    }

    $data['code']        = post_str('code');
    $data['name']        = post_str('name');
    $data['description'] = post_str('description');

    if ($data['code'] === '') {
        $errors[] = 'Il codice è obbligatorio.';
    }
    if ($data['name'] === '') {
        $errors[] = 'Il nome è obbligatorio.';
    }
    if ($data['code'] !== '' && $repo->neighborhoodCodeExists($data['code'], $edit ? $id : 0)) {
        $errors[] = 'Il codice è già in uso.';
    }

    if (empty($errors)) {
        $desc = $data['description'] !== '' ? $data['description'] : null;
        if ($edit) {
            $repo->updateNeighborhood($id, $data['code'], $data['name'], $desc);
            set_flash('success', 'Quartiere aggiornato.');
        } else {
            $repo->createNeighborhood($data['code'], $data['name'], $desc);
            set_flash('success', 'Quartiere creato.');
        }
        redirect(url_for('admin/neighborhoods/'));
    }
}

$title      = $edit ? 'Modifica quartiere' : 'Nuovo quartiere';
$submitLabel = $edit ? 'Salva modifiche' : 'Crea quartiere';
$actionUrl  = $edit
    ? url_for('admin/neighborhoods/create.php?id=' . $id)
    : url_for('admin/neighborhoods/create.php');

$html = '';

if (!empty($errors)) {
    $html .= '<div class="alert alert-danger"><ul>';
    foreach ($errors as $err) {
        $html .= '<li>' . e($err) . '</li>';
    }
    $html .= '</ul></div>';
}

$html .= '<form class="admin-form" method="post" action="' . e($actionUrl) . '">';
$html .= csrf_field('neighborhood_form');

$html .= '<label>Codice<input type="text" name="code" value="' . e($data['code']) . '" required></label>';
$html .= '<label>Nome<input type="text" name="name" value="' . e($data['name']) . '" required></label>';
$html .= '<label>Descrizione<textarea name="description" rows="3">' . e($data['description'] ?? '') . '</textarea></label>';

$html .= '<div class="form-actions">';
$html .= '<button class="button" type="submit">' . e($submitLabel) . '</button> ';
$html .= '<a href="' . e(url_for('admin/neighborhoods/')) . '">Annulla</a>';
$html .= '</div>';
$html .= '</form>';

render_page_backend($title, $html, [], 'admin.neighborhoods.manage');
