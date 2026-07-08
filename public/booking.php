<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

require_login();

$bookings = new BookingRepository();
$messages = new MessageRepository();

$id = (int) query_str('id');
$booking = $id > 0 ? $bookings->find($id) : null;

if ($booking === null) {
    http_response_code(404);
    render_page_frontend('Richiesta non trovata',
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
    render_page_frontend('Accesso negato',
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

    $messageClosedStates = ['rejected', 'withdrawn', 'completed'];
    $withdrawClosedStates = ['rejected', 'deposit_paid', 'cancellation_requested', 'completed', 'withdrawn'];

    if ($action === 'message' && ($isStudentOwner || $isLandlordOwner) && !in_array($booking['status'], $messageClosedStates, true)) {
        $body = post_str('body');
        if (mb_strlen($body) < 2) {
            set_flash('danger', 'Il messaggio è troppo corto.');
        } else {
            $messages->create($id, $uid, $body);
            set_flash('success', 'Messaggio inviato.');
        }
        redirect($bookingUrl);
    }

    // Il proprietario (o l'admin) approva la richiesta dopo la visita.
    if ($action === 'approve' && ($isLandlordOwner || $isAdmin) && $booking['status'] === 'visit_requested') {
        $bookings->approve($id, $uid);
        set_flash('success', 'Richiesta approvata: lo studente può ora versare la caparra.');
        redirect($bookingUrl);
    }

    // Il proprietario (o l'admin) rifiuta la richiesta.
    if ($action === 'reject' && ($isLandlordOwner || $isAdmin) && in_array($booking['status'], ['visit_requested', 'approved_pending_deposit'], true)) {
        $body = post_str('body');
        if (mb_strlen($body) < 2) {
            set_flash('danger', 'Il messaggio è troppo corto.');
        } else {
            $messages->create($id, $uid, $body);
            set_flash('success', 'Messaggio inviato.');
        }
        redirect($bookingUrl);
    }

    // Il proprietario (o l'admin) approva la richiesta dopo la visita.
    if ($action === 'approve' && ($isLandlordOwner || $isAdmin) && $booking['status'] === 'visit_requested') {
        $bookings->approve($id, $uid);
        set_flash('success', 'Richiesta approvata: lo studente può ora versare la caparra.');
        redirect($bookingUrl);
    }

    // Il proprietario (o l'admin) rifiuta la richiesta.
    if ($action === 'reject' && ($isLandlordOwner || $isAdmin) && in_array($booking['status'], ['visit_requested', 'approved_pending_deposit'], true)) {
        $bookings->reject($id, post_str('note') ?: null, $uid);
        set_flash('info', 'Richiesta rifiutata.');
        redirect($bookingUrl);
    }

    if ($action === 'withdraw' && $isStudentOwner && !in_array($booking['status'], $withdrawClosedStates, true)) {
        $bookings->updateStatus($id, 'withdrawn', 'Richiesta ritirata dallo studente', $uid);
        set_flash('info', 'Richiesta ritirata.');
        redirect($bookingUrl);
    }

    if ($action === 'refund' && ($isLandlordOwner || $isAdmin) && $booking['status'] === 'cancellation_requested') {
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

/* ---------------- View ---------------- */
$messages->markReadForViewer($id, $uid);

$threadRows = array_map(static fn ($m) => [
    'msg_side' => (int) $m['sender_id'] === $uid ? 'msg-me' : 'msg-them',
    'msg_body' => e($m['body']),
    'msg_sender' => e($m['sender_name']),
    'msg_time' => e(date('d/m H:i', strtotime((string) $m['created_at']))),
], $messages->forBooking($id));

$threadHtml = $threadRows === [] ? '' : render_list('frontend/_messages', $threadRows);

$historyRows = array_map(static fn ($h) => [
    'hist_status' => booking_status_badge((string) $h['status']),
    'hist_note' => e((string) ($h['note'] ?? '')),
    'hist_time' => e(date('d/m/Y H:i', strtotime((string) $h['created_at']))),
], $bookings->historyFor($id));
$historyHtml = render_list('frontend/_history', $historyRows);

$backUrl = $isLandlordOwner ? url_for('landlord/bookings.php')
    : ($isAdmin && !$isStudentOwner ? url_for('admin/bookings/index.php') : url_for('account/bookings.php'));
$backLabel = $isLandlordOwner ? 'Richieste ricevute' : ($isAdmin && !$isStudentOwner ? 'Tutte le richieste' : 'Le mie richieste');

$status = (string) $booking['status'];
$depositAmount = deposit_amount_for((float) $booking['price_monthly']);

$content = render_template('frontend/booking', [
    'back_url' => e($backUrl),
    'back_label' => e($backLabel),
    'room_title' => e($booking['room_name']),
    'property_title' => e($booking['property_title']),
    'neighborhood' => e($booking['neighborhood_name']),
    'status_badge' => render_badge_booking_status($status),
    'stepper' => render_stepper($status),
    'action_url' => e($bookingUrl),
    'csrf_field' => csrf_field('booking_action'),
    'thread' => $threadHtml,
    'no_messages' => $threadRows === [] ? '1' : '',
    'show_message_form' => (($isStudentOwner || $isLandlordOwner) && !in_array($status, ['withdrawn', 'rejected', 'completed'], true)) ? '1' : '',
    'student_name' => e($booking['student_name']),
    'landlord_name' => e($booking['landlord_name']),
    'move_in' => $booking['move_in_date'] ? e(date('d/m/Y', strtotime((string) $booking['move_in_date']))) : '',
    'room_url' => e(url_for('room.php?id=' . $booking['room_id'])),
    'price' => e(format_price($booking['price_monthly'])),
    'deposit_amount' => e(format_price($depositAmount)),
    // Decisione del proprietario/admin (solo su richiesta di visita in attesa).
    'show_decision' => (($isLandlordOwner || $isAdmin) && $status === 'visit_requested') ? '1' : '',
    // Lo studente approvato può versare la caparra.
    'show_pay' => ($isStudentOwner && $status === 'approved_pending_deposit') ? '1' : '',
    'deposit_url' => e(url_for('deposit.php?id=' . $id)),
    // Ricevuta caparra quando pagata. Le righe facoltative sono pre-renderizzate
    // qui perché template2 non supporta <[if!empty]> annidati nello stesso blocco.
    'show_receipt' => in_array($status, ['deposit_paid', 'cancellation_requested', 'completed', 'withdrawn'], true) && (float)$booking['deposit_amount'] > 0 ? '1' : '',
    'receipt_rows' =>
        ($booking['deposit_paid_at']
            ? '<p><span class="muted">Data:</span> ' . e(date('d/m/Y H:i', strtotime((string) $booking['deposit_paid_at']))) . '</p>'
            : '')
        . (($booking['deposit_reference'] ?? '') !== ''
            ? '<p><span class="muted">Riferimento:</span> <code>' . e((string) $booking['deposit_reference']) . '</code></p>'
            : '')
        . (isset($booking['refund_amount'])
            ? '<p><span class="muted">Caparra restituita:</span> <strong>' . e(format_price((float)$booking['refund_amount'])) . '</strong></p>'
            : ''),
    'show_refund' => (($isLandlordOwner || $isAdmin) && $status === 'cancellation_requested') ? '1' : '',
    'show_withdraw' => ($isStudentOwner && !in_array($status, ['rejected', 'deposit_paid', 'cancellation_requested', 'completed', 'withdrawn'], true)) ? '1' : '',
    'history' => $historyHtml,
]);

render_page_frontend('Richiesta · ' . $booking['room_name'], $content, ['body_class' => 'page-dashboard']);
