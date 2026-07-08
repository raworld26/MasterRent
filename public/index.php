<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

$neighborhoods = neighborhoods_all();
$poles = poles_all();
$roomCount = rooms_count_available();

// Prime 6 stanze disponibili come "in evidenza" (più recenti).
$featured = array_slice(rooms_search(['sort' => 'newest']), 0, 6);
$roomCards = room_cards_html($featured);
if ($roomCards === '') {
    $roomCards = '<p class="muted">Nessuna stanza disponibile al momento.</p>';
}

// Conteggio stanze disponibili per quartiere.
$counts = [];
$countStmt = db()->query(
    "SELECT p.neighborhood_id, COUNT(*) AS n
     FROM rooms r JOIN properties p ON p.id = r.property_id
     WHERE r.is_available = 1 AND r.status = 'available'
     GROUP BY p.neighborhood_id"
);
foreach ($countStmt->fetchAll() as $row) {
    $counts[(int) $row['neighborhood_id']] = (int) $row['n'];
}

$neighCards = '';
foreach ($neighborhoods as $n) {
    $count = $counts[(int) $n['id']] ?? 0;
    $label = $count === 1 ? '1 stanza' : $count . ' stanze';
    $neighCards .= '<li><a class="item-title" href="' . e(url_for('search.php?neighborhood_id=' . (int) $n['id'])) . '">'
        . e($n['name']) . '</a><span class="item-meta">' . e($label) . '</span></li>';
}

$poleChips = '';
foreach ($poles as $pole) {
    $poleChips .= '<a class="button-small" href="' . e(url_for('search.php?pole_id=' . (int) $pole['id'])) . '">'
        . e($pole['name']) . '</a> ';
}

$latestReviews = '';
foreach (reviews_latest_published(3) as $rev) {
    $latestReviews .= '<blockquote class="review-quote">'
        . stars_html((float) $rev['rating'])
        . '<p>' . e(excerpt((string) $rev['body'], 150)) . '</p>'
        . '<footer>' . e($rev['author']) . ' — <a href="' . e(url_for('room.php?id=' . (int) $rev['room_id'])) . '">' . e($rev['room_name']) . '</a></footer>'
        . '</blockquote>';
}
if ($latestReviews === '') {
    $latestReviews = '<p class="muted">Ancora nessuna recensione pubblicata.</p>';
}

$content = render_template('home.html', [
    'search_url' => e(url_for('search.php')),
    'account_url' => e(is_authenticated() ? role_home_url() : url_for('login.php')),
    'account_label' => is_authenticated() ? 'Area riservata' : 'Accedi',
    'neighborhood_options' => public_neighborhood_options(0),
    'pole_options' => select_options($poles, '', 'id', 'name', 'Tutti i poli'),
    'type_options' => select_options(room_type_options(), '', 'id', 'name', 'Tutte le tipologie'),
    'room_count' => (string) $roomCount,
    'neighborhood_count' => (string) count($neighborhoods),
    'room_cards' => $roomCards,
    'neighborhood_cards' => $neighCards,
    'pole_chips' => $poleChips,
    'latest_reviews' => $latestReviews,
]);

render_page('Home', $content, ['body_class' => 'page-public']);
