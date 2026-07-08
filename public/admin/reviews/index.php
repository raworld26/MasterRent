<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.reviews.index');

$reviewRepo = new ReviewRepository();

/* ── Azioni POST ──────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_service('admin.reviews.manage');
    verify_csrf_token(post_str('csrf_token')) || redirect(url_for('admin/reviews/index.php'));

    $action = post_str('action');
    $id     = post_int('id');

    if ($id > 0) {
        if ($action === 'publish') {
            $reviewRepo->setStatus($id, 'published');
            set_flash('success', 'Recensione pubblicata.');
        } elseif ($action === 'hide') {
            $reviewRepo->setStatus($id, 'hidden');
            set_flash('success', 'Recensione nascosta.');
        } elseif ($action === 'delete') {
            $reviewRepo->delete($id);
            set_flash('success', 'Recensione eliminata.');
        }
    }
    redirect(url_for('admin/reviews/index.php'));
}

/* ── Elenco ───────────────────────────────────────────────────────────── */
$reviews = $reviewRepo->allForAdmin();

$html = '<div class="admin-toolbar">';
$html .= '<h2>Recensioni (' . count($reviews) . ')</h2>';
$html .= '</div>';

$html .= '<table class="admin-table">';
$html .= '<thead><tr>';
$html .= '<th>ID</th><th>Stanza</th><th>Autore</th><th>Valutazione</th><th>Titolo</th><th>Stato</th><th>Data</th><th>Azioni</th>';
$html .= '</tr></thead><tbody>';

foreach ($reviews as $rv) {
    $statusLabel = $rv['status'] === 'published' ? '<span class="badge badge-success">Pubblicata</span>'
                                                  : '<span class="badge badge-muted">Nascosta</span>';

    $html .= '<tr>';
    $html .= '<td>' . e($rv['id']) . '</td>';
    $html .= '<td>' . e($rv['room_name']) . '</td>';
    $html .= '<td>' . e($rv['author']) . '</td>';
    $html .= '<td>' . stars_html((float) $rv['rating']) . '</td>';
    $html .= '<td>' . e($rv['title']) . '</td>';
    $html .= '<td>' . $statusLabel . '</td>';
    $html .= '<td>' . e(date('d/m/Y H:i', strtotime($rv['created_at']))) . '</td>';
    $html .= '<td>';

    /* Pubblica / Nascondi toggle */
    if ($rv['status'] !== 'published') {
        $html .= '<form method="post" style="display:inline">';
        $html .= csrf_field();
        $html .= '<input type="hidden" name="action" value="publish">';
        $html .= '<input type="hidden" name="id" value="' . e($rv['id']) . '">';
        $html .= '<button type="submit" class="btn btn-sm btn-success">Pubblica</button>';
        $html .= '</form> ';
    }
    if ($rv['status'] !== 'hidden') {
        $html .= '<form method="post" style="display:inline">';
        $html .= csrf_field();
        $html .= '<input type="hidden" name="action" value="hide">';
        $html .= '<input type="hidden" name="id" value="' . e($rv['id']) . '">';
        $html .= '<button type="submit" class="btn btn-sm btn-warning">Nascondi</button>';
        $html .= '</form> ';
    }

    /* Elimina */
    $html .= '<form method="post" style="display:inline" onsubmit="return confirm(\'Eliminare?\')">';
    $html .= csrf_field();
    $html .= '<input type="hidden" name="action" value="delete">';
    $html .= '<input type="hidden" name="id" value="' . e($rv['id']) . '">';
    $html .= '<button type="submit" class="btn btn-sm btn-danger">Elimina</button>';
    $html .= '</form>';

    $html .= '</td>';
    $html .= '</tr>';
}

if ($reviews === []) {
    $html .= '<tr><td colspan="8" class="text-center">Nessuna recensione presente.</td></tr>';
}

$html .= '</tbody></table>';

render_page_backend('Recensioni', $html, [], 'admin.reviews.index');
