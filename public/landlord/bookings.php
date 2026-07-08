<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
require_service('landlord.bookings');

$user = current_user();
$rows = bookings_by_landlord((int) $user['id']);

if ($rows === []) {
    $body = '<div class="empty-state"><p class="muted">Nessuna richiesta ricevuta. Quando uno studente prenota una visita per una delle tue stanze, la trovi qui.</p></div>';
} else {
    $body = '<ul class="item-list">';
    foreach ($rows as $b) {
        $body .= '<li>'
            . '<span class="item-title"><a href="' . e(url_for('booking.php?id=' . $b['id'])) . '">' . e($b['room_name']) . '</a></span>'
            . '<span class="item-meta">' . e($b['property_title'] . ' · ' . ($b['student_name'] ?? '')) . ' · ' . e(date('d/m/Y', strtotime((string) $b['created_at']))) . '</span>'
            . '<p>' . booking_status_badge((string) $b['status']) . '</p>'
            . '</li>';
    }
    $body .= '</ul>';
}

$content = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">Area Proprietario</p><h1>Richieste ricevute</h1>'
    . '<p class="muted">Le richieste degli studenti: visite, caparre e disdette. Apri una richiesta per leggere i messaggi o gestire il rapporto.</p></div>'
    . '<a class="button-secondary" href="' . e(url_for('landlord/index.php')) . '">Area riservata</a></header>'
    . '<section class="panel">' . $body . '</section>'
    . '</section>';

render_page('Richieste ricevute', $content, ['body_class' => 'page-dashboard']);
