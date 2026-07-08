<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.users.index');

$repo = new UserRepository();
$users = $repo->all();

/* ── Lista utenti ────────────────────────────────────────────────── */

$html = '<div class="admin-toolbar">';
if (has_service('admin.users.manage')) {
    $html .= '<a class="button" href="' . e(url_for('admin/users/create.php')) . '">+ Nuovo utente</a>';
}
$html .= '</div>';

$html .= '<table class="admin-table">';
$html .= '<thead><tr>'
    . '<th>Nome</th><th>Email</th><th>Gruppi</th><th>Stato</th><th>Registrato</th><th>Azioni</th>'
    . '</tr></thead><tbody>';

foreach ($users as $u) {
    $statusClass = match ($u['status']) {
        'active'    => 'badge-success',
        'suspended' => 'badge-warning',
        default     => 'badge-muted',
    };

    $html .= '<tr>';
    $html .= '<td>' . e($u['first_name'] . ' ' . $u['last_name']) . '</td>';
    $html .= '<td>' . e($u['email']) . '</td>';
    $html .= '<td>' . e($u['groups'] ?? '—') . '</td>';
    $html .= '<td><span class="badge ' . $statusClass . '">' . e(ucfirst($u['status'])) . '</span></td>';
    $html .= '<td>' . e(date('d/m/Y H:i', strtotime($u['created_at']))) . '</td>';
    $html .= '<td class="actions">';

    if (has_service('admin.users.manage')) {
        $html .= '<a class="btn-sm" href="' . e(url_for('admin/users/create.php?id=' . $u['id'])) . '">Modifica</a> ';
        $html .= '<form method="post" action="' . e(url_for('admin/users/delete.php')) . '" style="display:inline" '
            . 'onsubmit="return confirm(\'Eliminare questo utente?\')">'
            . csrf_field('user_delete')
            . '<input type="hidden" name="id" value="' . e((string) $u['id']) . '">'
            . '<button type="submit" class="btn-sm btn-danger">Elimina</button></form>';
    }

    $html .= '</td></tr>';
}

if ($users === []) {
    $html .= '<tr><td colspan="6" class="muted">Nessun utente trovato.</td></tr>';
}

$html .= '</tbody></table>';

render_page_backend('Utenti', $html, [], 'admin.users.index');
