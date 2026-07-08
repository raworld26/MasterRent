<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

$geo = new GeoRepository();
$rooms = new RoomRepository();

$neighborhoods = $geo->allNeighborhoods();
$neighborhoodCount = count($neighborhoods);
$roomCount = $rooms->countAvailable();

// Prime 6 stanze disponibili come "in evidenza".
$featured = array_slice($rooms->search(['sort' => 'newest']), 0, 6);
$featuredHtml = $featured === []
    ? '<p class="muted">Nessuna stanza disponibile al momento.</p>'
    : render_room_grid($featured);

// Chip "Vicino ai poli didattici": ricerca preimpostata sul polo.
$poleChips = '';
foreach ($geo->allPoles() as $pole) {
    $poleChips .= '<a class="pole-chip" href="' . e(url_for('search.php?pole_id=' . (int) $pole['id'])) . '">'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 3 10 5-10 5L2 8l10-5Z"/><path d="M6 10v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5"/></svg>'
        . e($pole['name'])
        . '</a>';
}

// Ultime recensioni pubblicate (citazioni editoriali).
$latestReviews = '';
foreach ((new ReviewRepository())->latestPublished(3) as $rev) {
    $latestReviews .= '<article class="review-quote">'
        . stars_html((float) $rev['rating'])
        . '<blockquote>' . e(excerpt((string) $rev['body'], 150)) . '</blockquote>'
        . '<footer><span>' . e($rev['author']) . '</span>'
        . '<a href="' . e(url_for('room.php?id=' . (int) $rev['room_id'])) . '">' . e($rev['room_name']) . '</a></footer>'
        . '</article>';
}

// Card quartieri con numero di stanze disponibili.
$countsStmt = db()->query(
    "SELECT p.neighborhood_id, COUNT(*) AS n
     FROM rooms r JOIN properties p ON p.id = r.property_id
     WHERE r.is_available = 1 AND r.status = 'available'
     GROUP BY p.neighborhood_id"
);
$counts = [];
foreach ($countsStmt->fetchAll() as $row) {
    $counts[(int) $row['neighborhood_id']] = (int) $row['n'];
}

$neighCards = '';
foreach ($neighborhoods as $n) {
    $count = $counts[(int) $n['id']] ?? 0;
    $label = $count === 1 ? '1 stanza' : $count . ' stanze';
    $neighCards .= '<a class="neigh-card" href="' . e(url_for('search.php?neighborhood_id=' . (int) $n['id'])) . '">'
        . '<span>' . e($n['name']) . '<small>' . e($label) . '</small></span>'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>'
        . '</a>';
}

$content = render_template('frontend/home', [
    'search_url' => e(url_for('search.php')),
    'register_url' => e(url_for('register.php')),
    'neighborhood_options' => select_options($geo->allNeighborhoods(), '', 'id', 'name', 'Tutti i quartieri'),
    'pole_options' => select_options($geo->allPoles(), '', 'id', 'name', 'Tutti i poli'),
    'neighborhood_count' => (string) $neighborhoodCount,
    'room_count' => (string) $roomCount,
    'featured_rooms' => $featuredHtml,
    'neighborhood_cards' => $neighCards,
    'pole_chips' => $poleChips,
    'latest_reviews' => $latestReviews,
]);

render_page_frontend('Stanze per studenti a L\'Aquila', $content, [
    'body_class' => 'page-home',
    'meta_description' => 'MasterRent: stanze e posti letto per studenti UNIVAQ a L\'Aquila. '
        . 'Cerca per quartiere e polo didattico, prenota la visita e blocca la stanza con caparra simulata.',
]);
