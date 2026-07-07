<?php

declare(strict_types=1);

/*
 * Livello dati dell'interazione studente-proprietario: richieste di visita,
 * ciclo di prenotazione con caparra, storico stati, messaggi e recensioni.
 * Funzioni procedurali (stile phase1) che replicano la logica di business
 * della Fase 2.
 */

/* =====================================================================
 * PRENOTAZIONI (bookings)
 * ===================================================================== */

function booking_exists_for_student_room(int $studentId, int $roomId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM bookings WHERE student_id = :sid AND room_id = :rid LIMIT 1');
    $stmt->execute(['sid' => $studentId, 'rid' => $roomId]);
    return (bool) $stmt->fetchColumn();
}

/** Richiesta esistente dello studente su una stanza (id + stato) o null. */
function booking_find_for_student_room(int $studentId, int $roomId): ?array
{
    $stmt = db()->prepare('SELECT id, status FROM bookings WHERE student_id = :sid AND room_id = :rid LIMIT 1');
    $stmt->execute(['sid' => $studentId, 'rid' => $roomId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

/**
 * Crea una richiesta di visita: registra lo storico 'visit_requested' e usa il
 * testo della richiesta come primo messaggio del thread. Ritorna l'id.
 */
function booking_create(int $roomId, int $studentId, ?string $message, ?string $moveInDate): int
{
    $db = db();
    $stmt = $db->prepare(
        'INSERT INTO bookings (room_id, student_id, message, move_in_date) VALUES (:rid, :sid, :msg, :move)'
    );
    $stmt->execute([
        'rid' => $roomId,
        'sid' => $studentId,
        'msg' => $message,
        'move' => ($moveInDate !== null && $moveInDate !== '') ? $moveInDate : null,
    ]);
    $bookingId = (int) $db->lastInsertId();

    booking_add_history($bookingId, 'visit_requested', 'Richiesta di visita inviata', $studentId);

    if ($message !== null && trim($message) !== '') {
        $db->prepare('INSERT INTO messages (booking_id, sender_id, body) VALUES (:bid, :sid, :body)')
            ->execute(['bid' => $bookingId, 'sid' => $studentId, 'body' => $message]);
    }

    return $bookingId;
}

/** Dettaglio completo di una richiesta (con stanza, immobile, studente, proprietario). */
function booking_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT b.*, r.name AS room_name, r.price_monthly, r.status AS room_status,
                p.id AS property_id, p.title AS property_title,
                p.landlord_id, n.name AS neighborhood_name,
                CONCAT(su.first_name, " ", su.last_name) AS student_name, su.email AS student_email, su.phone AS student_phone,
                CONCAT(lu.first_name, " ", lu.last_name) AS landlord_name, lu.email AS landlord_email
         FROM bookings b
         JOIN rooms r ON r.id = b.room_id
         JOIN properties p ON p.id = r.property_id
         JOIN neighborhoods n ON n.id = p.neighborhood_id
         JOIN users su ON su.id = b.student_id
         JOIN users lu ON lu.id = p.landlord_id
         WHERE b.id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function bookings_by_student(int $studentId): array
{
    $stmt = db()->prepare(
        'SELECT b.id, b.status, b.created_at, b.move_in_date, b.deposit_amount,
                r.id AS room_id, r.name AS room_name, r.price_monthly,
                p.title AS property_title, n.name AS neighborhood_name
         FROM bookings b
         JOIN rooms r ON r.id = b.room_id
         JOIN properties p ON p.id = r.property_id
         JOIN neighborhoods n ON n.id = p.neighborhood_id
         WHERE b.student_id = :sid ORDER BY b.created_at DESC'
    );
    $stmt->execute(['sid' => $studentId]);
    return $stmt->fetchAll();
}

function booking_active_for_student(int $studentId): ?array
{
    $stmt = db()->prepare(
        "SELECT b.*, r.id AS room_id, r.name AS room_name, r.type AS room_type,
                r.price_monthly, r.status AS room_status, r.is_available,
                p.id AS property_id, p.title AS property_title, p.description AS property_description,
                p.address, p.house_number, p.postal_code,
                p.landlord_id, n.name AS neighborhood_name,
                CONCAT(su.first_name, ' ', su.last_name) AS student_name,
                CONCAT(lu.first_name, ' ', lu.last_name) AS landlord_name,
                lu.email AS landlord_email, lu.phone AS landlord_phone,
                (SELECT pi.filename FROM property_images pi WHERE pi.property_id = p.id
                   ORDER BY pi.is_cover DESC, pi.id ASC LIMIT 1) AS cover
         FROM bookings b
         JOIN rooms r ON r.id = b.room_id
         JOIN properties p ON p.id = r.property_id
         JOIN neighborhoods n ON n.id = p.neighborhood_id
         JOIN users su ON su.id = b.student_id
         JOIN users lu ON lu.id = p.landlord_id
         WHERE b.student_id = :sid
           AND b.status IN ('deposit_paid','cancellation_requested')
         ORDER BY b.updated_at DESC, b.id DESC
         LIMIT 1"
    );
    $stmt->execute(['sid' => $studentId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function booking_active_for_room(int $roomId): ?array
{
    $stmt = db()->prepare(
        "SELECT b.status, b.created_at, b.updated_at,
                su.first_name, su.last_name, su.email, su.phone
         FROM bookings b
         JOIN users su ON su.id = b.student_id
         WHERE b.room_id = :rid
           AND b.status IN ('deposit_paid','cancellation_requested')
         ORDER BY b.updated_at DESC, b.id DESC
         LIMIT 1"
    );
    $stmt->execute(['rid' => $roomId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function bookings_by_landlord(int $landlordId): array
{
    $stmt = db()->prepare(
        'SELECT b.id, b.status, b.created_at, b.move_in_date, b.deposit_amount,
                r.id AS room_id, r.name AS room_name, r.price_monthly, r.status AS room_status,
                p.title AS property_title,
                CONCAT(su.first_name, " ", su.last_name) AS student_name
         FROM bookings b
         JOIN rooms r ON r.id = b.room_id
         JOIN properties p ON p.id = r.property_id
         JOIN users su ON su.id = b.student_id
         WHERE p.landlord_id = :lid ORDER BY b.created_at DESC'
    );
    $stmt->execute(['lid' => $landlordId]);
    return $stmt->fetchAll();
}

function bookings_all_for_admin(): array
{
    return db()->query(
        'SELECT b.id, b.status, b.created_at, b.deposit_amount,
                r.name AS room_name, r.price_monthly, p.title AS property_title,
                CONCAT(su.first_name, " ", su.last_name) AS student_name,
                CONCAT(lu.first_name, " ", lu.last_name) AS landlord_name
         FROM bookings b
         JOIN rooms r ON r.id = b.room_id
         JOIN properties p ON p.id = r.property_id
         JOIN users su ON su.id = b.student_id
         JOIN users lu ON lu.id = p.landlord_id
         ORDER BY b.created_at DESC'
    )->fetchAll();
}

function booking_add_history(int $bookingId, string $status, ?string $note, ?int $changedBy): void
{
    $stmt = db()->prepare(
        'INSERT INTO booking_status_history (booking_id, status, note, changed_by) VALUES (:bid, :s, :note, :by)'
    );
    $stmt->execute(['bid' => $bookingId, 's' => $status, 'note' => $note, 'by' => $changedBy]);
}

function booking_history(int $bookingId): array
{
    $stmt = db()->prepare(
        'SELECT status, note, created_at FROM booking_status_history WHERE booking_id = :bid ORDER BY created_at ASC, id ASC'
    );
    $stmt->execute(['bid' => $bookingId]);
    return $stmt->fetchAll();
}

function booking_update_status(int $id, string $status, ?string $note, int $changedBy): void
{
    db()->prepare('UPDATE bookings SET status = :s WHERE id = :id')->execute(['s' => $status, 'id' => $id]);
    booking_add_history($id, $status, $note, $changedBy);
}

function booking_request_cancellation(int $id, int $studentId, string $message): bool
{
    $db = db();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
            "UPDATE bookings
                SET status = 'cancellation_requested'
              WHERE id = :id AND student_id = :sid AND status = 'deposit_paid'"
        );
        $stmt->execute(['id' => $id, 'sid' => $studentId]);
        if ($stmt->rowCount() !== 1) {
            $db->rollBack();
            return false;
        }

        $db->prepare('INSERT INTO messages (booking_id, sender_id, body) VALUES (:bid, :sid, :body)')
            ->execute(['bid' => $id, 'sid' => $studentId, 'body' => $message]);
        booking_add_history($id, 'cancellation_requested', 'Disdetta richiesta dallo studente', $studentId);

        $db->commit();
        return true;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function booking_release_room(int $roomId, int $changedBy): int
{
    $db = db();
    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
            "SELECT id FROM bookings
              WHERE room_id = :rid
                AND status IN ('deposit_paid','cancellation_requested')
              FOR UPDATE"
        );
        $stmt->execute(['rid' => $roomId]);
        $activeBookingIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        $db->prepare("UPDATE rooms SET status = 'available', is_available = 1 WHERE id = :rid")
            ->execute(['rid' => $roomId]);

        foreach ($activeBookingIds as $bookingId) {
            $db->prepare("UPDATE bookings SET status = 'completed' WHERE id = :id")
                ->execute(['id' => $bookingId]);
            booking_add_history($bookingId, 'completed', 'Rapporto concluso dal proprietario; stanza resa disponibile', $changedBy);
        }

        $db->commit();
        return count($activeBookingIds);
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function booking_approve(int $id, int $changedBy): void
{
    booking_update_status($id, 'approved_pending_deposit', 'Richiesta approvata dopo la visita', $changedBy);
}

function booking_reject(int $id, ?string $note, int $changedBy): void
{
    booking_update_status($id, 'rejected', $note ?: 'Richiesta rifiutata', $changedBy);
}

/**
 * Registra il pagamento (SIMULATO) della caparra e prenota la stanza.
 * Operazione atomica: aggiorna la richiesta, marca la stanza come 'reserved'
 * (is_available = 0) e rifiuta automaticamente le altre richieste attive sulla
 * stessa stanza. La caparra è sempre pari a una mensilità.
 * Ritorna il riferimento (fittizio) del pagamento.
 */
function booking_mark_deposit_paid(int $id, int $roomId, float $amount, int $changedBy): string
{
    $db = db();
    $reference = 'DEMO-PAY-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);

    $db->beginTransaction();
    try {
        $db->prepare(
            'UPDATE bookings SET status = :s, deposit_amount = :amt, deposit_paid_at = NOW(), deposit_reference = :ref WHERE id = :id'
        )->execute(['s' => 'deposit_paid', 'amt' => $amount, 'ref' => $reference, 'id' => $id]);

        booking_add_history($id, 'deposit_paid', 'Caparra versata (pagamento simulato)', $changedBy);

        $db->prepare("UPDATE rooms SET status = 'reserved', is_available = 0 WHERE id = :rid")
            ->execute(['rid' => $roomId]);

        $others = $db->prepare(
            "SELECT id FROM bookings WHERE room_id = :rid AND id <> :id
              AND status IN ('visit_requested','approved_pending_deposit')"
        );
        $others->execute(['rid' => $roomId, 'id' => $id]);
        foreach ($others->fetchAll(PDO::FETCH_COLUMN) as $otherId) {
            $db->prepare('UPDATE bookings SET status = :s WHERE id = :id')
                ->execute(['s' => 'rejected', 'id' => (int) $otherId]);
            booking_add_history((int) $otherId, 'rejected', 'Stanza non più disponibile (prenotata da un altro studente)', $changedBy);
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    return $reference;
}

/* =====================================================================
 * MESSAGGI (messages)
 * ===================================================================== */

function messages_for_booking(int $bookingId): array
{
    $stmt = db()->prepare(
        'SELECT m.id, m.sender_id, m.body, m.created_at,
                CONCAT(u.first_name, " ", u.last_name) AS sender_name
         FROM messages m JOIN users u ON u.id = m.sender_id
         WHERE m.booking_id = :bid ORDER BY m.created_at ASC, m.id ASC'
    );
    $stmt->execute(['bid' => $bookingId]);
    return $stmt->fetchAll();
}

function message_create(int $bookingId, int $senderId, string $body): int
{
    $stmt = db()->prepare('INSERT INTO messages (booking_id, sender_id, body) VALUES (:bid, :sid, :body)');
    $stmt->execute(['bid' => $bookingId, 'sid' => $senderId, 'body' => $body]);
    return (int) db()->lastInsertId();
}

function messages_mark_read_for_viewer(int $bookingId, int $viewerId): void
{
    $stmt = db()->prepare(
        'UPDATE messages SET read_at = NOW() WHERE booking_id = :bid AND sender_id <> :uid AND read_at IS NULL'
    );
    $stmt->execute(['bid' => $bookingId, 'uid' => $viewerId]);
}

/* =====================================================================
 * RECENSIONI (reviews)
 * ===================================================================== */

/** Recensioni pubblicate di una stanza, con autore (Nome C.). */
function reviews_for_room(int $roomId): array
{
    $stmt = db()->prepare(
        'SELECT rv.id, rv.rating, rv.title, rv.body, rv.created_at,
                CONCAT(u.first_name, " ", LEFT(u.last_name, 1), ".") AS author
         FROM reviews rv JOIN users u ON u.id = rv.student_id
         WHERE rv.room_id = :rid AND rv.status = "published"
         ORDER BY rv.created_at DESC'
    );
    $stmt->execute(['rid' => $roomId]);
    return $stmt->fetchAll();
}

/** @return array{avg:float,count:int} */
function review_rating_for_room(int $roomId): array
{
    $stmt = db()->prepare(
        'SELECT AVG(rating) AS avg, COUNT(*) AS count FROM reviews WHERE room_id = :rid AND status = "published"'
    );
    $stmt->execute(['rid' => $roomId]);
    $row = $stmt->fetch();
    return ['avg' => (float) ($row['avg'] ?? 0), 'count' => (int) ($row['count'] ?? 0)];
}

/** Lo studente ha concluso un rapporto reale con questa stanza? */
function review_student_stayed(int $studentId, int $roomId): bool
{
    $stmt = db()->prepare(
        "SELECT 1 FROM bookings
         WHERE student_id = :sid AND room_id = :rid
           AND status = 'completed'
         LIMIT 1"
    );
    $stmt->execute(['sid' => $studentId, 'rid' => $roomId]);
    return (bool) $stmt->fetchColumn();
}

function review_has_reviewed(int $studentId, int $roomId): bool
{
    $stmt = db()->prepare('SELECT 1 FROM reviews WHERE student_id = :sid AND room_id = :rid LIMIT 1');
    $stmt->execute(['sid' => $studentId, 'rid' => $roomId]);
    return (bool) $stmt->fetchColumn();
}

function review_create(int $roomId, int $studentId, int $rating, ?string $title, ?string $body): void
{
    $stmt = db()->prepare(
        'INSERT INTO reviews (room_id, student_id, rating, title, body) VALUES (:rid, :sid, :rating, :title, :body)'
    );
    $stmt->execute(['rid' => $roomId, 'sid' => $studentId, 'rating' => $rating, 'title' => $title, 'body' => $body]);
}

/** Recensioni pubblicate più recenti (per la home), con stanza e autore. */
function reviews_latest_published(int $limit = 3): array
{
    $limit = max(1, $limit);
    $stmt = db()->prepare(
        'SELECT rv.rating, rv.title, rv.body, rv.created_at,
                r.id AS room_id, r.name AS room_name,
                CONCAT(u.first_name, " ", LEFT(u.last_name, 1), ".") AS author
         FROM reviews rv
         JOIN rooms r ON r.id = rv.room_id
         JOIN users u ON u.id = rv.student_id
         WHERE rv.status = "published"
         ORDER BY rv.created_at DESC
         LIMIT ' . $limit
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function reviews_all_for_admin(): array
{
    return db()->query(
        'SELECT rv.id, rv.rating, rv.title, rv.status, rv.created_at,
                r.id AS room_id, r.name AS room_name, CONCAT(u.first_name, " ", u.last_name) AS author
         FROM reviews rv
         JOIN rooms r ON r.id = rv.room_id
         JOIN users u ON u.id = rv.student_id
         ORDER BY rv.created_at DESC'
    )->fetchAll();
}

function review_set_status(int $id, string $status): void
{
    db()->prepare('UPDATE reviews SET status = :s WHERE id = :id')->execute(['s' => $status, 'id' => $id]);
}

function review_delete(int $id): void
{
    db()->prepare('DELETE FROM reviews WHERE id = :id')->execute(['id' => $id]);
}
