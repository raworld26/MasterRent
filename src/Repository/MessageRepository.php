<?php

declare(strict_types=1);

class MessageRepository extends Repository
{
    public function forBooking(int $bookingId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT m.id, m.sender_id, m.body, m.created_at,
                    CONCAT(u.first_name, " ", u.last_name) AS sender_name
             FROM messages m JOIN users u ON u.id = m.sender_id
             WHERE m.booking_id = :bid ORDER BY m.created_at ASC'
        );
        $stmt->execute(['bid' => $bookingId]);
        return $stmt->fetchAll();
    }

    public function create(int $bookingId, int $senderId, string $body): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO messages (booking_id, sender_id, body) VALUES (:bid, :sid, :body)'
        );
        $stmt->execute(['bid' => $bookingId, 'sid' => $senderId, 'body' => $body]);
        return (int) $this->db()->lastInsertId();
    }

    public function markReadForViewer(int $bookingId, int $viewerId): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE messages SET read_at = NOW()
             WHERE booking_id = :bid AND sender_id <> :uid AND read_at IS NULL'
        );
        $stmt->execute(['bid' => $bookingId, 'uid' => $viewerId]);
    }
}
