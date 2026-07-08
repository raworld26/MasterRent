<?php

declare(strict_types=1);

/*
 * Pagamento della caparra — SIMULAZIONE.
 * Nessun dato reale di pagamento viene raccolto o salvato: è una demo.
 * La caparra è sempre pari a una mensilità d'affitto (prezzo della stanza).
 * Paga solo lo studente titolare e solo se la richiesta è stata approvata.
 */

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
$bookingUrl = url_for('booking.php?id=' . $id);

if ((int) $booking['student_id'] !== $uid) {
    http_response_code(403);
    render_page('Accesso negato',
        '<section class="panel empty-state"><h1>Accesso negato</h1></section>', ['body_class' => 'page-dashboard']);
    exit;
}

if ((string) $booking['status'] !== 'approved_pending_deposit') {
    if ((string) $booking['status'] === 'deposit_paid') {
        set_flash('info', 'La caparra per questa stanza è già stata versata.');
    } else {
        set_flash('danger', 'Puoi versare la caparra solo dopo l\'approvazione del proprietario.');
    }
    redirect($bookingUrl);
}

if (booking_active_for_student($uid) !== null) {
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
    $fresh = booking_find($id);
    if ($fresh === null || (string) $fresh['status'] !== 'approved_pending_deposit') {
        set_flash('info', 'La richiesta non è più in attesa di caparra.');
        redirect($bookingUrl);
    }

    booking_mark_deposit_paid($id, (int) $booking['room_id'], $depositAmount, $uid);
    set_flash('success', 'Caparra versata (simulazione): la stanza è ora prenotata a tuo nome!');
    redirect($bookingUrl);
}

/* ---------------- Vista ---------------- */
$content = '<section class="dashboard-shell">'
    . '<header class="dashboard-header"><div><p class="eyebrow">Pagamento caparra</p><h1>' . e((string) $booking['room_name']) . '</h1>'
    . '<p class="muted">' . e($booking['property_title'] . ' · ' . $booking['neighborhood_name']) . '</p></div>'
    . '<a class="button-secondary" href="' . e($bookingUrl) . '">Torna alla richiesta</a></header>'
    . booking_stepper_html((string) $booking['status'])
    . '<section class="panel">'
    . '<div class="panel-heading"><h2>Riepilogo</h2></div>'
    . '<div class="meta-grid">'
    . '<p><strong>Canone mensile:</strong><br>' . e(format_price($booking['price_monthly'])) . '</p>'
    . '<p><strong>Caparra (1 mensilità):</strong><br>' . e(format_price($depositAmount)) . '</p>'
    . '</div>'
    . '<p class="muted">Questo è un pagamento <strong>simulato</strong> a scopo dimostrativo: non vengono raccolti né salvati dati reali di pagamento. '
    . 'Versando la caparra la stanza risulterà prenotata a tuo nome e non sarà più disponibile per altri studenti.</p>'
    . '<form method="POST" action="' . e(url_for('deposit.php?id=' . $id)) . '" class="form-standard">'
    . csrf_field('deposit_pay')
    . '<button type="submit" class="button-primary">Versa la caparra (simulazione)</button>'
    . '</form>'
    . '</section>'
    . '</section>';

render_page('Pagamento caparra', $content, ['body_class' => 'page-dashboard']);
