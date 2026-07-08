<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
require_service('account.bookings');

$user = current_user();
$rows = bookings_by_student((int) $user['id']);

if ($rows === []) {
    $body = '<div class="empty-state"><p class="muted">Nessuna richiesta inviata. Quando prenoti una visita, qui trovi lo stato della richiesta, la chat con il proprietario e la caparra.</p>'
        . '<a class="button-primary" href="' . e(url_for('search.php')) . '">Cerca stanze</a></div>';
} else {
    $body = '<ul class="item-list">';
    foreach ($rows as $b) {
        $body .= '<li>'
            . '<span class="item-title"><a href="' . e(url_for('booking.php?id=' . $b['id'])) . '">' . e($b['room_name']) . '</a></span>'
            . '<span class="item-meta">' . e($b['property_title'] . ' · ' . $b['neighborhood_name']) . ' · ' . e(date('d/m/Y', strtotime((string) $b['created_at']))) . '</span>'
            . '<p>' . booking_status_badge((string) $b['status']) . '</p>'
            . '</li>';
    }
    $body .= '</ul>';
}

$content = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">Area Studente</p><h1>Le mie richieste</h1>'
    . '<p class="muted">Le richieste di visita inviate ai proprietari. Quando una richiesta è approvata puoi versare la caparra e prenotare la stanza.</p></div>'
    . '<a class="button-secondary" href="' . e(url_for('account/index.php')) . '">Area riservata</a></header>'
    . '<section class="panel">' . $body . '</section>'
    . '</section>';

render_page('Le mie richieste', $content, ['body_class' => 'page-dashboard']);
