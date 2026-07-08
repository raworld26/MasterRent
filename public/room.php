<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$id = (int) query_str('id');
$room = $id > 0 ? room_find($id) : null;

if ($room === null) {
    http_response_code(404);
    render_page('Stanza non trovata',
        '<section class="panel empty-state"><h1>Stanza non trovata</h1>'
        . '<p class="muted">L\'annuncio che cerchi non esiste o non è più disponibile.</p>'
        . '<a class="button-primary" href="' . e(url_for('search.php')) . '">Cerca altre stanze</a></section>',
        ['body_class' => 'page-public']);
    exit;
}

$user = current_user();
$isStudent = user_has_group('student');
$propertyId = (int) $room['property_id'];
$roomUrl = url_for('room.php?id=' . $id);
$roomAvailable = (string) $room['status'] === 'available' && (int) $room['is_available'] === 1;
$isOwner = $user !== null && (int) $room['landlord_id'] === (int) $user['id'];

/* ---------------- Gestione POST ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = post_str('action');

    if ($action === 'toggle_favorite') {
        if ($user === null) {
            redirect(url_for('login.php'));
        }
        if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'room_favorite')) {
            set_flash('danger', 'Sessione scaduta. Riprova.');
        } elseif (!$isStudent) {
            set_flash('danger', 'Solo gli account studente possono salvare i preferiti.');
        } else {
            $nowFav = toggle_favorite($id);
            set_flash('success', $nowFav ? 'Stanza salvata nei preferiti.' : 'Stanza rimossa dai preferiti.');
        }
        redirect($roomUrl);
    }

    if ($action === 'book') {
        if ($user === null) {
            redirect(url_for('login.php'));
        }
        if (!$isStudent) {
            set_flash('danger', 'Solo gli account studente possono richiedere una visita.');
        } elseif (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'booking')) {
            set_flash('danger', 'Sessione scaduta. Riprova.');
        } elseif (!$roomAvailable) {
            set_flash('info', 'Questa stanza non è più disponibile.');
        } elseif (booking_active_for_student((int) $user['id']) !== null) {
            set_flash('warning', 'Hai già una casa attuale. Per prenotare un\'altra stanza, devi prima disdire quella attuale.');
        } elseif (booking_exists_for_student_room((int) $user['id'], $id)) {
            set_flash('info', 'Hai già una richiesta per questa stanza.');
        } else {
            $message = post_str('message');
            $moveIn = post_str('move_in_date');
            if (mb_strlen($message) < 10) {
                set_flash('danger', 'Scrivi un messaggio di almeno 10 caratteri.');
                redirect($roomUrl);
            }
            if ($moveIn !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $moveIn)) {
                $moveIn = '';
            }
            booking_create($id, (int) $user['id'], $message, $moveIn !== '' ? $moveIn : null);
            set_flash('success', 'Richiesta di visita inviata! Il proprietario ti risponderà a breve.');
            redirect(url_for('account/bookings.php'));
        }
        redirect($roomUrl);
    }

    if ($action === 'review') {
        if ($user === null || !$isStudent || $isOwner
            || !review_student_stayed((int) ($user['id'] ?? 0), $id)
            || review_has_reviewed((int) ($user['id'] ?? 0), $id)) {
            http_response_code(403);
            render_page(
                'Recensione non autorizzata',
                '<section class="panel empty-state"><h1>Accesso negato</h1><p class="muted">Puoi recensire solo una casa in cui hai effettivamente soggiornato e con rapporto concluso. Non puoi recensire due volte la stessa stanza.</p></section>',
                ['body_class' => 'page-public']
            );
            exit;
        }

        if ($user === null || !$isStudent) {
            set_flash('danger', 'Non puoi recensire questa stanza.');
        } elseif (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'review')) {
            set_flash('danger', 'Sessione scaduta. Riprova.');
        } elseif (!review_student_stayed((int) $user['id'], $id)) {
            set_flash('danger', 'Puoi recensire solo le stanze in cui hai alloggiato.');
        } elseif (review_has_reviewed((int) $user['id'], $id)) {
            set_flash('info', 'Hai già recensito questa stanza.');
        } else {
            $rating = post_int('rating', 0);
            $title = post_str('title');
            $body = post_str('body');
            if ($rating < 1 || $rating > 5 || $title === '' || mb_strlen($body) < 10) {
                set_flash('danger', 'Inserisci voto, titolo e un commento di almeno 10 caratteri.');
            } else {
                review_create($id, (int) $user['id'], $rating, $title, $body);
                set_flash('success', 'Grazie per la tua recensione!');
            }
        }
        redirect($roomUrl);
    }

    redirect($roomUrl);
}

/* ---------------- Dati per la vista ---------------- */
$images = property_images($propertyId);
$amenities = array_column(room_amenities($id), 'name');
$rating = review_rating_for_room($id);
$reviewRows = reviews_for_room($id);
$favIds = current_favorite_ids();

$myBooking = ($user !== null && $isStudent) ? booking_find_for_student_room((int) $user['id'], $id) : null;
$myStatus = $myBooking['status'] ?? null;
$myBookingId = isset($myBooking['id']) ? (int) $myBooking['id'] : 0;

$depositAmount = deposit_amount_for((float) $room['price_monthly']);

/* Galleria immagini (essenziale, senza JavaScript). */
if ($images === []) {
    $imageGallery = '<div class="image-gallery">' . property_image_markup(null, (string) $room['property_title'], 'detail-media') . '</div>';
} else {
    $items = '';
    foreach ($images as $image) {
        $caption = trim((string) ($image['caption'] ?? ''));
        $items .= '<figure>'
            . property_image_markup($image['filename'] ?? null, $caption !== '' ? $caption : (string) $room['property_title'], 'detail-media')
            . ($caption !== '' ? '<figcaption>' . e($caption) . '</figcaption>' : '')
            . '</figure>';
    }
    $imageGallery = '<div class="image-gallery">' . $items . '</div>';
}

/* Distanze dai poli (stime ZoneEstimates per macro-zona). */
$zones = get_zone_estimates();
$zoneData = $zones[(string) ($room['neighborhood_code'] ?? '')] ?? null;
if ($zoneData) {
    $distances = '<ul class="item-list">';
    foreach ($zoneData['poles'] as $p) {
        $best = !empty($p['best']) ? ' <span class="badge badge-success">Comoda</span>' : '';
        $distances .= '<li><span class="item-title">' . e($p['name']) . $best . '</span>'
            . '<span class="item-meta">🚶 ' . e($p['walk']) . ' · 🚌 ' . e($p['bus']) . '</span></li>';
    }
    $distances .= '</ul>';
} else {
    $distances = '<p class="muted">Stime non disponibili per questa zona.</p>';
}

/* Fase 1: le distanze pubbliche sono quelle reali inserite manualmente dal proprietario. */
$propertyPoles = property_poles($propertyId);
if ($propertyPoles !== []) {
    $distances = '<ul class="item-list">';
    foreach ($propertyPoles as $pole) {
        $distances .= '<li><span class="item-title">' . e((string) $pole['pole_name']) . '</span>'
            . '<span class="item-meta">' . e((string) $pole['distance_minutes']) . ' min - '
            . e(transit_label((string) $pole['transit_type'])) . '</span></li>';
    }
    $distances .= '</ul>';
} else {
    $distances = '<p class="muted">Distanze dai poli non ancora specificate dal proprietario.</p>';
}

/* Recensioni pubblicate. */
if ($reviewRows === []) {
    $reviewList = '<p class="muted">Nessuna recensione pubblica per questa stanza.</p>';
} else {
    $reviewList = '<ul class="item-list review-list">';
    foreach ($reviewRows as $rev) {
        $reviewList .= '<li>'
            . '<span class="item-title">' . stars_html((float) $rev['rating']) . ' ' . e((string) $rev['title']) . '</span>'
            . '<span class="item-meta">' . e($rev['author']) . ' · ' . e(date('d/m/Y', strtotime((string) $rev['created_at']))) . '</span>'
            . '<p>' . nl2br(e((string) $rev['body'])) . '</p>'
            . '</li>';
    }
    $reviewList .= '</ul>';
}

/* Banner "non disponibile" (se non prenotabile e non è la mia prenotazione). */
$banner = '';
if (!$roomAvailable && !in_array($myStatus, ['deposit_paid', 'cancellation_requested'], true)) {
    $banner = '<div class="alert alert-danger" role="status"><strong>Stanza non disponibile.</strong> '
        . 'Questa stanza è stata prenotata o ritirata e al momento non è prenotabile.</div>';
}

/* Box "La tua richiesta": dipende dal ruolo e dallo stato della prenotazione. */
$bookingBox = '';
$canRequestVisit = $isStudent && !$isOwner && $roomAvailable && $myStatus === null;

if ($isOwner) {
    $bookingBox = '<p class="muted">Questo è un tuo annuncio.</p>';
} elseif ($myStatus === 'visit_requested') {
    $bookingBox = '<p>Richiesta di visita inviata: in attesa della risposta del proprietario.</p>'
        . '<p><a class="button-secondary" href="' . e(url_for('booking.php?id=' . $myBookingId)) . '">Apri la richiesta</a></p>';
} elseif ($myStatus === 'approved_pending_deposit') {
    $bookingBox = '<p>Richiesta approvata! Versa la caparra di ' . e(format_price($depositAmount)) . ' per prenotare la stanza.</p>'
        . '<p><a class="button-primary" href="' . e(url_for('deposit.php?id=' . $myBookingId)) . '">Paga la caparra</a> '
        . '<a class="button-secondary" href="' . e(url_for('booking.php?id=' . $myBookingId)) . '">Apri la richiesta</a></p>';
} elseif ($myStatus === 'deposit_paid') {
    $bookingBox = '<p>Hai prenotato questa stanza: caparra versata.</p>'
        . '<p><a class="button-secondary" href="' . e(url_for('booking.php?id=' . $myBookingId)) . '">Apri la richiesta</a></p>';
} elseif ($myStatus === 'cancellation_requested') {
    $bookingBox = '<p>Hai richiesto la disdetta per questa stanza. Il proprietario deve ancora renderla disponibile.</p>'
        . '<p><a class="button-secondary" href="' . e(url_for('booking.php?id=' . $myBookingId)) . '">Apri la conversazione</a></p>';
} elseif ($myStatus === 'rejected') {
    $bookingBox = '<p class="muted">La tua richiesta per questa stanza è stata rifiutata.</p>';
} elseif ($myStatus === 'withdrawn') {
    $bookingBox = '<p class="muted">Hai ritirato la tua richiesta per questa stanza.</p>';
} elseif ($canRequestVisit) {
    $bookingBox = '<form method="POST" action="' . e($roomUrl) . '" class="form-standard">'
        . csrf_field('booking')
        . '<input type="hidden" name="action" value="book">'
        . '<div class="form-group"><label for="move_in_date">Data ingresso desiderata</label><input id="move_in_date" type="date" name="move_in_date"></div>'
        . '<div class="form-group"><label for="message">Messaggio al proprietario</label><textarea id="message" name="message" rows="4" minlength="10" required>Vorrei visitare questa stanza.</textarea></div>'
        . '<button type="submit" class="button-primary">Richiedi visita</button>'
        . '</form>';
} elseif ($user === null && $roomAvailable) {
    $bookingBox = '<p><a class="button-primary" href="' . e(url_for('login.php')) . '">Accedi per richiedere una visita</a></p>'
        . '<p class="muted">Non hai un account? <a href="' . e(url_for('register.php')) . '">Registrati</a>.</p>';
} elseif ($user !== null && !$isStudent) {
    $bookingBox = '<p class="muted">Le richieste di visita sono disponibili solo agli account studente.</p>';
} else {
    $bookingBox = '<p class="muted">Questa stanza non è al momento prenotabile.</p>';
}

/* Box preferiti (toggle via form POST). */
if ($isStudent) {
    $isFav = in_array($id, $favIds, true);
    $favoriteBox = '<form method="POST" action="' . e($roomUrl) . '" class="inline-form">'
        . csrf_field('room_favorite')
        . '<input type="hidden" name="action" value="toggle_favorite">'
        . '<button type="submit" class="button-secondary">' . ($isFav ? 'Rimuovi dai preferiti' : 'Salva nei preferiti') . '</button>'
        . '</form>';
} elseif ($user === null) {
    $favoriteBox = '<p class="muted">Accedi come studente per salvare questa stanza.</p>';
} else {
    $favoriteBox = '<p class="muted">I preferiti sono disponibili solo agli account studente.</p>';
}

/* Form recensione (solo studenti che hanno alloggiato e non hanno già recensito). */
$reviewForm = '';
$hasCompletedStay = $user !== null && $isStudent && !$isOwner
    && review_student_stayed((int) $user['id'], $id);
$alreadyReviewed = $user !== null && $isStudent
    && review_has_reviewed((int) $user['id'], $id);
$canReview = $hasCompletedStay && !$alreadyReviewed;
$reviewNote = '';
if ($user !== null && $isStudent && !$canReview) {
    $reviewNote = $isOwner
        ? '<p class="muted">Il proprietario non puo recensire il proprio annuncio.</p>'
        : ($alreadyReviewed
            ? '<p class="muted">Hai gia recensito questa stanza.</p>'
            : '<p class="muted">Puoi recensire solo una casa in cui hai effettivamente soggiornato e con rapporto concluso.</p>');
}
if ($canReview) {
    $reviewForm = '<div class="panel-heading"><h3>Lascia una recensione</h3></div>'
        . '<form method="POST" action="' . e($roomUrl) . '" class="form-standard">'
        . csrf_field('review')
        . '<input type="hidden" name="action" value="review">'
        . '<div class="form-grid">'
        . '<div class="form-group"><label for="rating">Valutazione</label><select id="rating" name="rating">'
        . '<option value="5">5 - Ottima</option><option value="4">4 - Buona</option><option value="3">3 - Sufficiente</option><option value="2">2 - Scarsa</option><option value="1">1 - Pessima</option>'
        . '</select></div>'
        . '<div class="form-group"><label for="title">Titolo</label><input id="title" name="title" type="text" maxlength="150" required></div>'
        . '</div>'
        . '<div class="form-group"><label for="body">Commento</label><textarea id="body" name="body" rows="4" required></textarea></div>'
        . '<button type="submit" class="button-primary">Pubblica recensione</button>'
        . '</form>';
} else {
    $reviewForm = $reviewNote;
}

$ratingLine = $rating['count'] > 0
    ? stars_html($rating['avg']) . ' ' . number_format($rating['avg'], 1, ',', '') . ' / 5 · ' . $rating['count'] . ($rating['count'] === 1 ? ' recensione' : ' recensioni')
    : 'Ancora nessuna recensione';

$content = render_template('room.html', [
    'back_url' => e(url_for('search.php')),
    'room_name' => e((string) $room['name']),
    'rating_line' => $ratingLine,
    'banner' => $banner,
    'image_gallery' => $imageGallery,
    'property_title' => e((string) $room['property_title']),
    'type' => e(room_type_label((string) $room['type'])),
    'price' => e(format_price($room['price_monthly'])),
    'deposit' => e(format_price($depositAmount) . ' (1 mensilità)'),
    'expenses' => !empty($room['expenses_included']) ? 'Incluse nel prezzo' : 'Escluse',
    'contract_type' => e((string) $room['contract_type']),
    'heating' => e(heating_label((string) $room['heating_type'])),
    'elevator' => !empty($room['has_elevator']) ? 'Sì' : 'No',
    'status_line' => $roomAvailable ? 'Disponibile' : room_status_badge((string) $room['status']),
    'address' => e(trim($room['address'] . ' ' . (string) ($room['house_number'] ?? ''))),
    'neighborhood' => e((string) $room['neighborhood_name']),
    'distances' => $distances,
    'description' => e((string) ($room['property_description'] ?? 'Nessuna descrizione disponibile.')),
    'amenities' => e($amenities === [] ? 'Nessun servizio indicato' : implode(', ', $amenities)),
    'landlord' => e(trim($room['landlord_first'] . ' ' . $room['landlord_last'])),
    'booking_box' => $bookingBox,
    'favorite_box' => $favoriteBox,
    'review_list' => $reviewList,
    'review_form' => $reviewForm,
]);

render_page($room['name'] . ' · ' . $room['neighborhood_name'], $content, ['body_class' => 'page-public']);
