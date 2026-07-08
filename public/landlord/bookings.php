<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();
require_service('landlord.bookings');

$user = current_user();
$uid = (int) $user['id'];
$bookings = (new BookingRepository())->byLandlord($uid);

$rows = [];
foreach ($bookings as $b) {
    $rows[] = [
        'req_url'    => e(url_for('booking.php?id=' . $b['id'])),
        'req_room'   => e($b['room_name']),
        'req_sub'    => e($b['property_title'] . ' · ' . ($b['student_name'] ?? '')),
        'req_date'   => e(date('d/m/Y', strtotime((string) $b['created_at']))),
        'req_status' => booking_status_badge((string) $b['status']),
        'req_cta'    => '',
    ];
}

$body = $rows === []
    ? render_empty_state(
        'Nessuna richiesta ricevuta',
        'Quando uno studente prenota una visita per una delle tue stanze, la trovi qui.',
        '',
        '',
        'inbox'
    )
    : render_list('frontend/request_rows', $rows);

$content = render_template('frontend/simple_page', [
    'page_title'  => 'Richieste ricevute',
    'page_intro'  => 'Le richieste degli studenti: visite, caparre e disdette. Apri una richiesta per leggere i messaggi o gestire il rapporto.',
    'page_action_url'   => '',
    'page_action_label' => '',
    'page_body'   => $body,
]);

render_page_frontend('Richieste ricevute', $content, ['body_class' => 'page-dashboard']);
