<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();
require_service('account.my_house');

$user = current_user();
$uid = (int) $user['id'];
$bookings = new BookingRepository();
$properties = new PropertyRepository();
$selfUrl = url_for('account/my-house.php');

$house = $bookings->activeForStudent($uid);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'my_house_cancel')) {
        set_flash('danger', 'Sessione scaduta. Riprova.');
        redirect($selfUrl);
    }

    if ($house === null) {
        set_flash('info', 'Non hai una casa attiva su cui richiedere la disdetta.');
        redirect($selfUrl);
    }

    if (post_str('action') === 'request_cancellation') {
        if ((string) $house['status'] === 'cancellation_requested') {
            set_flash('info', 'Hai gia inviato una richiesta di disdetta per questa casa.');
            redirect($selfUrl);
        }

        $message = post_str('message');
        if (mb_strlen($message) < 10) {
            set_flash('danger', 'Scrivi un messaggio di almeno 10 caratteri per il proprietario.');
            redirect($selfUrl);
        }

        if ($bookings->requestCancellation((int) $house['id'], $uid, $message)) {
            set_flash('success', 'Richiesta di disdetta inviata al proprietario.');
        } else {
            set_flash('info', 'La richiesta di disdetta non puo essere inviata in questo stato.');
        }
    }

    redirect($selfUrl);
}

if ($house === null) {
    $body = render_empty_state(
        'Non hai ancora una casa assegnata',
        'Quando una tua richiesta viene confermata con caparra, trovi qui stanza, proprietario e stato della prenotazione.',
        url_for('search.php'),
        'Torna agli annunci',
        'home'
    );

    $content = render_template('frontend/simple_page', [
        'page_title' => 'La mia casa',
        'page_intro' => 'La sezione mostra solo la casa o stanza attualmente associata al tuo account.',
        'page_action_url' => e(url_for('search.php')),
        'page_action_label' => 'Cerca stanze',
        'page_body' => $body,
    ]);

    render_page_frontend('La mia casa', $content, ['body_class' => 'page-dashboard']);
    exit;
}

$images = $properties->imagesFor((int) $house['property_id']);
$gallery = gallery_mosaic_html($images, (string) $house['property_title']);
$address = trim((string) $house['address'] . ' ' . (string) ($house['house_number'] ?? ''));
$moveIn = $house['move_in_date'] ? date('d/m/Y', strtotime((string) $house['move_in_date'])) : 'Non indicata';
$depositPaid = $house['deposit_paid_at'] ? date('d/m/Y H:i', strtotime((string) $house['deposit_paid_at'])) : 'Non disponibile';
$landlordPhone = trim((string) ($house['landlord_phone'] ?? ''));

$cancellationPanel = '';
if ((string) $house['status'] === 'cancellation_requested') {
    $cancellationPanel = '<div class="panel">'
        . '<h3>Disdetta richiesta</h3>'
        . '<p class="muted">Il proprietario ha ricevuto il tuo messaggio. La casa resta associata a te finche il proprietario non la rende di nuovo disponibile.</p>'
        . '<a class="button button-ghost button-block" href="' . e(url_for('booking.php?id=' . (int) $house['id'])) . '">Apri la conversazione</a>'
        . '</div>';
} else {
    $cancellationPanel = '<div class="panel">'
        . '<h3>Vuoi lasciare la casa?</h3>'
        . '<p class="muted">Questo non libera automaticamente la stanza: invia un messaggio al proprietario per concordare la disdetta.</p>'
        . '<form method="post" action="' . e($selfUrl) . '" class="form" data-validate>'
        . csrf_field('my_house_cancel')
        . '<input type="hidden" name="action" value="request_cancellation">'
        . '<label class="field"><span class="field-label">Messaggio al proprietario</span>'
        . '<textarea name="message" rows="4" minlength="10" required placeholder="Ciao, vorrei accordarmi per lasciare la stanza..."></textarea></label>'
        . '<button class="button button-block" type="submit">Richiedi disdetta</button>'
        . '</form></div>';
}

$content = render_template('frontend/my_house', [
    'back_url' => e(url_for('account/index.php')),
    'gallery' => $gallery,
    'property_title' => e((string) $house['property_title']),
    'room_name' => e((string) $house['room_name']),
    'room_type' => e(room_type_label((string) $house['room_type'])),
    'address' => e($address),
    'neighborhood' => e((string) $house['neighborhood_name']),
    'price' => e(format_price($house['price_monthly'])),
    'status_badge' => render_badge_booking_status((string) $house['status']),
    'room_status' => render_badge_room_status((string) $house['room_status']),
    'move_in' => e($moveIn),
    'deposit_paid' => e($depositPaid),
    'deposit_reference' => e((string) ($house['deposit_reference'] ?? '')),
    'landlord_name' => e((string) $house['landlord_name']),
    'landlord_email' => e((string) $house['landlord_email']),
    'landlord_phone' => e($landlordPhone !== '' ? $landlordPhone : 'Non indicato'),
    'room_url' => e(url_for('room.php?id=' . (int) $house['room_id'])),
    'booking_url' => e(url_for('booking.php?id=' . (int) $house['id'])),
    'cancellation_panel' => $cancellationPanel,
]);

render_page_frontend('La mia casa', $content, ['body_class' => 'page-dashboard']);
