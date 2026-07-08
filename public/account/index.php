<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();
require_service('account.home');

$user = current_user();
$uid = (int) $user['id'];

$favCount = count((new FavoriteRepository())->roomIds($uid));
$bookingRepo = new BookingRepository();
$bookings = $bookingRepo->byStudent($uid);
$activeHouse = $bookingRepo->activeForStudent($uid);
$bookingCount = count($bookings);

// Riquadri riassuntivi: cosa richiede attenzione adesso.
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
        : 'Hai ' . count($toPay) . ' caparre da versare: le tue richieste sono state approvate.';
    $attention = '<div class="banner banner--info" role="status">'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>'
        . '<div><strong>Caparra da versare</strong><p>' . e($label) . ' '
        . '<a href="' . e(url_for('deposit.php?id=' . (int) $first['id'])) . '">Paga ora la caparra →</a></p></div>'
        . '</div>';
}

$content = render_template('frontend/account_home', [
    'user_name' => e($user['full_name']),
    'user_email' => e($user['email']),
    'search_url' => e(url_for('search.php')),
    'favorites_url' => e(url_for('account/favorites.php')),
    'bookings_url' => e(url_for('account/bookings.php')),
    'my_house_url' => e(url_for('account/my-house.php')),
    'profile_url' => e(url_for('account/profile.php')),
    'fav_count' => (string) $favCount,
    'booking_count' => (string) $bookingCount,
    'pending_count' => (string) $pendingCount,
    'my_house_count' => $activeHouse === null ? '0' : '1',
    'my_house_text' => $activeHouse === null ? 'Nessuna casa attiva' : e((string) $activeHouse['room_name']),
    'attention_panel' => $attention,
]);

render_page_frontend('Area Studente', $content, ['body_class' => 'page-dashboard']);
