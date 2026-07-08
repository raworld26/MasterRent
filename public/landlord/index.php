<?php

declare(strict_types=1);

require_once __DIR__ . '/../../includes/bootstrap.php';

require_login();
require_service('landlord.home');

$user = current_user();
$uid = (int) $user['id'];

$props = properties_by_landlord($uid);
$bookings = bookings_by_landlord($uid);

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
    $attention = '<div class="alert alert-success" role="status">' . e($label)
        . ' <a href="' . e(url_for('landlord/bookings.php')) . '">Apri le richieste</a>.</div>';
} elseif ($pendingCount > 0) {
    $label = $pendingCount === 1
        ? 'C\'è 1 richiesta di visita in attesa di una tua risposta.'
        : 'Ci sono ' . $pendingCount . ' richieste di visita in attesa di una tua risposta.';
    $attention = '<div class="alert alert-success" role="status">' . e($label)
        . ' <a href="' . e(url_for('landlord/bookings.php')) . '">Gestisci le richieste</a>.</div>';
}

if ($props === []) {
    $body = '<div class="empty-state"><p class="muted">Non hai ancora pubblicato annunci. Pubblica il tuo primo immobile: bastano titolo, zona e qualche foto.</p>'
        . '<a class="button-primary" href="' . e(url_for('landlord/property_form.php')) . '">Pubblica il tuo primo annuncio</a></div>';
} else {
    $body = '<ul class="item-list">';
    foreach ($props as $p) {
        $rc = (int) $p['room_count'];
        $body .= '<li>'
            . '<span class="item-title"><a href="' . e(url_for('landlord/property.php?id=' . $p['id'])) . '">' . e($p['title']) . '</a></span>'
            . '<span class="item-meta">' . e($p['neighborhood_name']) . ' · ' . $rc . ($rc === 1 ? ' stanza' : ' stanze') . '</span>'
            . '<p><a class="button-small" href="' . e(url_for('landlord/property.php?id=' . $p['id'])) . '">Gestisci</a> '
            . '<a class="button-small button-secondary" href="' . e(url_for('landlord/property_form.php?id=' . $p['id'])) . '">Modifica</a></p>'
            . '</li>';
    }
    $body .= '</ul>';
}

$content = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">Area Proprietario</p><h1>' . e($user['full_name']) . '</h1></div>'
    . '<a class="button-primary" href="' . e(url_for('landlord/property_form.php')) . '">Nuovo annuncio</a></header>'
    . $attention
    . '<section class="account-summary">'
    . '<div><span class="summary-label">Annunci</span><strong>' . count($props) . '</strong></div>'
    . '<div><span class="summary-label">Richieste in attesa</span><strong>' . $pendingCount . '</strong></div>'
    . '<div><span class="summary-label">Stanze prenotate</span><strong>' . ($reservedCount + $cancellationCount) . '</strong></div>'
    . '</section>'
    . '<section class="panel"><div class="panel-heading"><h2>I miei annunci</h2>'
    . '<a class="button-secondary" href="' . e(url_for('landlord/bookings.php')) . '">Richieste ricevute</a></div>'
    . $body . '</section>'
    . '</section>';

render_page('Area Proprietario', $content, ['body_class' => 'page-dashboard']);
