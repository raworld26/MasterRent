<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$rooms = new RoomRepository();
$properties = new PropertyRepository();
$reviews = new ReviewRepository();
$bookings = new BookingRepository();

$id = (int) query_str('id');
$room = $id > 0 ? $rooms->find($id) : null;

if ($room === null) {
    http_response_code(404);
    $content = '<section class="panel empty-state"><h1>Stanza non trovata</h1>'
        . '<p class="muted">L\'annuncio che cerchi non esiste o non è più disponibile.</p>'
        . '<a class="button" href="' . e(url_for('search.php')) . '">Cerca altre stanze</a></section>';
    render_page_frontend('Stanza non trovata', $content, ['body_class' => 'page-room']);
    exit;
}

$user = current_user();
$isStudent = user_has_group('student');
$propertyId = (int) $room['property_id'];
$roomUrl = url_for('room.php?id=' . $id);
$isOwner = $user !== null && (int) $room['landlord_id'] === (int) $user['id'];

/* ---------------- Gestione POST (richiesta o recensione) ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = post_str('action');

    if ($action === 'book') {
        if ($user === null) {
            redirect(url_for('login.php'));
        }
        if (!$isStudent) {
            set_flash('danger', 'Solo gli account studente possono richiedere una visita.');
        } elseif (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'booking')) {
            set_flash('danger', 'Sessione scaduta. Riprova.');
        } elseif ((string) $room['status'] !== 'available' || (int) $room['is_available'] !== 1) {
            set_flash('info', 'Questa stanza non è più disponibile.');
        } elseif ($bookings->activeForStudent((int) $user['id']) !== null) {
            set_flash('warning', 'Hai già una casa attuale. Per prenotare un\'altra stanza, devi prima disdire quella attuale.');
        } elseif ($bookings->existsForStudentRoom((int) $user['id'], $id)) {
            set_flash('info', 'Hai già inviato una richiesta per questa stanza.');
        } else {
            $message = post_str('message');
            $moveIn = post_str('move_in_date');
            if (mb_strlen($message) < 10) {
                set_flash('danger', 'Scrivi un messaggio di almeno 10 caratteri.');
            } else {
                $bookings->create($id, (int) $user['id'], $message, $moveIn !== '' ? $moveIn : null);
                set_flash('success', 'Richiesta di visita inviata! Il proprietario ti risponderà a breve.');
                redirect(url_for('account/bookings.php'));
            }
        }
        redirect($roomUrl);
    }

    if ($action === 'review') {
        if ($user === null || !$isStudent || $isOwner
            || !$reviews->studentStayed((int) ($user['id'] ?? 0), $id)
            || $reviews->hasReviewed((int) ($user['id'] ?? 0), $id)) {
            http_response_code(403);
            render_page_frontend(
                'Recensione non autorizzata',
                '<section class="panel empty-state"><h1>Accesso negato</h1><p class="muted">Puoi recensire solo una casa in cui hai effettivamente soggiornato e con rapporto concluso. Non puoi recensire due volte la stessa stanza.</p></section>',
                ['body_class' => 'page-room']
            );
            exit;
        }

        if ($user === null || !$isStudent) {
            set_flash('danger', 'Non puoi recensire questa stanza.');
        } elseif (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'review')) {
            set_flash('danger', 'Sessione scaduta. Riprova.');
        } elseif (!$reviews->studentStayed((int) $user['id'], $id)) {
            set_flash('danger', 'Puoi recensire solo le stanze in cui hai alloggiato.');
        } elseif ($reviews->hasReviewed((int) $user['id'], $id)) {
            set_flash('info', 'Hai già recensito questa stanza.');
        } else {
            $rating = post_int('rating', 0);
            $title = post_str('title');
            $body = post_str('body');
            if ($rating < 1 || $rating > 5 || $title === '' || mb_strlen($body) < 10) {
                set_flash('danger', 'Inserisci voto, titolo e un commento di almeno 10 caratteri.');
            } else {
                $reviews->create($id, (int) $user['id'], $rating, $title, $body);
                set_flash('success', 'Grazie per la tua recensione!');
            }
        }
        redirect($roomUrl);
    }
}

/* ---------------- Preparazione dati per la vista ---------------- */
$images = $properties->imagesFor($propertyId);
$amenities = $rooms->amenitiesForRoom($id);
$rating = $reviews->ratingForRoom($id);
$reviewRows = $reviews->forRoom($id);
$favIds = current_favorite_ids();

// Galleria mosaico + lightbox
$galleryHtml = gallery_mosaic_html($images, (string) $room['name']);

// Mappa della zona (usando Google Maps per ottenere il pin esatto sull'indirizzo)
$fullAddress = trim($room['address'] . ' ' . (string) ($room['house_number'] ?? ''));
$mapEmbed = address_map_embed_url($fullAddress);
$mapLink = address_map_link_url($fullAddress);

// Accessori
$amenityRows = [];
foreach ($amenities as $a) {
    $amenityRows[] = ['amenity_icon' => e((string) $a['icon']), 'amenity_name' => e($a['name'])];
}
$amenitiesHtml = $amenityRows === [] ? '' : render_list('frontend/_amenities', $amenityRows);

// Recensioni
$revRows = array_map(static fn ($r) => [
    'review_author' => e($r['author']),
    'review_stars' => stars_html((float) $r['rating']),
    'review_title' => e((string) $r['title']),
    'review_body' => e((string) $r['body']),
    'review_date' => e(date('d/m/Y', strtotime((string) $r['created_at']))),
], $reviewRows);
$reviewsHtml = $revRows === [] ? '' : render_list('frontend/_reviews', $revRows);

// Stati per i blocchi condizionali
$myBooking = ($user !== null && $isStudent) ? $bookings->findForStudentRoom((int) $user['id'], $id) : null;
$myStatus = $myBooking['status'] ?? null;
$myBookingId = isset($myBooking['id']) ? (int) $myBooking['id'] : 0;
$roomAvailable = (string) $room['status'] === 'available' && (int) $room['is_available'] === 1;

// La stanza è prenotabile da questo utente?
$canRequestVisit = $isStudent && !$isOwner && $roomAvailable && $myStatus === null;
// Stanza non disponibile per un motivo diverso dalla propria prenotazione.
$showUnavailable = !$roomAvailable && !in_array($myStatus, ['deposit_paid', 'cancellation_requested'], true);

$hasCompletedStay = $user !== null && $isStudent && !$isOwner
    && $reviews->studentStayed((int) $user['id'], $id);
$alreadyReviewed = $user !== null && $isStudent
    && $reviews->hasReviewed((int) $user['id'], $id);
$canReview = $hasCompletedStay && !$alreadyReviewed;
$reviewNote = '';
if ($user !== null && $isStudent && !$canReview) {
    $reviewNote = $isOwner
        ? 'Il proprietario non puo recensire il proprio annuncio.'
        : ($alreadyReviewed
            ? 'Hai gia recensito questa stanza.'
            : 'Puoi recensire solo una casa in cui hai effettivamente soggiornato e con rapporto concluso.');
}

$depositAmount = deposit_amount_for((float) $room['price_monthly']);

$ratingText = $rating['count'] > 0
    ? number_format($rating['avg'], 1, ',', '') . ' / 5 · ' . $rating['count'] . ' recensioni'
    : 'Ancora nessuna recensione';

require_once __DIR__ . '/../src/ZoneEstimates.php';
$zones = get_zone_estimates();
$zoneCode = (string) ($room['neighborhood_code'] ?? '');
$zoneData = $zones[$zoneCode] ?? null;
$distancesHtml = '';
if ($zoneData) {
    foreach ($zoneData['poles'] as $code => $p) {
        $bestBadge = $p['best'] ? '<span class="card-badge card-badge-ok">Comoda</span>' : '';
        $distancesHtml .= '
        <div class="distance-row">
            <div class="distance-pole"><strong>' . e($p['name']) . '</strong> ' . $bestBadge . '</div>
            <div class="distance-times">
                <span>🚶 ' . e($p['walk']) . '</span>
                <span>🚌 ' . e($p['bus']) . '</span>
            </div>
        </div>';
    }
} else {
    $distancesHtml = '<p class="muted">Stime non disponibili per questa zona.</p>';
}

$content = render_template('frontend/room', [
    'search_url' => e(url_for('search.php')),
    'room_action' => e($roomUrl),
    'room_id' => (string) $id,
    'room_title' => e($room['name']),
    'room_type' => e(room_type_label((string) $room['type'])),
    'room_sqm' => (14 + (($id * 2) % 7)) . ' m²',
    'property_title' => e($room['property_title']),
    'property_description' => e((string) ($room['property_description'] ?? 'Nessuna descrizione disponibile.')),
    'neighborhood_name' => e($room['neighborhood_name']),
    'address' => e(trim($room['address'] . ' ' . (string) ($room['house_number'] ?? ''))),
    'distances_html' => $distancesHtml,
    'price' => e(format_price($room['price_monthly'])),
    'contract_type' => e((string) $room['contract_type']),
    'deposit' => e(format_price($depositAmount) . ' (1 mensilità)'),
    'deposit_amount' => e(format_price($depositAmount)),
    'room_status_pill' => $roomAvailable ? '' : room_status_badge((string) $room['status']),
    'expenses' => $room['expenses_included'] ? 'Incluse nel prezzo' : 'Escluse',
    'expenses_note' => $room['expenses_included'] ? 'Spese incluse nel prezzo' : '',
    'heating' => e(heating_label((string) $room['heating_type'])),
    'elevator' => $room['has_elevator'] ? 'Sì' : 'No',
    'rating_stars' => stars_html($rating['avg']),
    'rating_text' => e($ratingText),
    'fav_class' => in_array($id, $favIds, true) ? 'is-fav' : '',
    'fav_pressed' => in_array($id, $favIds, true) ? 'true' : 'false',
    'gallery' => $galleryHtml,
    'amenities' => $amenitiesHtml,
    'poles' => $distancesHtml,
    'reviews' => $reviewsHtml,
    'no_reviews' => $revRows === [] ? '1' : '',
    'show_review_form' => $canReview ? '1' : '',
    'review_note' => e($reviewNote),
    'review_csrf' => csrf_field('review'),
    'show_book_form' => $canRequestVisit ? '1' : '',
    'show_visit_pending' => $myStatus === 'visit_requested' ? '1' : '',
    'show_pay_deposit' => $myStatus === 'approved_pending_deposit' ? '1' : '',
    'show_reserved_by_me' => $myStatus === 'deposit_paid' ? '1' : '',
    'show_cancellation_requested' => $myStatus === 'cancellation_requested' ? '1' : '',
    'show_rejected' => $myStatus === 'rejected' ? '1' : '',
    'show_unavailable' => $showUnavailable ? '1' : '',
    'unavailable_banner' => $showUnavailable
        ? render_banner(
            'Stanza non disponibile',
            'Questa stanza è stata prenotata o ritirata e al momento non è prenotabile. Dai un\'occhiata alle altre disponibilità.',
            'warning'
        )
        : '',
    'show_book_login' => ($user === null && $roomAvailable) ? '1' : '',
    'show_owner_note' => $isOwner ? '1' : '',
    'book_csrf' => csrf_field('booking'),
    'my_bookings_url' => e(url_for('account/bookings.php')),
    'booking_url' => e($myBookingId > 0 ? url_for('booking.php?id=' . $myBookingId) : url_for('account/bookings.php')),
    'deposit_url' => e($myBookingId > 0 ? url_for('deposit.php?id=' . $myBookingId) : '#'),
    'login_url' => e(url_for('login.php')),
    'register_url' => e(url_for('register.php')),
    'landlord_name' => e(trim($room['landlord_first'] . ' ' . $room['landlord_last'])),
    'landlord_initial' => e(mb_strtoupper(mb_substr(trim($room['landlord_first']) ?: 'U', 0, 1))),
    'map_embed' => e($mapEmbed),
    'map_link' => e($mapLink),
    'map_zone' => e($room['neighborhood_name']),
]);

render_page_frontend($room['name'] . ' · ' . $room['neighborhood_name'], $content, [
    'body_class' => 'page-room',
    'meta_description' => excerpt(
        $room['name'] . ' a ' . $room['neighborhood_name'] . ', L\'Aquila: '
        . format_price($room['price_monthly']) . ' al mese, caparra simulata di 1 mensilità. '
        . (string) ($room['property_description'] ?? ''),
        160
    ),
]);
