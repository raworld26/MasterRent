<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
require_service('account.favorites');

$user = current_user();
$uid = (int) $user['id'];

// Stanze preferite con gli stessi dati usati dalle card di ricerca.
$stmt = db()->prepare(
    'SELECT r.id, r.name, r.type, r.price_monthly, r.expenses_included, r.status, r.created_at,
            p.id AS property_id, p.title AS property_title, p.address, p.house_number,
            n.name AS neighborhood_name, n.code AS neighborhood_code,
            (SELECT pi.filename FROM property_images pi WHERE pi.property_id = p.id
               ORDER BY pi.is_cover DESC, pi.id ASC LIMIT 1) AS cover,
            (SELECT MIN(php.distance_minutes) FROM property_has_poles php WHERE php.property_id = p.id) AS min_distance,
            (SELECT AVG(rv.rating) FROM reviews rv WHERE rv.room_id = r.id AND rv.status = "published") AS rating_avg,
            (SELECT COUNT(*) FROM reviews rv2 WHERE rv2.room_id = r.id AND rv2.status = "published") AS rating_count
     FROM favorites f
     JOIN rooms r ON r.id = f.room_id
     JOIN properties p ON p.id = r.property_id
     JOIN neighborhoods n ON n.id = p.neighborhood_id
     WHERE f.user_id = :uid ORDER BY f.created_at DESC'
);
$stmt->execute(['uid' => $uid]);
$rooms = $stmt->fetchAll();
$favIds = array_map(static fn ($r) => (int) $r['id'], $rooms);

$cards = room_cards_html($rooms, $favIds);
$body = $cards !== ''
    ? '<div class="search-results-grid">' . $cards . '</div>'
    : '<div class="empty-state"><p class="muted">Ancora nessun preferito. Apri una stanza e salvala nei preferiti per ritrovarla qui.</p>'
        . '<a class="button-primary" href="' . e(url_for('search.php')) . '">Esplora le stanze</a></div>';

$content = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">Area Studente</p><h1>I miei preferiti</h1>'
    . '<p class="muted">Le stanze che hai salvato.</p></div>'
    . '<a class="button-secondary" href="' . e(url_for('account/index.php')) . '">Area riservata</a></header>'
    . $body
    . '</section>';

render_page('I miei preferiti', $content, ['body_class' => 'page-dashboard']);
