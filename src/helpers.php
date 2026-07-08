<?php

declare(strict_types=1);

/*
 * Helper di presentazione del dominio (formattazione, etichette, badge).
 * Restituiscono stringhe già pronte (e già "escaped" dove necessario).
 */
require_once __DIR__ . '/ZoneEstimates.php';

/* Foto locali delle case (in public/assets/uploads/case), usate come ripiego
   quando una stanza non ha ancora una copertina propria. */
function default_aquila_photo(int $seed = 0): string
{
    $photos = [
        'contrada_santelia_4_zona_giorno.avif',
        'corso_vittorio_emanuele_ii_cucina.avif',
        'via_delle_nocelle_85_camera.avif',
        'via_gennaro_manna_33_living.avif',
        'via_goriano_valle_47_living.avif',
        'via_nicola_lombardi_12_zona_giorno.avif',
        'via_uruguay_6_open_space.avif',
    ];

    return UPLOADS_URL . '/case/' . $photos[abs($seed) % count($photos)];
}

/*
 * Galleria mosaico stile Airbnb: prima foto grande + fino a 4 secondarie.
 * Tutte le foto (con didascalia) sono serializzate in data-images per il
 * lightbox JavaScript. $images: righe con 'filename' e 'caption'.
 */
function gallery_mosaic_html(array $images, string $alt): string
{
    if ($images === []) {
        return '<div class="gallery gallery-empty"><img src="' . e(image_src(null))
            . '" alt="" class="gallery-empty-img"></div>';
    }

    $payload = array_map(static fn ($img) => [
        'src' => image_src($img['filename']),
        'caption' => (string) ($img['caption'] ?? ''),
    ], $images);
    $json = e(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

    $count = count($images);
    $tiles = array_slice($images, 0, 5);
    $klass = 'gallery gallery-mosaic gallery-n' . min($count, 5);

    $html = '<div class="' . $klass . '" data-gallery data-images="' . $json . '">';
    foreach ($tiles as $i => $img) {
        $src = e(image_src($img['filename']));
        $cap = e((string) ($img['caption'] ?? $alt));
        $loading = $i === 0 ? 'eager' : 'lazy';
        $html .= '<button class="gallery-item" type="button" data-gallery-item data-index="' . $i . '"'
            . ' aria-label="Apri foto ' . ($i + 1) . ' di ' . $count . '">'
            . '<img src="' . $src . '" alt="' . $cap . '" loading="' . $loading . '" decoding="async">'
            . '</button>';
    }
    $html .= '<button class="gallery-more" type="button" data-gallery-open aria-label="Mostra tutte le foto">'
        . '<svg viewBox="0 0 24 24" aria-hidden="true" width="18" height="18"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>'
        . 'Mostra tutte le foto (' . $count . ')</button>';
    $html .= '</div>';

    return $html;
}

/* URL di un'immagine: passa attraverso gli URL assoluti (per compatibilità),
   altrimenti la cerca nella cartella uploads. */
function image_src(?string $filename, ?int $fallbackSeed = null): string
{
    $filename = trim((string) $filename);
    if ($filename === '') {
        return $fallbackSeed === null ? asset_url('img/placeholder.svg') : default_aquila_photo($fallbackSeed);
    }
    if (preg_match('#^https?://#i', $filename)) {
        return $filename;
    }

    $resolved = resolve_upload_filename($filename);
    if ($resolved !== null) {
        return UPLOADS_URL . '/' . $resolved;
    }

    return $fallbackSeed === null ? asset_url('img/placeholder.svg') : default_aquila_photo($fallbackSeed);
}

function resolve_upload_filename(string $filename): ?string
{
    $relative = ltrim(str_replace('\\', '/', trim($filename)), '/');
    if ($relative === '') {
        return null;
    }

    if (is_file(UPLOADS_DIR . '/' . $relative)) {
        return $relative;
    }

    $legacyMap = [
        'case/giovanni_divincenzo1.jpg' => 'case/contrada_santelia_4_zona_giorno.avif',
        'case/giovanni_divincenzo3.jpg' => 'case/contrada_santelia_4_camera.avif',
        'case/santa_barbara1.jpg' => 'case/via_delle_nocelle_85_camera.avif',
        'case/santabarbara_2.jpg' => 'case/via_delle_nocelle_85_cucina.avif',
        'case/viafrancescotosti_1.jpg' => 'case/via_goriano_valle_47_living.avif',
        'case/viafrancescotosti_2.jpg' => 'case/via_goriano_valle_47_camera_doppia.avif',
        'case/viaitalia_1.jpg' => 'case/via_gennaro_manna_33_living.avif',
        'case/viaitalia_2.jpg' => 'case/via_gennaro_manna_33_pranzo.avif',
        'case/viaitalia_3.jpg' => 'case/via_gennaro_manna_33_bagno.avif',
        'case/vialegransasso_1.jpg' => 'case/via_nicola_lombardi_12_zona_giorno.avif',
        'case/vialegransasso_2.jpg' => 'case/via_nicola_lombardi_12_camera.avif',
        'case/vialegransasso_3.jpg' => 'case/via_nicola_lombardi_12_bagno.avif',
        'case/vialenapoli_1.jpg' => 'case/via_uruguay_6_open_space.avif',
        'case/vialenapoli_2.jpg' => 'case/via_uruguay_6_zona_notte.avif',
        'case/viale_napoli3.jpg' => 'case/via_uruguay_6_bagno.avif',
        'case/viale_corradoVI__1.jpg' => 'case/corso_vittorio_emanuele_ii_cucina.avif',
        'case/viale_corradoVI__2.jpg' => 'case/corso_vittorio_emanuele_ii_camera.avif',
        'case/viale_nizza1.jpg' => 'case/contrada_santelia_4_zona_giorno.avif',
        'case/viale_nizza2.jpg' => 'case/contrada_santelia_4_camera.avif',
        'case/viale_nizza3.jpg' => 'case/contrada_santelia_4_bagno.avif',
        'case/viaroma_1.jpg' => 'case/via_goriano_valle_47_pranzo.avif',
        'case/viaroma_2.jpg' => 'case/via_goriano_valle_47_living.avif',
        'case/via_roma3.jpg' => 'case/via_goriano_valle_47_bagno.avif',
    ];

    $mapped = $legacyMap[$relative] ?? null;
    if ($mapped !== null && is_file(UPLOADS_DIR . '/' . $mapped)) {
        return $mapped;
    }

    $pathInfo = pathinfo($relative);
    $directory = isset($pathInfo['dirname']) && $pathInfo['dirname'] !== '.' ? $pathInfo['dirname'] . '/' : '';
    $basename = $pathInfo['filename'] ?? '';
    foreach (['avif', 'webp', 'jpg', 'jpeg', 'png'] as $extension) {
        $candidate = $directory . $basename . '.' . $extension;
        if (is_file(UPLOADS_DIR . '/' . $candidate)) {
            return $candidate;
        }
    }

    return null;
}

function format_price($value): string
{
    return number_format((float) $value, 0, ',', '.') . ' €';
}

/*
 * Coordinate approssimate dei quartieri dell'Aquila, per la mappa della zona
 * (OpenStreetMap, nessuna API key). Ripiego: centro dell'Aquila.
 */
function neighborhood_coords(?string $code): array
{
    $map = [
        'centro_storico' => [42.3506, 13.3995],
        'pettino'        => [42.3712, 13.3785],
        'coppito'        => [42.3690, 13.3500],
        'sant_antonio'   => [42.3560, 13.3820],
        'piazza_armi'    => [42.3455, 13.4030],
        'torrione'       => [42.3520, 13.4080],
        'pile'           => [42.3560, 13.3700],
    ];

    return $map[(string) $code] ?? [42.3498, 13.3995];
}

/* URL di embed OpenStreetMap (iframe) centrato su lat/lng con marker. */
function osm_embed_url(float $lat, float $lng, float $span = 0.012): string
{
    $bbox = ($lng - $span) . ',' . ($lat - $span * .7) . ',' . ($lng + $span) . ',' . ($lat + $span * .7);

    return 'https://www.openstreetmap.org/export/embed.html?bbox=' . rawurlencode($bbox)
        . '&layer=mapnik&marker=' . $lat . ',' . $lng;
}

/* Link "apri in OpenStreetMap" per la posizione della zona. */
function osm_link_url(float $lat, float $lng): string
{
    return 'https://www.openstreetmap.org/?mlat=' . $lat . '&mlon=' . $lng . '#map=16/' . $lat . '/' . $lng;
}

/* URL di embed mappa per un indirizzo specifico (usato nelle stanze) */
function address_map_embed_url(string $address): string
{
    $query = rawurlencode($address . ', L\'Aquila, Italia');
    return 'https://maps.google.com/maps?q=' . $query . '&t=&z=16&ie=UTF8&iwloc=&output=embed';
}

/* Link mappa per un indirizzo specifico */
function address_map_link_url(string $address): string
{
    $query = rawurlencode($address . ', L\'Aquila, Italia');
    return 'https://maps.google.com/maps?q=' . $query;
}

/* Opzioni <datalist> per l'autocomplete dei campi ricerca (quartieri + poli). */
function neighborhoods_datalist_html(): string
{
    static $html = null;
    if ($html !== null) {
        return $html;
    }
    $geo = new GeoRepository();
    $html = '';
    foreach ($geo->allNeighborhoods() as $n) {
        $html .= '<option value="' . e($n['name']) . '"></option>';
    }
    foreach ($geo->allPoles() as $p) {
        $html .= '<option value="' . e($p['name']) . '"></option>';
    }

    return $html;
}

function room_type_label(string $type): string
{
    return [
        'single' => 'Singola',
        'double' => 'Doppia',
        'bed_space' => 'Posto letto',
        'entire_apartment' => 'Intero appartamento',
    ][$type] ?? ucfirst($type);
}

function transit_label(string $type): string
{
    return ['foot' => 'a piedi', 'bus' => 'in bus', 'car' => 'in auto'][$type] ?? $type;
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

/* Alias storico: delega al componente render_badge_booking_status(). */
function booking_status_badge(string $status): string
{
    return render_badge_booking_status($status);
}

/* Etichetta e badge per lo stato di prenotabilità di una stanza. */
function room_status_label(string $status): string
{
    return [
        'available' => 'Disponibile',
        'reserved' => 'Prenotata',
        'unavailable' => 'Non disponibile',
    ][$status] ?? $status;
}

/* Alias storico: delega al componente render_badge_room_status(). */
function room_status_badge(string $status): string
{
    return render_badge_room_status($status);
}

/* La caparra è sempre pari a UNA mensilità d'affitto. */
function deposit_amount_for(float $monthlyPrice): float
{
    return round($monthlyPrice, 2);
}

/* Stelline di valutazione (1-5) come HTML. */
function stars_html(float $rating): string
{
    $full = (int) round($rating);
    $html = '<span class="stars" aria-label="' . e(number_format($rating, 1)) . ' su 5">';
    for ($i = 1; $i <= 5; $i++) {
        $html .= '<span class="star' . ($i <= $full ? ' star-on' : '') . '">★</span>';
    }
    return $html . '</span>';
}

function excerpt(?string $text, int $length = 140): string
{
    $text = trim((string) $text);
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return mb_substr($text, 0, $length - 1) . '…';
}

/*
 * Costruisce una lista di <option> per una select, con eventuale opzione
 * segnaposto e selezione corrente.
 */
function select_options(array $items, $selected, string $valueKey = 'id', string $labelKey = 'name', string $placeholder = ''): string
{
    $html = $placeholder !== '' ? '<option value="">' . e($placeholder) . '</option>' : '';
    foreach ($items as $item) {
        $value = (string) $item[$valueKey];
        $sel = ((string) $selected === $value && $selected !== '') ? ' selected' : '';
        $html .= '<option value="' . e($value) . '"' . $sel . '>' . e($item[$labelKey]) . '</option>';
    }
    return $html;
}

/* Etichette delle tipologie di stanza per le select. */
function room_type_options(): array
{
    return [
        ['id' => 'single', 'name' => 'Singola'],
        ['id' => 'double', 'name' => 'Doppia'],
        ['id' => 'bed_space', 'name' => 'Posto letto'],
        ['id' => 'entire_apartment', 'name' => 'Intero appartamento'],
    ];
}

/*
 * Trasforma i risultati di ricerca stanze in righe pronte per il ciclo
 * <[foreach]> del template room_list.html. I preferiti sono calcolati una
 * sola volta per evitare query ripetute.
 */
function room_card_rows(array $rooms, ?array $favIds = null): array
{
    if ($favIds === null) {
        $favIds = current_favorite_ids();
    }

    $rows = [];
    foreach ($rooms as $r) {
        $dist = isset($r['min_distance']) && $r['min_distance'] !== null ? (int) $r['min_distance'] : null;
        $roomId = (int) $r['id'];
        $ratingCount = (int) ($r['rating_count'] ?? 0);
        $ratingAvg = $ratingCount > 0 ? (float) $r['rating_avg'] : 0.0;
        $isNew = isset($r['created_at']) && strtotime((string) $r['created_at']) >= strtotime('-30 days');
        $sqm = 14 + (($roomId * 2) % 7);
        $status = (string) ($r['status'] ?? 'available');
        $available = $status === 'available';

        // Indirizzo leggibile: via (se nota) + titolo immobile come ripiego.
        $address = trim((string) ($r['address'] ?? ''));
        $houseNumber = trim((string) ($r['house_number'] ?? ''));
        if ($houseNumber !== '') {
            $address .= ' ' . $houseNumber;
        }

        $rows[] = [
            'card_id' => (string) $r['id'],
            'card_url' => e(url_for('room.php?id=' . $r['id'])),
            'card_cover' => e(image_src($r['cover'] ?? null, $roomId)),
            'card_fallback_cover' => e(default_aquila_photo($roomId + 1)),
            'card_title' => e(room_type_label((string) $r['type'])),
            'card_property' => e($r['property_title']),
            'card_addr' => e($address),
            'card_neigh' => e($r['neighborhood_name']),
            'card_distance' => $dist !== null ? ('~' . $dist . ' min dai poli') : 'distanza n.d.',
            'card_distance_num' => (string) ($dist ?? 999),
            'card_price' => e(format_price($r['price_monthly'])),
            'card_price_num' => (string) (int) $r['price_monthly'],
            'card_created_ts' => (string) strtotime((string) ($r['created_at'] ?? '')),
            'card_type' => e(room_type_label((string) $r['type'])),
            'card_type_raw' => e((string) $r['type']),
            'card_sqm' => (string) $sqm,
            'card_sqm_label' => e($sqm . ' m²'),
            'card_expenses_tag' => !empty($r['expenses_included']) ? '<span>Spese incluse</span>' : '',
            'card_status_chip' => $available
                ? '<span class="card-badge card-badge-ok">Disponibile</span>'
                : '<span class="card-badge">' . e(room_status_label($status)) . '</span>',
            'card_fav_class' => in_array((int) $r['id'], $favIds, true) ? 'is-fav' : '',
            'card_fav_pressed' => in_array((int) $r['id'], $favIds, true) ? 'true' : 'false',
            'card_search' => e(mb_strtolower($r['name'] . ' ' . $r['property_title'] . ' ' . ($r['address'] ?? '') . ' ' . $r['neighborhood_name'])),
            'card_rating_num' => (string) ($ratingCount > 0 ? round($ratingAvg, 1) : 0),
            'card_reviews' => $ratingCount > 0 ? e('· ' . $ratingCount . ($ratingCount === 1 ? ' recensione' : ' recensioni')) : '',
            'card_rating_pill' => $ratingCount > 0
                ? '<span class="card-rating-pill" aria-label="Valutazione ' . e(number_format($ratingAvg, 1, ',', '')) . ' su 5">'
                    . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3l2.6 5.6 6 .8-4.4 4.2 1.1 6L12 17.8 6.7 19.6l1.1-6L3.4 9.4l6-.8L12 3z"/></svg>'
                    . e(number_format($ratingAvg, 1, ',', '')) . '</span>'
                : '',
            'card_new_badge' => $isNew ? '<span class="card-badge card-badge-new">Novità</span>' : '',
        ];
    }

    return $rows;
}

/* ---------------------------------------------------------------------------
 * Preferiti: solo utenti registrati con ruolo studente. Gli ospiti non hanno
 * una lista temporanea in sessione, cosi il salvataggio resta vincolato al DB.
 * ------------------------------------------------------------------------- */
function session_favorite_ids(): array
{
    return [];
}

function current_favorite_ids(): array
{
    $user = current_user();
    if ($user !== null && user_has_group('student')) {
        return (new FavoriteRepository())->roomIds((int) $user['id']);
    }
    return [];
}

function is_favorite(int $roomId): bool
{
    return in_array($roomId, current_favorite_ids(), true);
}

/** Inverte lo stato di preferito e ritorna il nuovo stato. */
function toggle_favorite(int $roomId): bool
{
    $user = current_user();

    if ($user === null) {
        throw new RuntimeException('auth_required');
    }

    if (!user_has_group('student')) {
        throw new RuntimeException('student_required');
    }

    $repo = new FavoriteRepository();
    if ($repo->isFavorite((int) $user['id'], $roomId)) {
        $repo->remove((int) $user['id'], $roomId);
        return false;
    }

    $repo->add((int) $user['id'], $roomId);
    return true;
}

/** Compatibilita con vecchie sessioni: elimina eventuali preferiti guest. */
function sync_session_favorites_to_db(int $userId): void
{
    unset($_SESSION['fav_rooms']);
}
