<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

require_login();

$id = (int) query_str('id');
$booking = $id > 0 ? booking_find($id) : null;

if ($booking === null) {
    http_response_code(404);
    render_page('Richiesta non trovata',
        '<section class="panel empty-state"><h1>Richiesta non trovata</h1></section>', ['body_class' => 'page-dashboard']);
    exit;
}

$user = current_user();
$uid = (int) $user['id'];
$isStudentOwner = (int) $booking['student_id'] === $uid;
$isLandlordOwner = (int) $booking['landlord_id'] === $uid;
$isAdmin = user_has_group('admin');

if (!$isStudentOwner && !$isLandlordOwner && !$isAdmin) {
    http_response_code(403);
    render_page('Accesso negato',
        '<section class="panel empty-state"><h1>Accesso negato</h1></section>', ['body_class' => 'page-dashboard']);
    exit;
}

$bookingUrl = url_for('booking.php?id=' . $id);

/* ---------------- POST ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = post_str('action');

    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'booking_action')) {
        set_flash('danger', 'Sessione scaduta. Riprova.');
        redirect($bookingUrl);
    }

    $status = (string) $booking['status'];
    $messageClosedStates = ['rejected', 'withdrawn', 'completed'];
    $withdrawClosedStates = ['rejected', 'deposit_paid', 'cancellation_requested', 'completed', 'withdrawn'];

    if ($action === 'message' && ($isStudentOwner || $isLandlordOwner) && !in_array($status, $messageClosedStates, true)) {
        $bodyMsg = post_str('body');
        if (mb_strlen($bodyMsg) < 2) {
            set_flash('danger', 'Il messaggio è troppo corto.');
        } else {
            message_create($id, $uid, $bodyMsg);
            set_flash('success', 'Messaggio inviato.');
        }
        redirect($bookingUrl);
    }

    if ($action === 'approve' && ($isLandlordOwner || $isAdmin) && $status === 'visit_requested') {
        booking_approve($id, $uid);
        set_flash('success', 'Richiesta approvata: lo studente può ora versare la caparra.');
        redirect($bookingUrl);
    }

    if ($action === 'reject' && ($isLandlordOwner || $isAdmin) && in_array($status, ['visit_requested', 'approved_pending_deposit'], true)) {
        booking_reject($id, post_str('note') ?: null, $uid);
        set_flash('info', 'Richiesta rifiutata.');
        redirect($bookingUrl);
    }

    if ($action === 'withdraw' && $isStudentOwner && !in_array($status, $withdrawClosedStates, true)) {
        booking_update_status($id, 'withdrawn', 'Richiesta ritirata dallo studente', $uid);
        set_flash('info', 'Richiesta ritirata.');
        redirect($bookingUrl);
    }

    if ($action === 'refund' && ($isLandlordOwner || $isAdmin) && $status === 'cancellation_requested') {
        $refundAmount = (float) post_str('refund_amount');
        $note = post_str('note');
        db()->prepare("UPDATE bookings SET status = 'withdrawn', refund_amount = :amt WHERE id = :id")
            ->execute(['amt' => $refundAmount, 'id' => $id]);
        $historyNote = "Caparra restituita: " . number_format($refundAmount, 2, ',', '.') . " €.";
        if ($note !== '') {
            $historyNote .= " Note: " . $note;
        }
        booking_add_history($id, 'withdrawn', $historyNote, $uid);
        set_flash('success', 'Rimborso processato e disdetta completata.');
        redirect($bookingUrl);
    }

    redirect($bookingUrl);
}

/* ---------------- Vista ---------------- */
messages_mark_read_for_viewer($id, $uid);

$status = (string) $booking['status'];
$depositAmount = deposit_amount_for((float) $booking['price_monthly']);
$messages = messages_for_booking($id);
$history = booking_history($id);

$backUrl = $isLandlordOwner ? url_for('landlord/bookings.php')
    : ($isAdmin && !$isStudentOwner ? url_for('admin/bookings/index.php') : url_for('account/bookings.php'));
$backLabel = $isLandlordOwner ? 'Richieste ricevute' : ($isAdmin && !$isStudentOwner ? 'Tutte le richieste' : 'Le mie richieste');

/* Azioni disponibili in base a ruolo e stato. */
$actions = '';
if (($isLandlordOwner || $isAdmin) && $status === 'visit_requested') {
    $actions .= '<form method="POST" action="' . e($bookingUrl) . '" class="inline-form">'
        . csrf_field('booking_action') . '<input type="hidden" name="action" value="approve">'
        . '<button type="submit" class="button-primary">Approva richiesta</button></form> ';
    $actions .= '<form method="POST" action="' . e($bookingUrl) . '" class="inline-form">'
        . csrf_field('booking_action') . '<input type="hidden" name="action" value="reject">'
        . '<button type="submit" class="button-danger">Rifiuta</button></form>';
} elseif (($isLandlordOwner || $isAdmin) && $status === 'approved_pending_deposit') {
    $actions .= '<form method="POST" action="' . e($bookingUrl) . '" class="inline-form">'
        . csrf_field('booking_action') . '<input type="hidden" name="action" value="reject">'
        . '<button type="submit" class="button-danger">Rifiuta</button></form>';
} elseif (($isLandlordOwner || $isAdmin) && $status === 'cancellation_requested') {
    $actions .= '<form method="POST" action="' . e($bookingUrl) . '" class="inline-form" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">'
        . csrf_field('booking_action') . '<input type="hidden" name="action" value="refund">'
        . '<input type="number" name="refund_amount" step="0.01" min="0" max="' . (float)$booking['deposit_amount'] . '" placeholder="Importo rimborso (€)" required style="width:auto;">'
        . '<input type="text" name="note" placeholder="Note (opzionale)" style="width:auto;">'
        . '<button type="submit" class="button-primary">Processa Disdetta e Rimborso</button></form>';
}
if ($isStudentOwner && $status === 'approved_pending_deposit') {
    $actions .= '<a class="button-primary" href="' . e(url_for('deposit.php?id=' . $id)) . '">Paga la caparra (' . e(format_price($depositAmount)) . ')</a> ';
}
if ($isStudentOwner && !in_array($status, ['rejected', 'deposit_paid', 'cancellation_requested', 'completed', 'withdrawn'], true)) {
    $actions .= '<form method="POST" action="' . e($bookingUrl) . '" class="inline-form" onsubmit="return confirm(\'Ritirare la richiesta?\')">'
        . csrf_field('booking_action') . '<input type="hidden" name="action" value="withdraw">'
        . '<button type="submit" class="button-secondary">Ritira richiesta</button></form>';
}
$actionsBox = $actions !== '' ? '<div class="actions-group">' . $actions . '</div>' : '';

/* Ricevuta caparra. */
$receipt = '';
if (in_array($status, ['deposit_paid', 'cancellation_requested', 'completed', 'withdrawn'], true) && $booking['deposit_amount'] > 0) {
    $receipt = '<div class="alert alert-success"><strong>Caparra versata (' . e(format_price((float)$booking['deposit_amount'])) . ').</strong> ';
    if (!empty($booking['deposit_paid_at'])) {
        $receipt .= 'Data: ' . e(date('d/m/Y H:i', strtotime((string) $booking['deposit_paid_at']))) . '. ';
    }
    if (($booking['deposit_reference'] ?? '') !== '') {
        $receipt .= 'Riferimento: <code>' . e((string) $booking['deposit_reference']) . '</code>.';
    }
    if (isset($booking['refund_amount'])) {
        $receipt .= '<br><strong>Caparra restituita:</strong> ' . e(format_price((float)$booking['refund_amount']));
    }
    $receipt .= '</div>';
}

/* Form messaggio (se la richiesta è ancora aperta). */
$messageForm = '';
if (($isStudentOwner || $isLandlordOwner) && !in_array($status, ['withdrawn', 'rejected', 'completed'], true)) {
    $messageForm = '<form method="POST" action="' . e($bookingUrl) . '" class="form-standard">'
        . csrf_field('booking_action') . '<input type="hidden" name="action" value="message">'
        . '<div class="form-group"><label for="body">Nuovo messaggio</label><textarea id="body" name="body" rows="3" required></textarea></div>'
        . '<button type="submit">Invia messaggio</button></form>';
} else {
    $messageForm = '<p class="muted">Questa richiesta non accetta nuovi messaggi.</p>';
}

/* Storico stati. */
$historyHtml = '<ul class="item-list">';
foreach ($history as $h) {
    $historyHtml .= '<li><span class="item-title">' . booking_status_badge((string) $h['status']) . '</span>'
        . '<span class="item-meta">' . e(date('d/m/Y H:i', strtotime((string) $h['created_at']))) . '</span>'
        . (($h['note'] ?? '') !== '' ? '<p>' . e((string) $h['note']) . '</p>' : '')
        . '</li>';
}
$historyHtml .= '</ul>';

$moveIn = $booking['move_in_date'] ? e(date('d/m/Y', strtotime((string) $booking['move_in_date']))) : '—';

$content = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">Richiesta #' . e((string) $id) . '</p>'
    . '<h1><a href="' . e(url_for('room.php?id=' . $booking['room_id'])) . '">' . e((string) $booking['room_name']) . '</a></h1>'
    . '<p class="muted">' . e($booking['property_title'] . ' · ' . $booking['neighborhood_name']) . '</p></div>'
    . '<a class="button-secondary" href="' . e($backUrl) . '">' . e($backLabel) . '</a></header>'
    . $receipt
    . '<section class="panel"><div class="panel-heading"><h2>Stato</h2></div>'
    . '<p>' . booking_status_badge($status) . '</p>'
    . booking_stepper_html($status)
    . '<div class="meta-grid">'
    . '<p><strong>Studente:</strong><br>' . e((string) $booking['student_name']) . '</p>'
    . '<p><strong>Proprietario:</strong><br>' . e((string) $booking['landlord_name']) . '</p>'
    . '<p><strong>Prezzo:</strong><br>' . e(format_price($booking['price_monthly'])) . ' / mese</p>'
    . '<p><strong>Caparra:</strong><br>' . e(format_price($depositAmount)) . '</p>'
    . '<p><strong>Data ingresso:</strong><br>' . $moveIn . '</p>'
    . '</div>'
    . $actionsBox
    . '</section>'
    . '<section class="panel"><div class="panel-heading"><h2>Messaggi</h2></div>'
    . '<div class="message-list">' . message_thread_markup($messages, $uid) . '</div>'
    . $messageForm
    . '</section>'
    . '<section class="panel"><div class="panel-heading"><h2>Storico</h2></div>' . $historyHtml . '</section>'
    . '</section>';

render_page('Richiesta · ' . $booking['room_name'], $content, ['body_class' => 'page-dashboard']);
