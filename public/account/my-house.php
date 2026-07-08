<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
require_service('account.my_house');

$user = current_user();
$uid = (int) $user['id'];
$selfUrl = url_for('account/my-house.php');
$house = booking_active_for_student($uid);

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

        if (booking_request_cancellation((int) $house['id'], $uid, $message)) {
            set_flash('success', 'Richiesta di disdetta inviata al proprietario.');
        } else {
            set_flash('info', 'La richiesta di disdetta non puo essere inviata in questo stato.');
        }
    }

    redirect($selfUrl);
}

if ($house === null) {
    $content = '<section class="dashboard-shell">'
        . '<header class="dashboard-header"><div><p class="eyebrow">Area Studente</p><h1>La mia casa</h1>'
        . '<p class="muted">La sezione mostra la casa o stanza attualmente associata al tuo account.</p></div>'
        . '<a class="button-secondary" href="' . e(url_for('account/index.php')) . '">Area riservata</a></header>'
        . '<div class="empty-state"><p class="muted">Non hai ancora una casa assegnata.</p>'
        . '<a class="button-primary" href="' . e(url_for('search.php')) . '">Torna agli annunci</a></div>'
        . '</section>';

    render_page('La mia casa', $content, ['body_class' => 'page-dashboard']);
    exit;
}

$images = property_images((int) $house['property_id']);
if ($images === []) {
    $gallery = '<div class="image-gallery">' . property_image_markup(null, (string) $house['property_title'], 'detail-media') . '</div>';
} else {
    $items = '';
    foreach ($images as $image) {
        $caption = trim((string) ($image['caption'] ?? ''));
        $items .= '<figure>'
            . property_image_markup($image['filename'] ?? null, $caption !== '' ? $caption : (string) $house['property_title'], 'detail-media')
            . ($caption !== '' ? '<figcaption>' . e($caption) . '</figcaption>' : '')
            . '</figure>';
    }
    $gallery = '<div class="image-gallery">' . $items . '</div>';
}

$address = trim((string) $house['address'] . ' ' . (string) ($house['house_number'] ?? ''));
$moveIn = $house['move_in_date'] ? date('d/m/Y', strtotime((string) $house['move_in_date'])) : 'Non indicata';
$depositPaid = $house['deposit_paid_at'] ? date('d/m/Y H:i', strtotime((string) $house['deposit_paid_at'])) : 'Non disponibile';
$landlordPhone = trim((string) ($house['landlord_phone'] ?? ''));

if ((string) $house['status'] === 'cancellation_requested') {
    $cancelBox = '<section class="panel"><div class="panel-heading"><h2>Disdetta richiesta</h2></div>'
        . '<p class="muted">Il proprietario ha ricevuto il tuo messaggio. La casa resta associata a te finche il proprietario non la rende di nuovo disponibile.</p>'
        . '<p><a class="button-secondary" href="' . e(url_for('booking.php?id=' . (int) $house['id'])) . '">Apri la conversazione</a></p>'
        . '</section>';
} else {
    $cancelBox = '<section class="panel"><div class="panel-heading"><h2>Vuoi lasciare la casa?</h2></div>'
        . '<p class="muted">Questo non libera automaticamente la stanza: invia un messaggio al proprietario per concordare la disdetta.</p>'
        . '<form method="POST" action="' . e($selfUrl) . '" class="form-standard">'
        . csrf_field('my_house_cancel')
        . '<input type="hidden" name="action" value="request_cancellation">'
        . '<div class="form-group"><label for="message">Messaggio al proprietario</label>'
        . '<textarea id="message" name="message" rows="4" minlength="10" required>Vorrei accordarmi per lasciare la stanza.</textarea></div>'
        . '<button type="submit" class="button-primary">Richiedi disdetta</button>'
        . '</form></section>';
}

$content = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">Area Studente</p><h1>La mia casa</h1>'
    . '<p class="muted">La stanza o casa attualmente associata al tuo account.</p></div>'
    . '<div class="actions-group">'
    . '<a class="button-secondary" href="' . e(url_for('account/index.php')) . '">Area riservata</a> '
    . '<a class="button-secondary" href="' . e(url_for('booking.php?id=' . (int) $house['id'])) . '">Conversazione</a>'
    . '</div></header>'
    . '<section class="panel">' . $gallery . '</section>'
    . '<section class="panel"><div class="panel-heading"><h2>' . e((string) $house['property_title']) . '</h2>'
    . booking_status_badge((string) $house['status']) . '</div>'
    . '<p class="muted">' . e((string) $house['room_name']) . ' &middot; ' . e($address) . ' &middot; ' . e((string) $house['neighborhood_name']) . '</p>'
    . '<div class="meta-grid">'
    . '<p><strong>Tipologia:</strong><br>' . e(room_type_label((string) $house['room_type'])) . '</p>'
    . '<p><strong>Prezzo:</strong><br>' . e(format_price($house['price_monthly'])) . ' / mese</p>'
    . '<p><strong>Stato stanza:</strong><br>' . room_status_badge((string) $house['room_status']) . '</p>'
    . '<p><strong>Ingresso:</strong><br>' . e($moveIn) . '</p>'
    . '<p><strong>Caparra versata:</strong><br>' . e($depositPaid) . '</p>'
    . '<p><strong>Riferimento:</strong><br>' . e((string) ($house['deposit_reference'] ?? '')) . '</p>'
    . '</div>'
    . '<p><a class="button-secondary" href="' . e(url_for('room.php?id=' . (int) $house['room_id'])) . '">Vedi annuncio</a></p>'
    . '</section>'
    . '<section class="panel"><div class="panel-heading"><h2>Proprietario</h2></div>'
    . '<p><strong>' . e((string) $house['landlord_name']) . '</strong></p>'
    . '<p class="muted">' . e((string) $house['landlord_email']) . '</p>'
    . '<p class="muted">' . e($landlordPhone !== '' ? $landlordPhone : 'Non indicato') . '</p>'
    . '</section>'
    . $cancelBox
    . '</section>';

render_page('La mia casa', $content, ['body_class' => 'page-dashboard']);
