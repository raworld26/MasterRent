<?php

declare(strict_types=1);

/*
 * Helper di presentazione e di dominio (formattazione, etichette, badge,
 * immagini, preferiti). Restituiscono stringhe già pronte (ed "escaped" dove
 * necessario). Stile phase1: procedurale, markup essenziale, nessun JavaScript.
 */

function format_price($value): string
{
    return number_format((float) $value, 0, ',', '.') . ' €';
}

/* La caparra è sempre pari a UNA mensilità d'affitto. */
function deposit_amount_for(float $monthlyPrice): float
{
    return round($monthlyPrice, 2);
}

function excerpt(?string $text, int $length = 140): string
{
    $text = trim((string) $text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length - 1) . '…';
}

/* ---------------------------------------------------------------------------
 * Etichette di dominio.
 * ------------------------------------------------------------------------- */
function room_type_label(string $type): string
{
    return [
        'single' => 'Singola',
        'double' => 'Doppia',
        'bed_space' => 'Posto letto',
        'entire_apartment' => 'Intero appartamento',
    ][$type] ?? $type;
}

/* Elenco tipologie stanza per le select (formato array). */
function room_type_options(): array
{
    return [
        ['id' => 'single', 'name' => 'Singola'],
        ['id' => 'double', 'name' => 'Doppia'],
        ['id' => 'bed_space', 'name' => 'Posto letto'],
        ['id' => 'entire_apartment', 'name' => 'Intero appartamento'],
    ];
}

function transit_type_label(string $type): string
{
    return ['foot' => 'a piedi', 'bus' => 'in bus', 'car' => 'in auto'][$type] ?? $type;
}

/* Alias usato in alcune viste. */
function transit_label(string $type): string
{
    return transit_type_label($type);
}

function heating_label(string $type): string
{
    return ['autonomous' => 'Autonomo', 'centralized' => 'Centralizzato'][$type] ?? $type;
}

function booking_status_label(string $status): string
{
    return [
        'visit_requested' => 'Visita richiesta',
        'approved_pending_deposit' => 'Approvata · caparra da versare',
        'rejected' => 'Rifiutata',
        'cancellation_requested' => 'Disdetta richiesta',
        'completed' => 'Conclusa',
        'deposit_paid' => 'Prenotata · caparra versata',
        'withdrawn' => 'Ritirata',
    ][$status] ?? $status;
}

function booking_status_badge(string $status): string
{
    $class = [
        'visit_requested' => 'status-waiting',
        'approved_pending_deposit' => 'status-approved',
        'rejected' => 'status-rejected',
        'cancellation_requested' => 'status-waiting',
        'completed' => 'status-closed',
        'deposit_paid' => 'status-approved',
        'withdrawn' => 'status-closed',
    ][$status] ?? 'status-closed';

    return '<span class="status-pill ' . e($class) . '"><span class="status-dot" aria-hidden="true"></span><span>'
        . e(booking_status_label($status)) . '</span></span>';
}

/*
 * Stepper del flusso di prenotazione (essenziale, senza JavaScript):
 * Visita richiesta · Approvata · Caparra · Prenotata. Gestisce anche i flussi
 * interrotti (rifiutata / ritirata).
 */
function booking_stepper_html(string $status): string
{
    $labels = ['Visita richiesta', 'Approvata', 'Caparra', 'Prenotata'];
    $halted = false;

    switch ($status) {
        case 'approved_pending_deposit':
            $current = 2;
            break;
        case 'deposit_paid':
            $current = 3;
            break;
        case 'cancellation_requested':
            $current = 3;
            $labels[3] = 'Disdetta richiesta';
            break;
        case 'completed':
            $current = 3;
            $labels[3] = 'Conclusa';
            break;
        case 'rejected':
            $current = 1;
            $labels[1] = 'Rifiutata';
            $halted = true;
            break;
        case 'withdrawn':
            $current = 1;
            $labels[1] = 'Ritirata';
            $halted = true;
            break;
        case 'visit_requested':
        default:
            $current = 0;
    }

    $completed = in_array($status, ['deposit_paid', 'completed'], true);
    $items = '';
    foreach ($labels as $i => $label) {
        $class = 'step';
        if ($i < $current || ($i === $current && $completed)) {
            $class .= ' step-done';
        } elseif ($i === $current) {
            $class .= $halted ? ' step-halted' : ' step-current';
        }
        $items .= '<li class="' . $class . '">' . e($label) . '</li>';
    }

    return '<ol class="booking-stepper">' . $items . '</ol>';
}

function room_status_label(string $status): string
{
    return [
        'available' => 'Disponibile',
        'reserved' => 'Prenotata',
        'unavailable' => 'Non disponibile',
    ][$status] ?? $status;
}

function room_status_badge(string $status): string
{
    $class = [
        'available' => 'badge-success',
        'reserved' => 'badge-danger',
        'unavailable' => 'badge-danger',
    ][$status] ?? 'badge-danger';

    return '<span class="badge ' . e($class) . '">' . e(room_status_label($status)) . '</span>';
}

/* Stelline di valutazione (1-5) come testo essenziale. */
function stars_html(float $rating): string
{
    $full = (int) round($rating);
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $full ? '★' : '☆';
    }
    return '<span class="stars" aria-label="' . e(number_format($rating, 1)) . ' su 5">' . $stars . '</span>';
}

/* ---------------------------------------------------------------------------
 * Select / opzioni.
 * ------------------------------------------------------------------------- */
function selected_attr($current, $value): string
{
    return (string) $current === (string) $value ? ' selected' : '';
}

function checked_attr(bool $checked): string
{
    return $checked ? ' checked' : '';
}

/*
 * Lista di <option> per una select, con eventuale opzione segnaposto.
 */
function select_options(array $items, $selected, string $valueKey = 'id', string $labelKey = 'name', string $placeholder = ''): string
{
    $html = $placeholder !== '' ? '<option value="">' . e($placeholder) . '</option>' : '';
    foreach ($items as $item) {
        $value = (string) $item[$valueKey];
        $sel = ((string) $selected === $value && (string) $selected !== '') ? ' selected' : '';
        $html .= '<option value="' . e($value) . '"' . $sel . '>' . e($item[$labelKey]) . '</option>';
    }
    return $html;
}

/* Opzioni quartieri (per le select pubbliche). */
function public_neighborhood_options(int $selectedNeighborhoodId, string $emptyLabel = 'Tutti i quartieri'): string
{
    $html = '<option value="">' . e($emptyLabel) . '</option>';
    try {
        $rows = db()->query('SELECT id, name FROM neighborhoods ORDER BY name ASC')->fetchAll();
    } catch (Throwable $exception) {
        error_log('[MasterRent] Neighborhood options query failed: ' . $exception->getMessage());
        return $html;
    }
    foreach ($rows as $row) {
        $id = (int) $row['id'];
        $html .= '<option value="' . e((string) $id) . '"' . selected_attr((string) $selectedNeighborhoodId, (string) $id) . '>' . e($row['name']) . '</option>';
    }
    return $html;
}

/* ---------------------------------------------------------------------------
 * Immagini annunci (risoluzione file locale, nessun asset esterno).
 * ------------------------------------------------------------------------- */
function property_image_public_url(?string $filename): ?string
{
    $filename = trim((string) $filename);
    if ($filename === '') {
        return null;
    }

    $relativePath = ltrim(str_replace('\\', '/', $filename), '/');
    if ($relativePath === '' || str_contains($relativePath, '..')) {
        return null;
    }

    $path = PROJECT_ROOT . '/public/assets/uploads/' . $relativePath;
    if (!is_file($path)) {
        return null;
    }

    return asset_url('uploads/' . $relativePath);
}

/* Blocco immagine (con placeholder se assente). */
function property_image_markup(?string $filename, string $alt, string $class = 'property-media'): string
{
    $url = property_image_public_url($filename);
    if ($url === null) {
        return '<div class="' . e($class) . ' image-placeholder">Nessuna immagine</div>';
    }

    return '<div class="' . e($class) . '"><img src="' . e($url) . '" alt="' . e($alt) . '"></div>';
}

/*
 * Salvataggio di una singola immagine annuncio caricata (JPG/PNG/WebP, max 4 MB).
 * Ritorna ['filename','caption'] oppure null se nessun file. Lancia RuntimeException
 * in caso di errore di validazione.
 */
function save_property_image_upload(array $file, int $propertyId): ?array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Caricamento immagine non riuscito.');
    }

    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > 4 * 1024 * 1024) {
        throw new RuntimeException('L\'immagine deve pesare al massimo 4 MB.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new RuntimeException('File immagine non valido.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmpName);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Formato immagine non supportato. Usa JPG, PNG o WebP.');
    }

    $uploadDir = PROJECT_ROOT . '/public/assets/uploads';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        throw new RuntimeException('Cartella upload non disponibile.');
    }

    $filename = 'prop_' . $propertyId . '_' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
    $destination = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new RuntimeException('Impossibile salvare l\'immagine.');
    }

    $originalName = (string) ($file['name'] ?? '');
    return [
        'filename' => $filename,
        'caption' => trim(pathinfo($originalName, PATHINFO_FILENAME)) ?: null,
    ];
}

/* Elimina in sicurezza un file immagine generato dall'upload (solo prop_*). */
function delete_uploaded_image_file(?string $filename): void
{
    $filename = trim((string) $filename);
    if ($filename === '' || !preg_match('/^prop_[0-9]+_[a-f0-9]{16}\.(jpg|png|webp)$/', $filename)) {
        return;
    }
    $uploadsRoot = realpath(PROJECT_ROOT . '/public/assets/uploads');
    $path = realpath(PROJECT_ROOT . '/public/assets/uploads/' . $filename);
    if ($uploadsRoot !== false && $path !== false
        && str_starts_with($path, $uploadsRoot . DIRECTORY_SEPARATOR) && is_file($path)) {
        @unlink($path);
    }
}

/* ---------------------------------------------------------------------------
 * Preferiti: solo studenti registrati (nessuna lista temporanea per gli ospiti).
 * ------------------------------------------------------------------------- */
function current_favorite_ids(): array
{
    $user = current_user();
    if ($user === null || !user_has_group('student')) {
        return [];
    }
    $stmt = db()->prepare('SELECT room_id FROM favorites WHERE user_id = :uid');
    $stmt->execute(['uid' => (int) $user['id']]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

/** Inverte lo stato di preferito e ritorna il nuovo stato (true = ora preferito). */
function toggle_favorite(int $roomId): bool
{
    $user = current_user();
    if ($user === null) {
        throw new RuntimeException('auth_required');
    }
    if (!user_has_group('student')) {
        throw new RuntimeException('student_required');
    }

    $uid = (int) $user['id'];
    $stmt = db()->prepare('SELECT 1 FROM favorites WHERE user_id = :uid AND room_id = :rid LIMIT 1');
    $stmt->execute(['uid' => $uid, 'rid' => $roomId]);

    if ($stmt->fetchColumn()) {
        db()->prepare('DELETE FROM favorites WHERE user_id = :uid AND room_id = :rid')
            ->execute(['uid' => $uid, 'rid' => $roomId]);
        return false;
    }

    db()->prepare('INSERT IGNORE INTO favorites (user_id, room_id) VALUES (:uid, :rid)')
        ->execute(['uid' => $uid, 'rid' => $roomId]);
    return true;
}

/* ---------------------------------------------------------------------------
 * Griglia di card annuncio (stile phase1) usata da home, ricerca, preferiti.
 * $rooms: righe con id, name, type, price_monthly, property_title, address,
 * house_number, neighborhood_name, cover, status, min_distance,
 * rating_avg, rating_count.
 * ------------------------------------------------------------------------- */
function room_cards_html(array $rooms, ?array $favIds = null): string
{
    if ($favIds === null) {
        $favIds = current_favorite_ids();
    }

    if ($rooms === []) {
        return '';
    }

    $html = '';
    foreach ($rooms as $r) {
        $roomId = (int) $r['id'];
        $detailUrl = e(url_for('room.php?id=' . $roomId));
        $address = trim((string) ($r['address'] ?? '') . ' ' . (string) ($r['house_number'] ?? ''));
        $status = (string) ($r['status'] ?? 'available');
        $dist = isset($r['min_distance']) && $r['min_distance'] !== null ? (int) $r['min_distance'] : null;
        $ratingCount = (int) ($r['rating_count'] ?? 0);
        $ratingAvg = $ratingCount > 0 ? (float) $r['rating_avg'] : 0.0;

        $statusChip = $status === 'available'
            ? ''
            : ' ' . room_status_badge($status);
        $ratingText = $ratingCount > 0
            ? '<p class="listing-rating">' . stars_html($ratingAvg) . ' ' . e(number_format($ratingAvg, 1, ',', '')) . '/5 · ' . $ratingCount . ($ratingCount === 1 ? ' recensione' : ' recensioni') . '</p>'
            : '';
        $distText = $dist !== null ? '<p class="muted">~' . e((string) $dist) . ' min dai poli</p>' : '';
        $favMark = in_array($roomId, $favIds, true) ? '<span class="fav-mark" title="Nei preferiti">♥</span>' : '';

        $html .= '<article class="property-card listing-card">'
            . property_image_markup($r['cover'] ?? null, (string) ($r['property_title'] ?? ''))
            . '<div class="property-card-body">'
            . '<h3><a href="' . $detailUrl . '">' . e((string) $r['name']) . '</a> ' . $favMark . '</h3>'
            . '<p class="location-subtitle listing-location">' . e((string) ($r['neighborhood_name'] ?? '')) . ($address !== '' ? ' - ' . e($address) : '') . '</p>'
            . '<p class="listing-kind">' . e(room_type_label((string) $r['type'])) . ' in ' . e((string) $r['property_title']) . $statusChip . '</p>'
            . $ratingText
            . $distText
            . '<div class="listing-card-footer">'
            . '<p class="listing-price">' . e(format_price($r['price_monthly'])) . ' / mese</p>'
            . '<a class="button-small" href="' . $detailUrl . '">Dettaglio</a>'
            . '</div>'
            . '</div>'
            . '</article>';
    }

    return $html;
}

/* Rende un thread di messaggi (booking) in markup essenziale. */
function message_thread_markup(array $messages, int $currentUserId): string
{
    if ($messages === []) {
        return '<p class="muted">Nessun messaggio nel thread.</p>';
    }

    $html = '';
    foreach ($messages as $message) {
        $senderName = trim((string) ($message['sender_name'] ?? ($message['first_name'] ?? '') . ' ' . ($message['last_name'] ?? '')));
        $isCurrent = (int) ($message['sender_id'] ?? 0) === $currentUserId;
        $class = $isCurrent ? 'message-item message-item-current' : 'message-item';

        $html .= '<div class="' . e($class) . '">'
            . '<div class="message-meta"><strong>' . e($senderName === '' ? 'Utente' : $senderName) . '</strong><span>' . e((string) ($message['created_at'] ?? '')) . '</span></div>'
            . '<p class="message-body">' . nl2br(e((string) ($message['body'] ?? ''))) . '</p>'
            . '</div>';
    }

    return $html;
}
