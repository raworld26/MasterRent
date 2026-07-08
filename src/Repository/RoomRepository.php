<?php

declare(strict_types=1);

class RoomRepository extends Repository
{
    /**
     * Ricerca stanze (room-centric) con filtri opzionali.
     * Filtri: q, neighborhood_id, pole_id, type, price_min, price_max, sort.
     */
    public function search(array $filters = []): array
    {
        // Solo unità pubblicabili: disponibili manualmente e non prenotate.
        $where = ['r.is_available = 1', "r.status = 'available'"];
        $params = [];
        $priceMin = isset($filters['price_min']) && is_numeric($filters['price_min'])
            ? max(0.0, (float) $filters['price_min'])
            : null;
        $priceMax = isset($filters['price_max']) && is_numeric($filters['price_max'])
            ? max(0.0, (float) $filters['price_max'])
            : null;
        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            [$priceMin, $priceMax] = [$priceMax, $priceMin];
        }

        if (!empty($filters['q'])) {
            $where[] = '(p.title LIKE :q_title OR n.name LIKE :q_neighborhood OR p.address LIKE :q_address)';
            $query = '%' . $filters['q'] . '%';
            $params['q_title'] = $query;
            $params['q_neighborhood'] = $query;
            $params['q_address'] = $query;
        }
        if (!empty($filters['neighborhood_id'])) {
            $where[] = 'p.neighborhood_id = :nb';
            $params['nb'] = (int) $filters['neighborhood_id'];
        }
        if (!empty($filters['type'])) {
            $where[] = 'r.type = :type';
            $params['type'] = $filters['type'];
        }
        if ($priceMin !== null) {
            $where[] = 'r.price_monthly >= :pmin';
            $params['pmin'] = $priceMin;
        }
        if ($priceMax !== null) {
            $where[] = 'r.price_monthly <= :pmax';
            $params['pmax'] = $priceMax;
        }
        // Il filtro pole_id è gestito a livello applicativo in public/search.php tramite ZoneEstimates
        if (!empty($filters['furnished'])) {
            // Solo stanze con l'accessorio "Arredato" (code = furnished).
            $where[] = "EXISTS (SELECT 1 FROM room_has_amenities rha
                                JOIN amenities a ON a.id = rha.amenity_id AND a.code = 'furnished'
                                WHERE rha.room_id = r.id)";
        }

        $order = [
            'price_asc' => 'r.price_monthly ASC',
            'price_desc' => 'r.price_monthly DESC',
            'newest' => 'r.created_at DESC',
            'distance' => 'min_distance IS NULL, min_distance ASC, r.price_monthly ASC',
        ][$filters['sort'] ?? 'price_asc'] ?? 'r.price_monthly ASC';

        $sql = 'SELECT r.id, r.name, r.type, r.price_monthly, r.expenses_included, r.created_at,
                       p.id AS property_id, p.title AS property_title, p.address, p.house_number,
                       n.name AS neighborhood_name, n.id AS neighborhood_id, n.code AS neighborhood_code,
                       (SELECT pi.filename FROM property_images pi WHERE pi.property_id = p.id
                          ORDER BY pi.is_cover DESC, pi.id ASC LIMIT 1) AS cover,
                       (SELECT COUNT(*) FROM property_images pi2 WHERE pi2.property_id = p.id) AS image_count,
                       (SELECT MIN(php.distance_minutes) FROM property_has_poles php WHERE php.property_id = p.id) AS min_distance,
                       (SELECT AVG(rv.rating) FROM reviews rv WHERE rv.room_id = r.id AND rv.status = \'published\') AS rating_avg,
                       (SELECT COUNT(*) FROM reviews rv2 WHERE rv2.room_id = r.id AND rv2.status = \'published\') AS rating_count
                FROM rooms r
                JOIN properties p ON p.id = r.property_id
                JOIN neighborhoods n ON n.id = p.neighborhood_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY ' . $order;

        $stmt = $this->db()->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function countAvailable(): int
    {
        return (int) $this->db()->query("SELECT COUNT(*) FROM rooms WHERE is_available = 1 AND status = 'available'")->fetchColumn();
    }

    /** Aggiorna lo stato del ciclo di prenotazione di una stanza. */
    public function setStatus(int $id, string $status): void
    {
        // Una stanza prenotata/non disponibile non deve restare pubblicabile.
        $isAvailable = $status === 'available' ? 1 : 0;
        $this->db()->prepare('UPDATE rooms SET status = :s, is_available = :av WHERE id = :id')
            ->execute(['s' => $status, 'av' => $isAvailable, 'id' => $id]);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT r.*, p.id AS property_id, p.title AS property_title, p.description AS property_description,
                    p.address, p.house_number, p.postal_code, p.has_elevator, p.heating_type, p.total_rooms,
                    p.landlord_id, n.name AS neighborhood_name, n.id AS neighborhood_id, n.code AS neighborhood_code,
                    u.first_name AS landlord_first, u.last_name AS landlord_last,
                    u.email AS landlord_email, u.phone AS landlord_phone
             FROM rooms r
             JOIN properties p ON p.id = r.property_id
             JOIN neighborhoods n ON n.id = p.neighborhood_id
             JOIN users u ON u.id = p.landlord_id
             WHERE r.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function byProperty(int $propertyId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT id, name, type, price_monthly, deposit_months, expenses_included, contract_type, is_available, status
             FROM rooms WHERE property_id = :pid ORDER BY price_monthly ASC'
        );
        $stmt->execute(['pid' => $propertyId]);

        return $stmt->fetchAll();
    }

    public function amenitiesForRoom(int $roomId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT a.id, a.code, a.name, a.icon
             FROM room_has_amenities rha JOIN amenities a ON a.id = rha.amenity_id
             WHERE rha.room_id = :rid ORDER BY a.name ASC'
        );
        $stmt->execute(['rid' => $roomId]);

        return $stmt->fetchAll();
    }

    public function amenityIds(int $roomId): array
    {
        $stmt = $this->db()->prepare('SELECT amenity_id FROM room_has_amenities WHERE room_id = :rid');
        $stmt->execute(['rid' => $roomId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO rooms (property_id, name, type, price_monthly, deposit_months, expenses_included, contract_type, is_available)
             VALUES (:property_id, :name, :type, :price_monthly, :deposit_months, :expenses_included, :contract_type, :is_available)'
        );
        $stmt->execute($data);

        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->db()->prepare(
            'UPDATE rooms SET name = :name, type = :type, price_monthly = :price_monthly,
                    deposit_months = :deposit_months, expenses_included = :expenses_included,
                    contract_type = :contract_type, is_available = :is_available
             WHERE id = :id'
        );
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $stmt = $this->db()->prepare('DELETE FROM rooms WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function setAmenities(int $roomId, array $amenityIds): void
    {
        $this->db()->prepare('DELETE FROM room_has_amenities WHERE room_id = :rid')->execute(['rid' => $roomId]);
        if ($amenityIds === []) {
            return;
        }
        $stmt = $this->db()->prepare('INSERT IGNORE INTO room_has_amenities (room_id, amenity_id) VALUES (:rid, :aid)');
        foreach ($amenityIds as $aid) {
            $stmt->execute(['rid' => $roomId, 'aid' => (int) $aid]);
        }
    }
}
