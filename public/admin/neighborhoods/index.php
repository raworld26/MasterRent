<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.neighborhoods.index');

$repo  = new GeoRepository();
$items = $repo->allNeighborhoods();

$html  = '<div class="admin-toolbar">';
$html .= '<a class="button" href="' . e(url_for('admin/neighborhoods/create.php')) . '">+ Nuovo quartiere</a>';
$html .= '</div>';

$html .= '<table class="admin-table">';
$html .= '<thead><tr><th>Nome</th><th>Codice</th><th>Descrizione</th><th>Azioni</th></tr></thead>';
$html .= '<tbody>';

foreach ($items as $item) {
    $html .= '<tr>';
    $html .= '<td>' . e($item['name']) . '</td>';
    $html .= '<td><code>' . e($item['code']) . '</code></td>';
    $html .= '<td>' . e($item['description'] ?? '') . '</td>';
    $html .= '<td class="actions">';
    $html .= '<a href="' . e(url_for('admin/neighborhoods/create.php?id=' . $item['id'])) . '">Modifica</a> ';
    $html .= '<form method="post" action="' . e(url_for('admin/neighborhoods/delete.php')) . '" class="inline-form" onsubmit="return confirm(\'Eliminare questo quartiere?\')">';
    $html .= csrf_field('delete_neighborhood');
    $html .= '<input type="hidden" name="id" value="' . e((string) $item['id']) . '">';
    $html .= '<button type="submit" class="link-danger">Elimina</button>';
    $html .= '</form>';
    $html .= '</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';

if (empty($items)) {
    $html .= '<p class="empty-state">Nessun quartiere presente.</p>';
}

render_page_backend('Quartieri', $html, [], 'admin.neighborhoods.index');
