<?php

declare(strict_types=1);

/*
 * Livello dati del catalogo: stanze, immobili, geografia (quartieri/poli) e
 * accessori. Funzioni procedurali (stile phase1) che replicano la logica delle
 * repository della Fase 2.
 */

/* =====================================================================
 * STANZE (rooms)
 * ===================================================================== */

/**
 * Ricerca stanze (room-centric) con filtri opzionali.
 * Filtri: q, neighborhood_id, type, price_min, price_max, furnished, sort.
 * Il filtro pole_id è gestito a livello applicativo (ZoneEstimates).
 */
function rooms_search(array $filters = []): array
{
    // Solo unità pubblicabili: disponibili manualmente e non prenotate.
    $where = ['r.is_available = 1', "r.status = 'available'"];
    $params = [];

    $priceMin = isset($filters['price_min']) && is_numeric($filters['price_min']) ? max(0.0, (float) $filters['price_min']) : null;
    $priceMax = isset($filters['price_max']) && is_numeric($filters['price_max']) ? max(0.0, (float) $filters['price_max']) : null;
    if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
        [$priceMin, $priceMax] = [$priceMax, $priceMin];
    }

    if (!empty($filters['q'])) {
        $where[] = '(p.title LIKE :q_title OR n.name LIKE :q_neighborhood OR p.address LIKE :q_address OR r.name LIKE :q_room)';
        $query = '%' . $filters['q'] . '%';
        $params['q_title'] = $query;
        $params['q_neighborhood'] = $query;
        $params['q_address'] = $query;
        $params['q_room'] = $query;
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
    if (!empty($filters['furnished'])) {
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

    $sql = 'SELECT r.id, r.name, r.type, r.price_monthly, r.expenses_included, r.created_at, r.status,
                   p.id AS property_id, p.title AS property_title, p.address, p.house_number,
                   n.name AS neighborhood_name, n.id AS neighborhood_id, n.code AS neighborhood_code,
                   (SELECT pi.filename FROM property_images pi WHERE pi.property_id = p.id
                      ORDER BY pi.is_cover DESC, pi.id ASC LIMIT 1) AS cover,
                   (SELECT MIN(php.distance_minutes) FROM property_has_poles php WHERE php.property_id = p.id) AS min_distance,
                   (SELECT AVG(rv.rating) FROM reviews rv WHERE rv.room_id = r.id AND rv.status = \'published\') AS rating_avg,
                   (SELECT COUNT(*) FROM reviews rv2 WHERE rv2.room_id = r.id AND rv2.status = \'published\') AS rating_count
            FROM rooms r
            JOIN properties p ON p.id = r.property_id
            JOIN neighborhoods n ON n.id = p.neighborhood_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY ' . $order;

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function rooms_count_available(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM rooms WHERE is_available = 1 AND status = 'available'")->fetchColumn();
}

/** Dettaglio completo di una stanza (con immobile, quartiere, proprietario). */
function room_find(int $id): ?array
{
    $stmt = db()->prepare(
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

function rooms_by_property(int $propertyId): array
{
    $stmt = db()->prepare(
        'SELECT id, name, type, price_monthly, deposit_months, expenses_included, contract_type, is_available, status
         FROM rooms WHERE property_id = :pid ORDER BY price_monthly ASC'
    );
    $stmt->execute(['pid' => $propertyId]);
    return $stmt->fetchAll();
}

function room_amenities(int $roomId): array
{
    $stmt = db()->prepare(
        'SELECT a.id, a.code, a.name, a.icon
         FROM room_has_amenities rha JOIN amenities a ON a.id = rha.amenity_id
         WHERE rha.room_id = :rid ORDER BY a.name ASC'
    );
    $stmt->execute(['rid' => $roomId]);
    return $stmt->fetchAll();
}

function room_amenity_ids(int $roomId): array
{
    $stmt = db()->prepare('SELECT amenity_id FROM room_has_amenities WHERE room_id = :rid');
    $stmt->execute(['rid' => $roomId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function room_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO rooms (property_id, name, type, price_monthly, deposit_months, expenses_included, contract_type, is_available)
         VALUES (:property_id, :name, :type, :price_monthly, :deposit_months, :expenses_included, :contract_type, :is_available)'
    );
    $stmt->execute($data);
    return (int) db()->lastInsertId();
}

function room_update(int $id, array $data): void
{
    $data['id'] = $id;
    $stmt = db()->prepare(
        'UPDATE rooms SET name = :name, type = :type, price_monthly = :price_monthly,
                deposit_months = :deposit_months, expenses_included = :expenses_included,
                contract_type = :contract_type, is_available = :is_available
         WHERE id = :id'
    );
    $stmt->execute($data);
}

function room_delete(int $id): void
{
    db()->prepare('DELETE FROM rooms WHERE id = :id')->execute(['id' => $id]);
}

function room_set_amenities(int $roomId, array $amenityIds): void
{
    db()->prepare('DELETE FROM room_has_amenities WHERE room_id = :rid')->execute(['rid' => $roomId]);
    if ($amenityIds === []) {
        return;
    }
    $stmt = db()->prepare('INSERT IGNORE INTO room_has_amenities (room_id, amenity_id) VALUES (:rid, :aid)');
    foreach ($amenityIds as $aid) {
        $aid = (int) $aid;
        if ($aid > 0) {
            $stmt->execute(['rid' => $roomId, 'aid' => $aid]);
        }
    }
}

/* Ricalcola il numero di stanze totali di un immobile. */
function property_refresh_room_count(int $propertyId): void
{
    if ($propertyId <= 0) {
        return;
    }
    $stmt = db()->prepare(
        'UPDATE properties SET total_rooms = (SELECT COUNT(*) FROM rooms WHERE property_id = :count_id) WHERE id = :target_id'
    );
    $stmt->execute(['count_id' => $propertyId, 'target_id' => $propertyId]);
}

/* =====================================================================
 * IMMOBILI (properties)
 * ===================================================================== */

function property_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT p.*, n.name AS neighborhood_name, n.code AS neighborhood_code,
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

function properties_by_landlord(int $landlordId): array
{
    $stmt = db()->prepare(
        'SELECT p.id, p.title, p.neighborhood_id, n.name AS neighborhood_name,
                (SELECT COUNT(*) FROM rooms r WHERE r.property_id = p.id) AS room_count,
                (SELECT pi.filename FROM property_images pi WHERE pi.property_id = p.id
                   ORDER BY pi.is_cover DESC, pi.id ASC LIMIT 1) AS cover
         FROM properties p
         JOIN neighborhoods n ON n.id = p.neighborhood_id
         WHERE p.landlord_id = :lid ORDER BY p.created_at DESC'
    );
    $stmt->execute(['lid' => $landlordId]);
    return $stmt->fetchAll();
}

function properties_for_admin(): array
{
    return db()->query(
        'SELECT p.id, p.title, n.name AS neighborhood_name,
                CONCAT(u.first_name, " ", u.last_name) AS landlord_name,
                (SELECT COUNT(*) FROM rooms r WHERE r.property_id = p.id) AS room_count
         FROM properties p
         JOIN neighborhoods n ON n.id = p.neighborhood_id
         JOIN users u ON u.id = p.landlord_id
         ORDER BY p.created_at DESC'
    )->fetchAll();
}

function property_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO properties (landlord_id, neighborhood_id, title, description, address, house_number, postal_code, total_rooms, has_elevator, heating_type)
         VALUES (:landlord_id, :neighborhood_id, :title, :description, :address, :house_number, :postal_code, :total_rooms, :has_elevator, :heating_type)'
    );
    $stmt->execute([
        'landlord_id' => (int) $data['landlord_id'],
        'neighborhood_id' => (int) $data['neighborhood_id'],
        'title' => (string) $data['title'],
        'description' => ($data['description'] ?? '') !== '' ? $data['description'] : null,
        'address' => (string) $data['address'],
        'house_number' => ($data['house_number'] ?? '') !== '' ? $data['house_number'] : null,
        'postal_code' => (string) ($data['postal_code'] ?? '67100'),
        'total_rooms' => (int) ($data['total_rooms'] ?? 1),
        'has_elevator' => (int) ($data['has_elevator'] ?? 0),
        'heating_type' => (string) ($data['heating_type'] ?? 'autonomous'),
    ]);
    return (int) db()->lastInsertId();
}

function property_update(int $id, array $data): void
{
    $stmt = db()->prepare(
        'UPDATE properties SET neighborhood_id = :neighborhood_id, title = :title, description = :description,
                address = :address, house_number = :house_number, postal_code = :postal_code,
                total_rooms = :total_rooms, has_elevator = :has_elevator, heating_type = :heating_type
         WHERE id = :id'
    );
    $stmt->execute([
        'neighborhood_id' => (int) $data['neighborhood_id'],
        'title' => (string) $data['title'],
        'description' => ($data['description'] ?? '') !== '' ? $data['description'] : null,
        'address' => (string) $data['address'],
        'house_number' => ($data['house_number'] ?? '') !== '' ? $data['house_number'] : null,
        'postal_code' => (string) ($data['postal_code'] ?? '67100'),
        'total_rooms' => (int) ($data['total_rooms'] ?? 1),
        'has_elevator' => (int) ($data['has_elevator'] ?? 0),
        'heating_type' => (string) ($data['heating_type'] ?? 'autonomous'),
        'id' => $id,
    ]);
}

function property_set_landlord(int $id, int $landlordId): void
{
    db()->prepare('UPDATE properties SET landlord_id = :lid WHERE id = :id')->execute(['lid' => $landlordId, 'id' => $id]);
}

function property_delete(int $id): void
{
    db()->prepare('DELETE FROM properties WHERE id = :id')->execute(['id' => $id]);
}

/* ---- Immagini ---- */
function property_images(int $propertyId): array
{
    $stmt = db()->prepare('SELECT id, property_id, filename, is_cover, caption FROM property_images WHERE property_id = :pid ORDER BY is_cover DESC, id ASC');
    $stmt->execute(['pid' => $propertyId]);
    return $stmt->fetchAll();
}

function property_cover(int $propertyId): ?array
{
    $stmt = db()->prepare('SELECT id, filename FROM property_images WHERE property_id = :pid AND is_cover = 1 LIMIT 1');
    $stmt->execute(['pid' => $propertyId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function property_find_image(int $imageId): ?array
{
    $stmt = db()->prepare('SELECT id, property_id, filename, is_cover, caption FROM property_images WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $imageId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function property_add_image(int $propertyId, string $filename, bool $isCover, ?string $caption): int
{
    db()->prepare('INSERT INTO property_images (property_id, filename, is_cover, caption) VALUES (:pid, :fn, :cover, :cap)')
        ->execute(['pid' => $propertyId, 'fn' => $filename, 'cover' => $isCover ? 1 : 0, 'cap' => $caption]);
    return (int) db()->lastInsertId();
}

function property_set_cover(int $propertyId, int $imageId): void
{
    $db = db();
    $db->prepare('UPDATE property_images SET is_cover = 0 WHERE property_id = :pid')->execute(['pid' => $propertyId]);
    $db->prepare('UPDATE property_images SET is_cover = 1 WHERE id = :id AND property_id = :pid')->execute(['id' => $imageId, 'pid' => $propertyId]);
}

function property_delete_image(int $imageId): void
{
    db()->prepare('DELETE FROM property_images WHERE id = :id')->execute(['id' => $imageId]);
}

/* ---- Distanze dai poli ---- */
function property_poles(int $propertyId): array
{
    $stmt = db()->prepare(
        'SELECT php.pole_id, php.distance_minutes, php.transit_type, up.name AS pole_name
         FROM property_has_poles php JOIN university_poles up ON up.id = php.pole_id
         WHERE php.property_id = :pid ORDER BY php.distance_minutes ASC, up.name ASC'
    );
    $stmt->execute(['pid' => $propertyId]);
    return $stmt->fetchAll();
}

function property_set_pole(int $propertyId, int $poleId, int $minutes, string $transit): void
{
    db()->prepare(
        'INSERT INTO property_has_poles (property_id, pole_id, distance_minutes, transit_type)
         VALUES (:pid, :pole, :min, :transit)
         ON DUPLICATE KEY UPDATE distance_minutes = VALUES(distance_minutes), transit_type = VALUES(transit_type)'
    )->execute(['pid' => $propertyId, 'pole' => $poleId, 'min' => $minutes, 'transit' => $transit]);
}

function property_remove_pole(int $propertyId, int $poleId): void
{
    db()->prepare('DELETE FROM property_has_poles WHERE property_id = :pid AND pole_id = :pole')
        ->execute(['pid' => $propertyId, 'pole' => $poleId]);
}

/* =====================================================================
 * GEOGRAFIA (neighborhoods, university_poles)
 * ===================================================================== */

function neighborhoods_all(): array
{
    return db()->query('SELECT id, code, name, description FROM neighborhoods ORDER BY name ASC')->fetchAll();
}

function poles_all(): array
{
    return db()->query('SELECT id, code, name, description FROM university_poles ORDER BY name ASC')->fetchAll();
}

function neighborhood_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, code, name, description FROM neighborhoods WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function neighborhood_code_exists(string $code, int $ignoreId = 0): bool
{
    $stmt = db()->prepare('SELECT 1 FROM neighborhoods WHERE code = :code AND id <> :id LIMIT 1');
    $stmt->execute(['code' => $code, 'id' => $ignoreId]);
    return (bool) $stmt->fetchColumn();
}

function neighborhood_in_use(int $id): bool
{
    $stmt = db()->prepare('SELECT 1 FROM properties WHERE neighborhood_id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return (bool) $stmt->fetchColumn();
}

function neighborhood_create(string $code, string $name, ?string $description): void
{
    db()->prepare('INSERT INTO neighborhoods (code, name, description) VALUES (:code, :name, :desc)')
        ->execute(['code' => $code, 'name' => $name, 'desc' => $description]);
}

function neighborhood_update(int $id, string $code, string $name, ?string $description): void
{
    db()->prepare('UPDATE neighborhoods SET code = :code, name = :name, description = :desc WHERE id = :id')
        ->execute(['code' => $code, 'name' => $name, 'desc' => $description, 'id' => $id]);
}

function neighborhood_delete(int $id): void
{
    db()->prepare('DELETE FROM neighborhoods WHERE id = :id')->execute(['id' => $id]);
}

function pole_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, code, name, description FROM university_poles WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function pole_code_exists(string $code, int $ignoreId = 0): bool
{
    $stmt = db()->prepare('SELECT 1 FROM university_poles WHERE code = :code AND id <> :id LIMIT 1');
    $stmt->execute(['code' => $code, 'id' => $ignoreId]);
    return (bool) $stmt->fetchColumn();
}

function pole_in_use(int $id): bool
{
    $stmt = db()->prepare('SELECT 1 FROM property_has_poles WHERE pole_id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    return (bool) $stmt->fetchColumn();
}

function pole_create(string $code, string $name, ?string $description): void
{
    db()->prepare('INSERT INTO university_poles (code, name, description) VALUES (:code, :name, :desc)')
        ->execute(['code' => $code, 'name' => $name, 'desc' => $description]);
}

function pole_update(int $id, string $code, string $name, ?string $description): void
{
    db()->prepare('UPDATE university_poles SET code = :code, name = :name, description = :desc WHERE id = :id')
        ->execute(['code' => $code, 'name' => $name, 'desc' => $description, 'id' => $id]);
}

function pole_delete(int $id): void
{
    db()->prepare('DELETE FROM university_poles WHERE id = :id')->execute(['id' => $id]);
}

/* =====================================================================
 * ACCESSORI (amenities)
 * ===================================================================== */

function amenities_all(): array
{
    return db()->query('SELECT id, code, name, icon FROM amenities ORDER BY name ASC')->fetchAll();
}

function amenity_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, code, name, icon FROM amenities WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function amenity_code_exists(string $code, int $ignoreId = 0): bool
{
    $stmt = db()->prepare('SELECT 1 FROM amenities WHERE code = :code AND id <> :id LIMIT 1');
    $stmt->execute(['code' => $code, 'id' => $ignoreId]);
    return (bool) $stmt->fetchColumn();
}

function amenity_create(string $code, string $name, ?string $icon): void
{
    db()->prepare('INSERT INTO amenities (code, name, icon) VALUES (:code, :name, :icon)')
        ->execute(['code' => $code, 'name' => $name, 'icon' => $icon]);
}

function amenity_update(int $id, string $code, string $name, ?string $icon): void
{
    db()->prepare('UPDATE amenities SET code = :code, name = :name, icon = :icon WHERE id = :id')
        ->execute(['code' => $code, 'name' => $name, 'icon' => $icon, 'id' => $id]);
}

function amenity_delete(int $id): void
{
    db()->prepare('DELETE FROM amenities WHERE id = :id')->execute(['id' => $id]);
}
