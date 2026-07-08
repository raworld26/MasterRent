<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.services.index');

$repo     = new ServiceRepository();
$services = $repo->all();

/* ── Lista servizi ───────────────────────────────────────────────── */

$html = '<div class="admin-toolbar">';
if (has_service('admin.services.manage')) {
    $html .= '<a class="button" href="' . e(url_for('admin/services/create.php')) . '">+ Nuovo servizio</a>';
}
$html .= '</div>';

$html .= '<table class="admin-table">';
$html .= '<thead><tr>'
    . '<th>Nome</th><th>Codice</th><th>Area</th><th>Menu</th><th>Gruppi</th><th>Azioni</th>'
    . '</tr></thead><tbody>';

foreach ($services as $s) {
    $areaLabel = $s['area'] === 'backend' ? 'Backend' : 'Frontend';
    $menuLabel = (int) $s['is_menu_item'] === 1
        ? '<span class="badge badge-info">Sì</span>'
        : 'No';
    $activeLabel = (int) $s['is_active'] === 1
        ? ''
        : ' <span class="badge badge-muted">Disattivo</span>';

    $html .= '<tr>';
    $html .= '<td>' . e($s['name']) . $activeLabel . '</td>';
    $html .= '<td><code>' . e($s['code']) . '</code></td>';
    $html .= '<td>' . e($areaLabel) . '</td>';
    $html .= '<td>' . $menuLabel . '</td>';
    $html .= '<td>' . e((string) $s['group_count']) . '</td>';
    $html .= '<td class="actions">';

    if (has_service('admin.services.manage')) {
        $html .= '<a class="btn-sm" href="' . e(url_for('admin/services/create.php?id=' . $s['id'])) . '">Modifica</a> ';
        $html .= '<form method="post" action="' . e(url_for('admin/services/delete.php')) . '" style="display:inline" '
            . 'onsubmit="return confirm(\'Eliminare questo servizio?\')">'
            . csrf_field('service_delete')
            . '<input type="hidden" name="id" value="' . e((string) $s['id']) . '">'
            . '<button type="submit" class="btn-sm btn-danger">Elimina</button></form>';
    }

    $html .= '</td></tr>';
}

if ($services === []) {
    $html .= '<tr><td colspan="6" class="muted">Nessun servizio trovato.</td></tr>';
}

$html .= '</tbody></table>';

render_page_backend('Servizi', $html, [], 'admin.services.index');
