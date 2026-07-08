<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
require_service('account.home');

$user = current_user();
$uid = (int) $user['id'];

$favCount = count(current_favorite_ids());
$bookings = bookings_by_student($uid);
$activeHouse = booking_active_for_student($uid);
$bookingCount = count($bookings);

$pendingCount = 0;
$toPay = [];
foreach ($bookings as $b) {
    if ((string) $b['status'] === 'visit_requested') {
        $pendingCount++;
    }
    if ((string) $b['status'] === 'approved_pending_deposit') {
        $toPay[] = $b;
    }
}

$attention = '';
if ($toPay !== []) {
    $first = $toPay[0];
    $label = count($toPay) === 1
        ? 'Hai 1 caparra da versare: la richiesta per "' . $first['room_name'] . '" è stata approvata.'
        : 'Hai ' . count($toPay) . ' caparre da versare: alcune tue richieste sono state approvate.';
    $attention = '<div class="alert alert-success" role="status">' . e($label)
        . ' <a href="' . e(url_for('deposit.php?id=' . (int) $first['id'])) . '">Paga ora la caparra</a>.</div>';
}

$content = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">Area Studente</p><h1>' . e($user['full_name']) . '</h1>'
    . '<p class="muted">' . e($user['email']) . '</p></div>'
    . '<a class="button-secondary" href="' . e(url_for('account/profile.php')) . '">Profilo</a></header>'
    . $attention
    . '<section class="account-summary">'
    . '<div><span class="summary-label">Preferiti</span><strong>' . $favCount . '</strong></div>'
    . '<div><span class="summary-label">Richieste inviate</span><strong>' . $bookingCount . '</strong></div>'
    . '<div><span class="summary-label">In attesa</span><strong>' . $pendingCount . '</strong></div>'
    . '<div><span class="summary-label">La mia casa</span><strong>' . ($activeHouse === null ? '0' : '1') . '</strong></div>'
    . '</section>'
    . '<section class="panel"><div class="panel-heading"><h2>Scorciatoie</h2></div>'
    . '<ul class="item-list">'
    . '<li><a class="item-title" href="' . e(url_for('search.php')) . '">Cerca stanze</a></li>'
    . '<li><a class="item-title" href="' . e(url_for('account/my-house.php')) . '">La mia casa</a></li>'
    . '<li><a class="item-title" href="' . e(url_for('account/bookings.php')) . '">Le mie richieste</a></li>'
    . '<li><a class="item-title" href="' . e(url_for('account/favorites.php')) . '">I miei preferiti</a></li>'
    . '<li><a class="item-title" href="' . e(url_for('account/profile.php')) . '">Il mio profilo</a></li>'
    . '</ul></section>'
    . '</section>';

render_page('Area Studente', $content, ['body_class' => 'page-dashboard']);
