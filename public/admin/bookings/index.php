<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../src/bootstrap.php';

require_login();
require_service('admin.bookings.index');

$bookings = (new BookingRepository())->allForAdmin();

$html = '<div class="admin-toolbar">';
$html .= '<h2>Richieste (' . count($bookings) . ')</h2>';
$html .= '</div>';

$html .= '<table class="admin-table">';
$html .= '<thead><tr>';
$html .= '<th>ID</th><th>Stanza</th><th>Immobile</th><th>Studente</th><th>Proprietario</th><th>Stato</th><th>Caparra</th><th>Data</th><th>Azioni</th>';
$html .= '</tr></thead><tbody>';

foreach ($bookings as $b) {
    $deposit = in_array($b['status'], ['deposit_paid', 'cancellation_requested', 'completed'], true) && $b['deposit_amount'] !== null
        ? format_price($b['deposit_amount'])
        : '—';
    $html .= '<tr>';
    $html .= '<td>' . e($b['id']) . '</td>';
    $html .= '<td>' . e($b['room_name']) . '</td>';
    $html .= '<td>' . e($b['property_title']) . '</td>';
    $html .= '<td>' . e($b['student_name']) . '</td>';
    $html .= '<td>' . e($b['landlord_name']) . '</td>';
    $html .= '<td>' . booking_status_badge($b['status']) . '</td>';
    $html .= '<td>' . e($deposit) . '</td>';
    $html .= '<td>' . e(date('d/m/Y H:i', strtotime($b['created_at']))) . '</td>';
    $html .= '<td>';
    $html .= '<a href="' . e(url_for('booking.php?id=' . $b['id'])) . '" class="btn btn-sm btn-info">Dettaglio</a>';
    $html .= '</td>';
    $html .= '</tr>';
}

if ($bookings === []) {
    $html .= '<tr><td colspan="9" class="text-center">Nessuna richiesta presente.</td></tr>';
}

$html .= '</tbody></table>';

render_page_backend('Richieste', $html, [], 'admin.bookings.index');
