<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();
require_service('account.bookings');

$user = current_user();
$rows = (new BookingRepository())->byStudent((int) $user['id']);

$listHtml = $rows === []
    ? render_empty_state(
        'Nessuna richiesta inviata',
        'Quando prenoti una visita, qui trovi lo stato della richiesta, la chat con il proprietario e la caparra.',
        url_for('search.php'),
        'Cerca stanze',
        'inbox'
    )
    : render_list('frontend/request_rows', array_map(static fn ($b) => [
        'req_url' => e(url_for('booking.php?id=' . $b['id'])),
        'req_room' => e($b['room_name']),
        'req_sub' => e($b['property_title'] . ' · ' . $b['neighborhood_name']),
        'req_date' => e(date('d/m/Y', strtotime((string) $b['created_at']))),
        'req_status' => booking_status_badge((string) $b['status']),
    ], $rows));

$content = render_template('frontend/simple_page', [
    'page_title' => 'Le mie richieste',
    'page_intro' => 'Le richieste di visita inviate ai proprietari. Quando una richiesta è approvata puoi versare la caparra e prenotare la stanza.',
    'page_action_url' => e(url_for('search.php')),
    'page_action_label' => 'Cerca stanze',
    'page_body' => $listHtml,
]);

render_page_frontend('Le mie richieste', $content, ['body_class' => 'page-dashboard']);
