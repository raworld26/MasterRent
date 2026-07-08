<?php

declare(strict_types=1);

class FavoriteRepository extends Repository
{
    /** @return int[] room ids preferiti dall'utente */
    public function roomIds(int $userId): array
    {
        $stmt = $this->db()->prepare('SELECT room_id FROM favorites WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function isFavorite(int $userId, int $roomId): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM favorites WHERE user_id = :uid AND room_id = :rid LIMIT 1');
        $stmt->execute(['uid' => $userId, 'rid' => $roomId]);
        return (bool) $stmt->fetchColumn();
    }

    public function add(int $userId, int $roomId): void
    {
        $this->db()->prepare('INSERT IGNORE INTO favorites (user_id, room_id) VALUES (:uid, :rid)')
            ->execute(['uid' => $userId, 'rid' => $roomId]);
    }

    public function remove(int $userId, int $roomId): void
    {
        $this->db()->prepare('DELETE FROM favorites WHERE user_id = :uid AND room_id = :rid')
            ->execute(['uid' => $userId, 'rid' => $roomId]);
    }

    /** Stanze preferite con dati per la pagina "Preferiti". */
    public function roomsForUser(int $userId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT r.id, r.name, r.type, r.price_monthly, r.expenses_included, r.status, r.created_at,
                    p.id AS property_id, p.title AS property_title, p.address,
                    n.name AS neighborhood_name,
                    (SELECT pi.filename FROM property_images pi WHERE pi.property_id = p.id
                       ORDER BY pi.is_cover DESC, pi.id ASC LIMIT 1) AS cover,
                    (SELECT MIN(php.distance_minutes) FROM property_has_poles php WHERE php.property_id = p.id) AS min_distance,
                    (SELECT AVG(rv.rating) FROM reviews rv WHERE rv.room_id = r.id AND rv.status = \'published\') AS rating_avg,
                    (SELECT COUNT(*) FROM reviews rv2 WHERE rv2.room_id = r.id AND rv2.status = \'published\') AS rating_count
             FROM favorites f
             JOIN rooms r ON r.id = f.room_id
             JOIN properties p ON p.id = r.property_id
             JOIN neighborhoods n ON n.id = p.neighborhood_id
             WHERE f.user_id = :uid ORDER BY f.created_at DESC'
        );
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetchAll();
    }

    /** Migra i preferiti dalla sessione (ospite) al DB dopo il login. */
    public function mergeFromSession(int $userId, array $roomIds): void
    {
        foreach ($roomIds as $rid) {
            $this->add($userId, (int) $rid);
        }
    }
}
