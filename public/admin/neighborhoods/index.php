<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../includes/bootstrap.php';

require_login();
require_service('admin.neighborhoods.index');

$items = neighborhoods_all();
$canManage = has_service('admin.neighborhoods.manage');

$html = '<div class="admin-toolbar"><h1>Quartieri</h1>';
if ($canManage) {
    $html .= '<a class="button-primary" href="' . e(url_for('admin/neighborhoods/create.php')) . '">+ Nuovo quartiere</a>';
}
$html .= '</div>';

$html .= '<table class="data-table"><thead><tr><th>Nome</th><th>Codice</th><th>Descrizione</th><th>Azioni</th></tr></thead><tbody>';
foreach ($items as $item) {
    $actions = '';
    if ($canManage) {
        $actions = '<a class="button-small button-secondary" href="' . e(url_for('admin/neighborhoods/create.php?id=' . $item['id'])) . '">Modifica</a> '
            . '<form method="post" action="' . e(url_for('admin/neighborhoods/delete.php')) . '" class="inline-form" onsubmit="return confirm(\'Eliminare questo quartiere?\')">'
            . csrf_field('delete_neighborhood') . '<input type="hidden" name="id" value="' . (int) $item['id'] . '">'
            . '<button type="submit" class="button-small button-danger">Elimina</button></form>';
    }
    $html .= '<tr><td>' . e($item['name']) . '</td><td><code>' . e($item['code']) . '</code></td><td>' . e(excerpt((string) ($item['description'] ?? ''), 80)) . '</td><td>' . $actions . '</td></tr>';
}
if ($items === []) {
    $html .= '<tr><td colspan="4" class="muted">Nessun quartiere presente.</td></tr>';
}
$html .= '</tbody></table>';

render_admin_page('Quartieri', $html, 'admin.neighborhoods.index');
