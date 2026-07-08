<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.services.manage');

$svcRepo   = new ServiceRepository();
$groupRepo = new GroupRepository();

$id       = (int) query_str('id');
$editing  = $id > 0;
$service  = null;
$svcGroupIds = [];

if ($editing) {
    $service = $svcRepo->find($id);
    if ($service === null) {
        set_flash('danger', 'Servizio non trovato.');
        redirect(url_for('admin/services/index.php'));
    }
    $svcGroupIds = $svcRepo->groupIds($id);
}

$allGroups = $groupRepo->all();
$errors    = [];

/* ── POST: salvataggio ──────────────────────────────────────────── */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token(post_str('csrf_token'), 'service_form')) {
        $errors[] = 'Token CSRF non valido.';
    }

    $code        = post_str('code');
    $name        = post_str('name');
    $description = post_str('description');
    $area        = post_str('area');
    $path        = post_str('path');
    $httpMethod  = post_str('http_method');
    $isMenuItem  = isset($_POST['is_menu_item']) ? 1 : 0;
    $menuOrder   = post_int('menu_order');
    $isActive    = isset($_POST['is_active']) ? 1 : 0;
    $groupIds    = array_map('intval', $_POST['groups'] ?? []);

    if ($code === '') $errors[] = 'Il codice è obbligatorio.';
    if ($name === '') $errors[] = 'Il nome è obbligatorio.';
    if (!in_array($area, ['frontend', 'backend'], true)) {
        $errors[] = 'Area non valida.';
    }
    if (!in_array($httpMethod, ['GET', 'POST', 'ALL'], true)) {
        $errors[] = 'Metodo HTTP non valido.';
    }

    // Unicità codice
    if ($code !== '' && $svcRepo->codeExists($code, $editing ? $id : 0)) {
        $errors[] = 'Questo codice è già in uso.';
    }

    if ($errors === []) {
        $data = [
            'code'         => $code,
            'name'         => $name,
            'description'  => $description !== '' ? $description : null,
            'area'         => $area,
            'path'         => $path,
            'http_method'  => $httpMethod,
            'is_menu_item' => $isMenuItem,
            'menu_order'   => $menuOrder,
            'is_active'    => $isActive,
        ];

        if ($editing) {
            $svcRepo->update($id, $data);
            $svcRepo->setGroups($id, $groupIds);
            set_flash('success', 'Servizio aggiornato.');
        } else {
            $newId = $svcRepo->create($data);
            $svcRepo->setGroups($newId, $groupIds);
            set_flash('success', 'Servizio creato.');
        }

        redirect(url_for('admin/services/index.php'));
    }

    // Ripopola
    $service = $service ?? [];
    $service['code']         = $code;
    $service['name']         = $name;
    $service['description']  = $description;
    $service['area']         = $area;
    $service['path']         = $path;
    $service['http_method']  = $httpMethod;
    $service['is_menu_item'] = $isMenuItem;
    $service['menu_order']   = $menuOrder;
    $service['is_active']    = $isActive;
    $svcGroupIds             = $groupIds;
}

/* ── Valori per il form ─────────────────────────────────────────── */

$v = [
    'code'         => $service['code'] ?? '',
    'name'         => $service['name'] ?? '',
    'description'  => $service['description'] ?? '',
    'area'         => $service['area'] ?? 'backend',
    'path'         => $service['path'] ?? '',
    'http_method'  => $service['http_method'] ?? 'GET',
    'is_menu_item' => (int) ($service['is_menu_item'] ?? 0),
    'menu_order'   => (int) ($service['menu_order'] ?? 0),
    'is_active'    => (int) ($service['is_active'] ?? 1),
];

/* ── HTML ────────────────────────────────────────────────────────── */

$title = $editing ? 'Modifica servizio' : 'Nuovo servizio';

$html = '';
if ($errors !== []) {
    $html .= '<div class="alert alert-danger"><ul>';
    foreach ($errors as $err) {
        $html .= '<li>' . e($err) . '</li>';
    }
    $html .= '</ul></div>';
}

$html .= '<form class="admin-form" method="post">';
$html .= csrf_field('service_form');

$html .= '<div class="form-group">';
$html .= '<label for="code">Codice</label>';
$html .= '<input type="text" id="code" name="code" value="' . e($v['code']) . '" required>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="name">Nome</label>';
$html .= '<input type="text" id="name" name="name" value="' . e($v['name']) . '" required>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="description">Descrizione</label>';
$html .= '<textarea id="description" name="description" rows="3">' . e($v['description']) . '</textarea>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="area">Area</label>';
$html .= '<select id="area" name="area">';
$html .= select_options([
    ['id' => 'frontend', 'name' => 'Frontend'],
    ['id' => 'backend',  'name' => 'Backend'],
], $v['area']);
$html .= '</select>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="path">Percorso</label>';
$html .= '<input type="text" id="path" name="path" value="' . e($v['path']) . '">';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="http_method">Metodo HTTP</label>';
$html .= '<select id="http_method" name="http_method">';
$html .= select_options([
    ['id' => 'GET',  'name' => 'GET'],
    ['id' => 'POST', 'name' => 'POST'],
    ['id' => 'ALL',  'name' => 'ALL'],
], $v['http_method']);
$html .= '</select>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label class="checkbox-label">';
$html .= '<input type="checkbox" name="is_menu_item" value="1"' . ($v['is_menu_item'] ? ' checked' : '') . '> ';
$html .= 'Voce di menu</label>';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label for="menu_order">Ordine menu</label>';
$html .= '<input type="number" id="menu_order" name="menu_order" value="' . e((string) $v['menu_order']) . '">';
$html .= '</div>';

$html .= '<div class="form-group">';
$html .= '<label class="checkbox-label">';
$html .= '<input type="checkbox" name="is_active" value="1"' . ($v['is_active'] ? ' checked' : '') . '> ';
$html .= 'Attivo</label>';
$html .= '</div>';

$html .= '<fieldset class="form-group"><legend>Gruppi assegnati</legend>';
foreach ($allGroups as $g) {
    $checked = in_array((int) $g['id'], $svcGroupIds, true) ? ' checked' : '';
    $html .= '<label class="checkbox-label">'
        . '<input type="checkbox" name="groups[]" value="' . e((string) $g['id']) . '"' . $checked . '> '
        . e($g['name'])
        . '</label> ';
}
$html .= '</fieldset>';

$html .= '<div class="form-actions">';
$html .= '<button class="button" type="submit">Salva</button> ';
$html .= '<a href="' . e(url_for('admin/services/index.php')) . '">Annulla</a>';
$html .= '</div>';

$html .= '</form>';

render_page_backend($title, $html, [], 'admin.services.index');
