<?php

declare(strict_types=1);

class PropertyRepository extends Repository
{
    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT p.*, n.name AS neighborhood_name,
                    u.first_name AS landlord_first, u.last_name AS landlord_last, u.email AS landlord_email
             FROM properties p
             JOIN neighborhoods n ON n.id = p.neighborhood_id
             JOIN users u ON u.id = p.landlord_id
             WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function byLandlord(int $landlordId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT p.id, p.title, p.address, p.house_number, n.name AS neighborhood_name,
                    (SELECT COUNT(*) FROM rooms r WHERE r.property_id = p.id) AS room_count,
                    (SELECT pi.filename FROM property_images pi WHERE pi.property_id = p.id
                       ORDER BY pi.is_cover DESC, pi.id ASC LIMIT 1) AS cover
             FROM properties p JOIN neighborhoods n ON n.id = p.neighborhood_id
             WHERE p.landlord_id = :lid ORDER BY p.created_at DESC'
        );
        $stmt->execute(['lid' => $landlordId]);

        return $stmt->fetchAll();
    }

    public function forAdmin(): array
    {
        return $this->db()->query(
            'SELECT p.id, p.title, n.name AS neighborhood_name,
                    CONCAT(u.first_name, " ", u.last_name) AS landlord_name,
                    (SELECT COUNT(*) FROM rooms r WHERE r.property_id = p.id) AS room_count
             FROM properties p
             JOIN neighborhoods n ON n.id = p.neighborhood_id
             JOIN users u ON u.id = p.landlord_id
             ORDER BY p.created_at DESC'
        )->fetchAll();
    }

    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO properties (landlord_id, neighborhood_id, title, description, address, house_number, postal_code, total_rooms, has_elevator, heating_type)
             VALUES (:landlord_id, :neighborhood_id, :title, :description, :address, :house_number, :postal_code, :total_rooms, :has_elevator, :heating_type)'
        );
        $stmt->execute($data);

        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->db()->prepare(
            'UPDATE properties SET neighborhood_id = :neighborhood_id, title = :title, description = :description,
                    address = :address, house_number = :house_number, postal_code = :postal_code,
                    total_rooms = :total_rooms, has_elevator = :has_elevator, heating_type = :heating_type
             WHERE id = :id'
        );
        $stmt->execute($data);
    }

    public function delete(int $id): void
    {
        $this->db()->prepare('DELETE FROM properties WHERE id = :id')->execute(['id' => $id]);
    }

    /* ---------- Immagini ---------- */
    public function imagesFor(int $propertyId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT id, filename, is_cover, caption FROM property_images
             WHERE property_id = :pid ORDER BY is_cover DESC, id ASC'
        );
        $stmt->execute(['pid' => $propertyId]);

        return $stmt->fetchAll();
    }

    public function coverFor(int $propertyId): ?string
    {
        $stmt = $this->db()->prepare(
            'SELECT filename FROM property_images WHERE property_id = :pid ORDER BY is_cover DESC, id ASC LIMIT 1'
        );
        $stmt->execute(['pid' => $propertyId]);
        $value = $stmt->fetchColumn();

        return $value === false ? null : (string) $value;
    }

    public function addImage(int $propertyId, string $filename, bool $isCover = false, ?string $caption = null): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO property_images (property_id, filename, is_cover, caption) VALUES (:pid, :filename, :cover, :caption)'
        );
        $stmt->execute(['pid' => $propertyId, 'filename' => $filename, 'cover' => $isCover ? 1 : 0, 'caption' => $caption]);

        return (int) $this->db()->lastInsertId();
    }

    public function findImage(int $imageId): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM property_images WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $imageId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function deleteImage(int $imageId): void
    {
        $this->db()->prepare('DELETE FROM property_images WHERE id = :id')->execute(['id' => $imageId]);
    }

    public function setCover(int $propertyId, int $imageId): void
    {
        $this->db()->prepare('UPDATE property_images SET is_cover = 0 WHERE property_id = :pid')->execute(['pid' => $propertyId]);
        $this->db()->prepare('UPDATE property_images SET is_cover = 1 WHERE id = :id AND property_id = :pid')
            ->execute(['id' => $imageId, 'pid' => $propertyId]);
    }

    /* ---------- Distanze dai poli ---------- */
    public function polesFor(int $propertyId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT php.pole_id, php.distance_minutes, php.transit_type, up.name AS pole_name, up.code AS pole_code
             FROM property_has_poles php JOIN university_poles up ON up.id = php.pole_id
             WHERE php.property_id = :pid ORDER BY php.distance_minutes ASC'
        );
        $stmt->execute(['pid' => $propertyId]);

        return $stmt->fetchAll();
    }

    public function replacePoleDistances(int $propertyId, array $rows): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $db->prepare('DELETE FROM property_has_poles WHERE property_id = :pid')->execute(['pid' => $propertyId]);

            if ($rows !== []) {
                $stmt = $db->prepare(
                    'INSERT INTO property_has_poles (property_id, pole_id, distance_minutes, transit_type)
                     VALUES (:pid, :pole, :minutes, :transit)'
                );
                foreach ($rows as $row) {
                    $stmt->execute([
                        'pid' => $propertyId,
                        'pole' => (int) $row['pole_id'],
                        'minutes' => max(1, (int) $row['distance_minutes']),
                        'transit' => in_array((string) $row['transit_type'], ['foot', 'bus', 'car'], true)
                            ? (string) $row['transit_type']
                            : 'car',
                    ]);
                }
            }

            $db->commit();
        } catch (Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }

    public function setPole(int $propertyId, int $poleId, int $minutes, string $transit): void
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO property_has_poles (property_id, pole_id, distance_minutes, transit_type)
             VALUES (:pid, :pole, :minutes, :transit)
             ON DUPLICATE KEY UPDATE distance_minutes = VALUES(distance_minutes), transit_type = VALUES(transit_type)'
        );
        $stmt->execute(['pid' => $propertyId, 'pole' => $poleId, 'minutes' => $minutes, 'transit' => $transit]);
    }

    public function removePole(int $propertyId, int $poleId): void
    {
        $this->db()->prepare('DELETE FROM property_has_poles WHERE property_id = :pid AND pole_id = :pole')
            ->execute(['pid' => $propertyId, 'pole' => $poleId]);
    }
}
