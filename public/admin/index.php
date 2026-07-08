<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();
require_service('backend.dashboard');

$db = db();
$stats = [
    'stat_users' => (string) (int) $db->query('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')->fetchColumn(),
    'stat_properties' => (string) (int) $db->query('SELECT COUNT(*) FROM properties')->fetchColumn(),
    'stat_rooms' => (string) (int) $db->query('SELECT COUNT(*) FROM rooms')->fetchColumn(),
    'stat_neighborhoods' => (string) (int) $db->query('SELECT COUNT(*) FROM neighborhoods')->fetchColumn(),
    'stat_open_requests' => (string) (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'visit_requested'")->fetchColumn(),
    'stat_reserved' => (string) (int) $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'reserved'")->fetchColumn(),
];

// Azioni rapide: solo le sezioni per cui l'utente ha il servizio.
$quick = [
    ['admin.users.index', 'admin/users/index.php', 'Gestisci utenti'],
    ['admin.properties.index', 'admin/properties/index.php', 'Annunci'],
    ['admin.bookings.index', 'admin/bookings/index.php', 'Richieste e caparre'],
    ['admin.reviews.index', 'admin/reviews/index.php', 'Recensioni'],
    ['admin.neighborhoods.index', 'admin/neighborhoods/index.php', 'Quartieri'],
    ['admin.poles.index', 'admin/poles/index.php', 'Poli universitari'],
];

$quickLinks = '';
foreach ($quick as [$service, $path, $label]) {
    if (has_service($service)) {
        $quickLinks .= '<a class="button button-ghost" href="' . e(url_for($path)) . '">' . e($label) . '</a>';
    }
}

$content = render_template('backend/dashboard', $stats + ['quick_links' => $quickLinks]);

render_page_backend('Dashboard', $content, ['body_class' => 'page-admin'], 'backend.dashboard');
