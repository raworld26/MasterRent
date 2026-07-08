<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.properties.index');

/* ── Eliminazione (POST) ─────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post_str('action') === 'delete') {
    require_service('admin.properties.manage');
    verify_csrf_token(post_str('csrf_token')) || redirect(url_for('admin/properties/index.php'));

    $id = post_int('id');
    if ($id > 0) {
        (new PropertyRepository())->delete($id);
        set_flash('success', 'Annuncio eliminato.');
    }
    redirect(url_for('admin/properties/index.php'));
}

/* ── Elenco ───────────────────────────────────────────────────────────── */
$properties = (new PropertyRepository())->forAdmin();

$html = '<div class="admin-toolbar">';
$html .= '<h2>Annunci (' . count($properties) . ')</h2>';
if (has_service('admin.properties.manage')) {
    $html .= '<a class="button" href="' . e(url_for('admin/properties/form.php')) . '">+ Nuovo annuncio</a>';
}
$html .= '</div>';

$html .= '<table class="admin-table">';
$html .= '<thead><tr>';
$html .= '<th>ID</th><th>Titolo</th><th>Quartiere</th><th>Proprietario</th><th>Stanze</th><th>Azioni</th>';
$html .= '</tr></thead><tbody>';

foreach ($properties as $p) {
    $html .= '<tr>';
    $html .= '<td>' . e($p['id']) . '</td>';
    $html .= '<td>' . e($p['title']) . '</td>';
    $html .= '<td>' . e($p['neighborhood_name']) . '</td>';
    $html .= '<td>' . e($p['landlord_name']) . '</td>';
    $html .= '<td>' . e($p['room_count']) . '</td>';
    $html .= '<td>';
    $html .= '<a href="' . e(url_for('admin/properties/view.php?id=' . $p['id'])) . '" class="btn btn-sm btn-info">Dettaglio</a> ';
    if (has_service('admin.properties.manage')) {
        $html .= '<a href="' . e(url_for('admin/properties/form.php?id=' . $p['id'])) . '" class="btn btn-sm btn-secondary">Modifica</a> ';
    }
    $html .= '<form method="post" style="display:inline" onsubmit="return confirm(\'Eliminare?\')">';
    $html .= csrf_field();
    $html .= '<input type="hidden" name="action" value="delete">';
    $html .= '<input type="hidden" name="id" value="' . e($p['id']) . '">';
    $html .= '<button type="submit" class="btn btn-sm btn-danger">Elimina</button>';
    $html .= '</form>';
    $html .= '</td>';
    $html .= '</tr>';
}

if ($properties === []) {
    $html .= '<tr><td colspan="6" class="text-center">Nessun annuncio presente.</td></tr>';
}

$html .= '</tbody></table>';

render_page_backend('Annunci', $html, [], 'admin.properties.index');
