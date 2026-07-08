<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

require_login();
require_service('admin.amenities.index');

$items = amenities_all();
$canManage = has_service('admin.amenities.manage');

$html = '<div class="admin-toolbar"><h1>Accessori</h1>';
if ($canManage) {
    $html .= '<a class="button-primary" href="' . e(url_for('admin/amenities/create.php')) . '">+ Nuovo accessorio</a>';
}
$html .= '</div>';

$html .= '<table class="data-table"><thead><tr><th>Nome</th><th>Codice</th><th>Icona</th><th>Azioni</th></tr></thead><tbody>';
foreach ($items as $item) {
    $actions = '';
    if ($canManage) {
        $actions = '<a class="button-small button-secondary" href="' . e(url_for('admin/amenities/create.php?id=' . $item['id'])) . '">Modifica</a> '
            . '<form method="post" action="' . e(url_for('admin/amenities/delete.php')) . '" class="inline-form" onsubmit="return confirm(\'Eliminare questo accessorio?\')">'
            . csrf_field('delete_amenity') . '<input type="hidden" name="id" value="' . (int) $item['id'] . '">'
            . '<button type="submit" class="button-small button-danger">Elimina</button></form>';
    }
    $html .= '<tr><td>' . e($item['name']) . '</td><td><code>' . e($item['code']) . '</code></td><td>' . e((string) ($item['icon'] ?? '')) . '</td><td>' . $actions . '</td></tr>';
}
if ($items === []) {
    $html .= '<tr><td colspan="4" class="muted">Nessun accessorio presente.</td></tr>';
}
$html .= '</tbody></table>';

render_admin_page('Accessori', $html, 'admin.amenities.index');
