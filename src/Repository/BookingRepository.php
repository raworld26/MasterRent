<?php

declare(strict_types=1);

class BookingRepository extends Repository
{
    public function existsForStudentRoom(int $studentId, int $roomId): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM bookings WHERE student_id = :sid AND room_id = :rid LIMIT 1');
        $stmt->execute(['sid' => $studentId, 'rid' => $roomId]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(int $roomId, int $studentId, ?string $message, ?string $moveInDate): int
    {
        $pdo = $this->db();
        $stmt = $pdo->prepare(
            'INSERT INTO bookings (room_id, student_id, message, move_in_date) VALUES (:rid, :sid, :msg, :move)'
        );
        $stmt->execute(['rid' => $roomId, 'sid' => $studentId, 'msg' => $message, 'move' => $moveInDate ?: null]);
        $bookingId = (int) $pdo->lastInsertId();

        $this->addHistory($bookingId, 'visit_requested', 'Richiesta di visita inviata', $studentId);

        // Il primo messaggio del thread riprende il testo della richiesta.
        if ($message !== null && trim($message) !== '') {
            $pdo->prepare('INSERT INTO messages (booking_id, sender_id, body) VALUES (:bid, :sid, :body)')
                ->execute(['bid' => $bookingId, 'sid' => $studentId, 'body' => $message]);
        }

        return $bookingId;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare(
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

    public function byStudent(int $studentId): array
    {
        $stmt = $this->db()->prepare(
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

    public function activeForStudent(int $studentId): ?array
    {
        $stmt = $this->db()->prepare(
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

    public function activeForRoom(int $roomId): ?array
    {
        $stmt = $this->db()->prepare(
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

    public function byLandlord(int $landlordId): array
    {
        $stmt = $this->db()->prepare(
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

    public function allForAdmin(): array
    {
        return $this->db()->query(
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

    public function updateStatus(int $id, string $status, ?string $note, int $changedBy): void
    {
        $this->db()->prepare('UPDATE bookings SET status = :s WHERE id = :id')->execute(['s' => $status, 'id' => $id]);
        $this->addHistory($id, $status, $note, $changedBy);
    }

    public function requestCancellation(int $id, int $studentId, string $message): bool
    {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "UPDATE bookings
                    SET status = 'cancellation_requested'
                  WHERE id = :id AND student_id = :sid AND status = 'deposit_paid'"
            );
            $stmt->execute(['id' => $id, 'sid' => $studentId]);
            if ($stmt->rowCount() !== 1) {
                $pdo->rollBack();
                return false;
            }

            $pdo->prepare('INSERT INTO messages (booking_id, sender_id, body) VALUES (:bid, :sid, :body)')
                ->execute(['bid' => $id, 'sid' => $studentId, 'body' => $message]);
            $this->addHistory($id, 'cancellation_requested', 'Disdetta richiesta dallo studente', $studentId);

            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function releaseRoom(int $roomId, int $changedBy): int
    {
        $pdo = $this->db();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "SELECT id FROM bookings
                  WHERE room_id = :rid
                    AND status IN ('deposit_paid','cancellation_requested')
                  FOR UPDATE"
            );
            $stmt->execute(['rid' => $roomId]);
            $activeBookingIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            $pdo->prepare("UPDATE rooms SET status = 'available', is_available = 1 WHERE id = :rid")
                ->execute(['rid' => $roomId]);

            foreach ($activeBookingIds as $bookingId) {
                $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = :id")
                    ->execute(['id' => $bookingId]);
                $this->addHistory($bookingId, 'completed', 'Rapporto concluso dal proprietario; stanza resa disponibile', $changedBy);
            }

            $pdo->commit();
            return count($activeBookingIds);
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function addHistory(int $bookingId, string $status, ?string $note, ?int $changedBy): void
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO booking_status_history (booking_id, status, note, changed_by) VALUES (:bid, :s, :note, :by)'
        );
        $stmt->execute(['bid' => $bookingId, 's' => $status, 'note' => $note, 'by' => $changedBy]);
    }

    public function historyFor(int $bookingId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT status, note, created_at FROM booking_status_history WHERE booking_id = :bid ORDER BY created_at ASC, id ASC'
        );
        $stmt->execute(['bid' => $bookingId]);
        return $stmt->fetchAll();
    }

    /** Stato della richiesta esistente dello studente su una stanza (o null). */
    public function statusForStudentRoom(int $studentId, int $roomId): ?string
    {
        $stmt = $this->db()->prepare('SELECT status FROM bookings WHERE student_id = :sid AND room_id = :rid LIMIT 1');
        $stmt->execute(['sid' => $studentId, 'rid' => $roomId]);
        $status = $stmt->fetchColumn();
        return $status === false ? null : (string) $status;
    }

    /** Richiesta esistente dello studente su una stanza (id + stato) o null. */
    public function findForStudentRoom(int $studentId, int $roomId): ?array
    {
        $stmt = $this->db()->prepare('SELECT id, status FROM bookings WHERE student_id = :sid AND room_id = :rid LIMIT 1');
        $stmt->execute(['sid' => $studentId, 'rid' => $roomId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** Il proprietario approva la richiesta dopo la visita. */
    public function approve(int $id, int $changedBy): void
    {
        $this->updateStatus($id, 'approved_pending_deposit', 'Richiesta approvata dopo la visita', $changedBy);
    }

    /** Il proprietario rifiuta la richiesta. */
    public function reject(int $id, ?string $note, int $changedBy): void
    {
        $this->updateStatus($id, 'rejected', $note ?: 'Richiesta rifiutata', $changedBy);
    }

    /**
     * Registra il pagamento (SIMULATO) della caparra e prenota la stanza.
     * Operazione atomica: aggiorna la richiesta, marca la stanza come
     * 'reserved' e rifiuta automaticamente le altre richieste attive sulla
     * stessa stanza. La caparra è sempre pari a una mensilità.
     *
     * @return string riferimento (fittizio) del pagamento
     */
    public function markDepositPaid(int $id, int $roomId, float $amount, int $changedBy): string
    {
        $pdo = $this->db();
        $reference = 'DEMO-PAY-' . str_pad((string) $id, 6, '0', STR_PAD_LEFT);

        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'UPDATE bookings
                    SET status = :s, deposit_amount = :amt, deposit_paid_at = NOW(), deposit_reference = :ref
                  WHERE id = :id'
            )->execute(['s' => 'deposit_paid', 'amt' => $amount, 'ref' => $reference, 'id' => $id]);

            $this->addHistory($id, 'deposit_paid', 'Caparra versata (pagamento simulato)', $changedBy);

            // La stanza diventa prenotata e sparisce dagli annunci disponibili.
            $pdo->prepare("UPDATE rooms SET status = 'reserved', is_available = 0 WHERE id = :rid")
                ->execute(['rid' => $roomId]);

            // Le altre richieste ancora aperte sulla stanza vengono rifiutate.
            $others = $pdo->prepare(
                "SELECT id FROM bookings
                  WHERE room_id = :rid AND id <> :id
                    AND status IN ('visit_requested','approved_pending_deposit')"
            );
            $others->execute(['rid' => $roomId, 'id' => $id]);
            foreach ($others->fetchAll(PDO::FETCH_COLUMN) as $otherId) {
                $pdo->prepare('UPDATE bookings SET status = :s WHERE id = :id')
                    ->execute(['s' => 'rejected', 'id' => (int) $otherId]);
                $this->addHistory((int) $otherId, 'rejected', 'Stanza non più disponibile (prenotata da un altro studente)', $changedBy);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $reference;
    }
}
