<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
require_service('backend.dashboard');

$db = db();
$stats = [
    'Utenti' => (int) $db->query('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL')->fetchColumn(),
    'Annunci' => (int) $db->query('SELECT COUNT(*) FROM properties')->fetchColumn(),
    'Stanze' => (int) $db->query('SELECT COUNT(*) FROM rooms')->fetchColumn(),
    'Quartieri' => (int) $db->query('SELECT COUNT(*) FROM neighborhoods')->fetchColumn(),
    'Richieste aperte' => (int) $db->query("SELECT COUNT(*) FROM bookings WHERE status = 'visit_requested'")->fetchColumn(),
    'Stanze prenotate' => (int) $db->query("SELECT COUNT(*) FROM rooms WHERE status = 'reserved'")->fetchColumn(),
];

$statsHtml = '<section class="account-summary">';
foreach ($stats as $label => $value) {
    $statsHtml .= '<div><span class="summary-label">' . e($label) . '</span><strong>' . $value . '</strong></div>';
}
$statsHtml .= '</section>';

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
        $quickLinks .= '<a class="button-secondary" href="' . e(url_for($path)) . '">' . e($label) . '</a> ';
    }
}

$content = '<header class="dashboard-header"><div><p class="eyebrow">Area Riservata</p><h1>Dashboard</h1></div></header>'
    . $statsHtml
    . '<section class="panel"><div class="panel-heading"><h2>Azioni rapide</h2></div><p>' . $quickLinks . '</p></section>';

render_admin_page('Dashboard', $content, 'backend.dashboard');
