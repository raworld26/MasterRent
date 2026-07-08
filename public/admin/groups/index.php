<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.groups.index');

$repo   = new GroupRepository();
$groups = $repo->all();

/* ── Lista gruppi ────────────────────────────────────────────────── */

$html = '<div class="admin-toolbar">';
if (has_service('admin.groups.manage')) {
    $html .= '<a class="button" href="' . e(url_for('admin/groups/create.php')) . '">+ Nuovo gruppo</a>';
}
$html .= '</div>';

$html .= '<table class="admin-table">';
$html .= '<thead><tr>'
    . '<th>Nome</th><th>Codice</th><th>Membri</th><th>Sistema</th><th>Azioni</th>'
    . '</tr></thead><tbody>';

foreach ($groups as $g) {
    $isSystem = (int) $g['is_system'] === 1;

    $html .= '<tr>';
    $html .= '<td>' . e($g['name']) . '</td>';
    $html .= '<td><code>' . e($g['code']) . '</code></td>';
    $html .= '<td>' . e((string) $g['member_count']) . '</td>';
    $html .= '<td>' . ($isSystem ? '<span class="badge badge-info">Sì</span>' : 'No') . '</td>';
    $html .= '<td class="actions">';

    if (has_service('admin.groups.manage')) {
        $html .= '<a class="btn-sm" href="' . e(url_for('admin/groups/create.php?id=' . $g['id'])) . '">Modifica</a> ';

        if (!$isSystem) {
            $html .= '<form method="post" action="' . e(url_for('admin/groups/delete.php')) . '" style="display:inline" '
                . 'onsubmit="return confirm(\'Eliminare questo gruppo?\')">'
                . csrf_field('group_delete')
                . '<input type="hidden" name="id" value="' . e((string) $g['id']) . '">'
                . '<button type="submit" class="btn-sm btn-danger">Elimina</button></form>';
        }
    }

    $html .= '</td></tr>';
}

if ($groups === []) {
    $html .= '<tr><td colspan="5" class="muted">Nessun gruppo trovato.</td></tr>';
}

$html .= '</tbody></table>';

render_page_backend('Gruppi', $html, [], 'admin.groups.index');
