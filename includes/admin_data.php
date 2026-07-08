<?php

declare(strict_types=1);

/*
 * Livello dati dell'amministrazione: utenti, gruppi e servizi
 * (modello users-groups-services). Funzioni procedurali (stile phase1).
 */

/* =====================================================================
 * UTENTI
 * ===================================================================== */

function users_all_admin(): array
{
    return db()->query(
        'SELECT u.id, u.email, u.first_name, u.last_name, u.status, u.created_at,
                (SELECT GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ", ")
                   FROM users_has_groups uhg JOIN user_groups g ON g.id = uhg.group_id
                  WHERE uhg.user_id = u.id) AS groups
         FROM users u
         WHERE u.deleted_at IS NULL
         ORDER BY u.created_at DESC'
    )->fetchAll();
}

function user_find_admin(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, email, first_name, last_name, phone, status FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function user_group_ids(int $userId): array
{
    $stmt = db()->prepare('SELECT group_id FROM users_has_groups WHERE user_id = :id');
    $stmt->execute(['id' => $userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function user_set_groups(int $userId, array $groupIds): void
{
    db()->prepare('DELETE FROM users_has_groups WHERE user_id = :id')->execute(['id' => $userId]);
    if ($groupIds === []) {
        return;
    }
    $stmt = db()->prepare('INSERT IGNORE INTO users_has_groups (user_id, group_id) VALUES (:uid, :gid)');
    foreach ($groupIds as $gid) {
        $gid = (int) $gid;
        if ($gid > 0) {
            $stmt->execute(['uid' => $userId, 'gid' => $gid]);
        }
    }
}

function user_create_admin(array $data): int
{
    db()->prepare(
        'INSERT INTO users (email, password_hash, first_name, last_name, phone, status, email_verified_at)
         VALUES (:email, :password_hash, :first_name, :last_name, :phone, :status, NOW())'
    )->execute([
        'email' => strtolower((string) $data['email']),
        'password_hash' => (string) $data['password_hash'],
        'first_name' => (string) $data['first_name'],
        'last_name' => (string) $data['last_name'],
        'phone' => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
        'status' => (string) $data['status'],
    ]);
    return (int) db()->lastInsertId();
}

function user_update_admin(int $id, array $data): void
{
    db()->prepare(
        'UPDATE users SET email = :email, first_name = :first_name, last_name = :last_name, phone = :phone, status = :status WHERE id = :id'
    )->execute([
        'email' => strtolower((string) $data['email']),
        'first_name' => (string) $data['first_name'],
        'last_name' => (string) $data['last_name'],
        'phone' => ($data['phone'] ?? '') !== '' ? $data['phone'] : null,
        'status' => (string) $data['status'],
        'id' => $id,
    ]);
}

function user_update_password_admin(int $id, string $passwordHash): void
{
    db()->prepare('UPDATE users SET password_hash = :ph WHERE id = :id')->execute(['ph' => $passwordHash, 'id' => $id]);
}

function user_soft_delete(int $id): void
{
    db()->prepare('UPDATE users SET deleted_at = NOW() WHERE id = :id')->execute(['id' => $id]);
}

function landlords_for_select(): array
{
    return db()->query(
        'SELECT DISTINCT u.id, CONCAT(u.first_name, " ", u.last_name, " (", u.email, ")") AS name
         FROM users u
         JOIN users_has_groups uhg ON uhg.user_id = u.id
         JOIN user_groups g ON g.id = uhg.group_id
         WHERE g.code = "landlord" AND u.deleted_at IS NULL AND u.status = "active"
         ORDER BY u.last_name ASC, u.first_name ASC'
    )->fetchAll();
}

/* =====================================================================
 * GRUPPI
 * ===================================================================== */

function groups_all(): array
{
    return db()->query(
        'SELECT g.id, g.code, g.name, g.description, g.is_system,
                (SELECT COUNT(*) FROM users_has_groups uhg WHERE uhg.group_id = g.id) AS member_count
         FROM user_groups g ORDER BY g.name ASC'
    )->fetchAll();
}

function group_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, code, name, description, is_system FROM user_groups WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function group_code_exists(string $code, int $ignoreId = 0): bool
{
    $stmt = db()->prepare('SELECT 1 FROM user_groups WHERE code = :code AND id <> :id LIMIT 1');
    $stmt->execute(['code' => $code, 'id' => $ignoreId]);
    return (bool) $stmt->fetchColumn();
}

function group_create(string $code, string $name, ?string $description): void
{
    db()->prepare('INSERT INTO user_groups (code, name, description) VALUES (:code, :name, :desc)')
        ->execute(['code' => $code, 'name' => $name, 'desc' => $description]);
}

function group_update(int $id, string $code, string $name, ?string $description): void
{
    db()->prepare('UPDATE user_groups SET code = :code, name = :name, description = :desc WHERE id = :id')
        ->execute(['code' => $code, 'name' => $name, 'desc' => $description, 'id' => $id]);
}

function group_delete(int $id): void
{
    db()->prepare('DELETE FROM user_groups WHERE id = :id')->execute(['id' => $id]);
}

/* =====================================================================
 * SERVIZI
 * ===================================================================== */

function services_all_admin(): array
{
    return db()->query(
        'SELECT s.id, s.code, s.name, s.area, s.path, s.http_method, s.is_menu_item, s.menu_order, s.is_active,
                (SELECT COUNT(*) FROM services_has_groups shg WHERE shg.service_id = s.id) AS group_count
         FROM services s ORDER BY s.area ASC, s.menu_order ASC, s.name ASC'
    )->fetchAll();
}

function service_find(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, code, name, description, area, path, http_method, is_menu_item, menu_order, is_active FROM services WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function service_code_exists(string $code, int $ignoreId = 0): bool
{
    $stmt = db()->prepare('SELECT 1 FROM services WHERE code = :code AND id <> :id LIMIT 1');
    $stmt->execute(['code' => $code, 'id' => $ignoreId]);
    return (bool) $stmt->fetchColumn();
}

function service_group_ids(int $serviceId): array
{
    $stmt = db()->prepare('SELECT group_id FROM services_has_groups WHERE service_id = :id');
    $stmt->execute(['id' => $serviceId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function service_set_groups(int $serviceId, array $groupIds): void
{
    db()->prepare('DELETE FROM services_has_groups WHERE service_id = :id')->execute(['id' => $serviceId]);
    if ($groupIds === []) {
        return;
    }
    $stmt = db()->prepare('INSERT IGNORE INTO services_has_groups (service_id, group_id) VALUES (:sid, :gid)');
    foreach ($groupIds as $gid) {
        $gid = (int) $gid;
        if ($gid > 0) {
            $stmt->execute(['sid' => $serviceId, 'gid' => $gid]);
        }
    }
}

function service_create(array $data): int
{
    db()->prepare(
        'INSERT INTO services (code, name, description, area, path, http_method, is_menu_item, menu_order, is_active)
         VALUES (:code, :name, :description, :area, :path, :http_method, :is_menu_item, :menu_order, :is_active)'
    )->execute([
        'code' => (string) $data['code'],
        'name' => (string) $data['name'],
        'description' => ($data['description'] ?? null),
        'area' => (string) $data['area'],
        'path' => ($data['path'] ?? '') !== '' ? $data['path'] : null,
        'http_method' => (string) $data['http_method'],
        'is_menu_item' => (int) $data['is_menu_item'],
        'menu_order' => (int) $data['menu_order'],
        'is_active' => (int) $data['is_active'],
    ]);
    return (int) db()->lastInsertId();
}

function service_update(int $id, array $data): void
{
    db()->prepare(
        'UPDATE services SET code = :code, name = :name, description = :description, area = :area,
                path = :path, http_method = :http_method, is_menu_item = :is_menu_item,
                menu_order = :menu_order, is_active = :is_active
         WHERE id = :id'
    )->execute([
        'code' => (string) $data['code'],
        'name' => (string) $data['name'],
        'description' => ($data['description'] ?? null),
        'area' => (string) $data['area'],
        'path' => ($data['path'] ?? '') !== '' ? $data['path'] : null,
        'http_method' => (string) $data['http_method'],
        'is_menu_item' => (int) $data['is_menu_item'],
        'menu_order' => (int) $data['menu_order'],
        'is_active' => (int) $data['is_active'],
        'id' => $id,
    ]);
}

function service_delete(int $id): void
{
    db()->prepare('DELETE FROM services WHERE id = :id')->execute(['id' => $id]);
}
