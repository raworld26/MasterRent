<?php

declare(strict_types=1);

/*
 * Pagamento della caparra — SIMULAZIONE.
 * Nessun dato reale di pagamento viene raccolto o salvato: è una demo.
 * La caparra è sempre pari a una mensilità d'affitto (prezzo della stanza).
 * Regole: paga solo lo studente titolare della richiesta e solo se la
 * richiesta è stata approvata dal proprietario (approved_pending_deposit).
 */

require_once __DIR__ . '/../src/bootstrap.php';

require_login();

$bookings = new BookingRepository();

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
$bookingUrl = url_for('booking.php?id=' . $id);

// Solo lo studente titolare può pagare.
if ((int) $booking['student_id'] !== $uid) {
    http_response_code(403);
    render_page_frontend('Accesso negato',
        '<section class="panel empty-state"><h1>Accesso negato</h1></section>', ['body_class' => 'page-dashboard']);
    exit;
}

// Non si può pagare prima dell'approvazione del proprietario.
if ((string) $booking['status'] !== 'approved_pending_deposit') {
    if ((string) $booking['status'] === 'deposit_paid') {
        set_flash('info', 'La caparra per questa stanza è già stata versata.');
    } else {
        set_flash('danger', 'Puoi versare la caparra solo dopo l\'approvazione del proprietario.');
    }
    redirect($bookingUrl);
}

if ($bookings->activeForStudent($uid) !== null) {
    set_flash('warning', 'Hai già una casa attuale. Per pagare la caparra di questa stanza, devi prima disdire l\'altra.');
    redirect($bookingUrl);
}

$depositAmount = deposit_amount_for((float) $booking['price_monthly']);

/* ---------------- POST: conferma pagamento simulato ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!verify_csrf_token((string) ($_POST['csrf_token'] ?? ''), 'deposit_pay')) {
        set_flash('danger', 'Sessione scaduta. Riprova.');
        redirect(url_for('deposit.php?id=' . $id));
    }

    // Rilettura difensiva dello stato per evitare doppi pagamenti / race.
    $fresh = $bookings->find($id);
    if ($fresh === null || (string) $fresh['status'] !== 'approved_pending_deposit') {
        set_flash('info', 'La richiesta non è più in attesa di caparra.');
        redirect($bookingUrl);
    }

    $bookings->markDepositPaid($id, (int) $booking['room_id'], $depositAmount, $uid);
    set_flash('success', 'Caparra versata (simulazione): la stanza è ora prenotata a tuo nome!');
    redirect($bookingUrl);
}

/* ---------------- Vista ---------------- */
$content = render_template('frontend/deposit', [
    'back_url' => e($bookingUrl),
    'room_title' => e((string) $booking['room_name']),
    'property_title' => e((string) $booking['property_title']),
    'neighborhood' => e((string) $booking['neighborhood_name']),
    'price' => e(format_price($booking['price_monthly'])),
    'deposit_amount' => e(format_price($depositAmount)),
    'action_url' => e(url_for('deposit.php?id=' . $id)),
    'csrf_field' => csrf_field('deposit_pay'),
    'stepper' => render_stepper((string) $booking['status']),
]);

render_page_frontend('Pagamento caparra', $content, ['body_class' => 'page-dashboard']);
