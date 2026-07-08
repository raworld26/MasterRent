<?php

declare(strict_types=1);

class ReviewRepository extends Repository
{
    /** Recensioni pubblicate di una stanza, con autore. */
    public function forRoom(int $roomId): array
    {
        $stmt = $this->db()->prepare(
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
    public function ratingForRoom(int $roomId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT AVG(rating) AS avg, COUNT(*) AS count FROM reviews
             WHERE room_id = :rid AND status = "published"'
        );
        $stmt->execute(['rid' => $roomId]);
        $row = $stmt->fetch();
        return ['avg' => (float) ($row['avg'] ?? 0), 'count' => (int) ($row['count'] ?? 0)];
    }

    /** Lo studente ha concluso un rapporto reale con questa stanza? */
    public function studentStayed(int $studentId, int $roomId): bool
    {
        $stmt = $this->db()->prepare(
            "SELECT 1 FROM bookings
             WHERE student_id = :sid AND room_id = :rid
               AND status = 'completed'
             LIMIT 1"
        );
        $stmt->execute(['sid' => $studentId, 'rid' => $roomId]);
        return (bool) $stmt->fetchColumn();
    }

    public function hasReviewed(int $studentId, int $roomId): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM reviews WHERE student_id = :sid AND room_id = :rid LIMIT 1');
        $stmt->execute(['sid' => $studentId, 'rid' => $roomId]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(int $roomId, int $studentId, int $rating, ?string $title, ?string $body): void
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO reviews (room_id, student_id, rating, title, body) VALUES (:rid, :sid, :rating, :title, :body)'
        );
        $stmt->execute(['rid' => $roomId, 'sid' => $studentId, 'rating' => $rating, 'title' => $title, 'body' => $body]);
    }

    /** Le recensioni pubblicate più recenti (per la home), con stanza e autore. */
    public function latestPublished(int $limit = 3): array
    {
        $stmt = $this->db()->prepare(
            'SELECT rv.rating, rv.title, rv.body, rv.created_at,
                    r.id AS room_id, r.name AS room_name,
                    CONCAT(u.first_name, " ", LEFT(u.last_name, 1), ".") AS author
             FROM reviews rv
             JOIN rooms r ON r.id = rv.room_id
             JOIN users u ON u.id = rv.student_id
             WHERE rv.status = "published"
             ORDER BY rv.created_at DESC
             LIMIT ' . max(1, $limit)
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /* ---------- Admin ---------- */
    public function allForAdmin(): array
    {
        return $this->db()->query(
            'SELECT rv.id, rv.rating, rv.title, rv.status, rv.created_at,
                    r.name AS room_name, CONCAT(u.first_name, " ", u.last_name) AS author
             FROM reviews rv
             JOIN rooms r ON r.id = rv.room_id
             JOIN users u ON u.id = rv.student_id
             ORDER BY rv.created_at DESC'
        )->fetchAll();
    }

    public function setStatus(int $id, string $status): void
    {
        $this->db()->prepare('UPDATE reviews SET status = :s WHERE id = :id')->execute(['s' => $status, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db()->prepare('DELETE FROM reviews WHERE id = :id')->execute(['id' => $id]);
    }
}
