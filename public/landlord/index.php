<?php

declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

require_login();
require_service('landlord.home');

$user = current_user();
$uid = (int) $user['id'];

$props = (new PropertyRepository())->byLandlord($uid);
$bookings = (new BookingRepository())->byLandlord($uid);

// Riquadri riassuntivi: richieste da gestire e stanze già prenotate.
$pendingCount = 0;
$reservedCount = 0;
$cancellationCount = 0;
foreach ($bookings as $b) {
    if ((string) $b['status'] === 'visit_requested') {
        $pendingCount++;
    }
    if ((string) $b['status'] === 'deposit_paid') {
        $reservedCount++;
    }
    if ((string) $b['status'] === 'cancellation_requested') {
        $cancellationCount++;
    }
}

$attention = '';
if ($cancellationCount > 0) {
    $label = $cancellationCount === 1
        ? 'C\'e 1 richiesta di disdetta da gestire.'
        : 'Ci sono ' . $cancellationCount . ' richieste di disdetta da gestire.';
    $attention = '<div class="banner banner--warning" role="status">'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 11 12 4l8 7"/><path d="M6 10v9h12v-9"/></svg>'
        . '<div><strong>Disdette richieste</strong><p>' . e($label) . ' '
        . '<a href="' . e(url_for('landlord/bookings.php')) . '">Apri le richieste &rarr;</a></p></div>'
        . '</div>';
} elseif ($pendingCount > 0) {
    $label = $pendingCount === 1
        ? 'C\'è 1 richiesta di visita in attesa di una tua risposta.'
        : 'Ci sono ' . $pendingCount . ' richieste di visita in attesa di una tua risposta.';
    $attention = '<div class="banner banner--warning" role="status">'
        . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v8Z"/></svg>'
        . '<div><strong>Richieste in attesa</strong><p>' . e($label) . ' '
        . '<a href="' . e(url_for('landlord/bookings.php')) . '">Gestisci le richieste →</a></p></div>'
        . '</div>';
}

$body = $props === []
    ? render_empty_state(
        'Non hai ancora pubblicato annunci',
        'Pubblica il tuo primo immobile: bastano titolo, zona e qualche foto.',
        url_for('landlord/property_form.php'),
        'Pubblica il tuo primo annuncio',
        'home'
    )
    : render_list('frontend/landlord_cards', array_map(static fn ($p) => [
        'mng_url' => e(url_for('landlord/property.php?id=' . $p['id'])),
        'edit_url' => e(url_for('landlord/property_form.php?id=' . $p['id'])),
        'cover' => e(image_src($p['cover'] ?? null)),
        'title' => e($p['title']),
        'neigh' => e($p['neighborhood_name']),
        'rooms' => (string) (int) $p['room_count'],
    ], $props));

$content = render_template('frontend/landlord_home', [
    'user_name' => e($user['full_name']),
    'new_property_url' => e(url_for('landlord/property_form.php')),
    'requests_url' => e(url_for('landlord/bookings.php')),
    'property_count' => (string) count($props),
    'pending_count' => (string) $pendingCount,
    'reserved_count' => (string) ($reservedCount + $cancellationCount),
    'attention_panel' => $attention,
    'page_body' => $body,
]);

render_page_frontend('Area Proprietario', $content, ['body_class' => 'page-dashboard']);
