<?php

declare(strict_types=1);

/*
 * Accesso ai dati per utenti, gruppi e servizi (modello users-groups-services).
 */
class UserRepository extends Repository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db()->prepare(
            'SELECT id, email, password_hash, first_name, last_name, status, deleted_at
             FROM users
             WHERE email = :email
             LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user === false ? null : $user;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        return (bool) $stmt->fetchColumn();
    }

    /**
     * Crea un utente e ritorna il nuovo id.
     */
    public function create(array $data): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO users (email, password_hash, first_name, last_name, phone, status, email_verified_at)
             VALUES (:email, :password_hash, :first_name, :last_name, :phone, :status, :email_verified_at)'
        );
        $stmt->execute([
            'email' => $data['email'],
            'password_hash' => $data['password_hash'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'status' => $data['status'] ?? 'active',
            // Verifica email simulata (nessun mail server): attiva alla creazione.
            'email_verified_at' => ($data['status'] ?? 'active') === 'active' ? date('Y-m-d H:i:s') : null,
        ]);

        return (int) $this->db()->lastInsertId();
    }

    public function attachGroupByCode(int $userId, string $groupCode): void
    {
        $stmt = $this->db()->prepare(
            'INSERT IGNORE INTO users_has_groups (user_id, group_id)
             SELECT :user_id, g.id FROM user_groups AS g WHERE g.code = :code'
        );
        $stmt->execute(['user_id' => $userId, 'code' => $groupCode]);
    }

    public function updateLastLogin(int $userId): void
    {
        try {
            $stmt = $this->db()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
            $stmt->execute(['id' => $userId]);
        } catch (Throwable $exception) {
            error_log('[MasterRent] Could not update last_login_at: ' . $exception->getMessage());
        }
    }

    public function groupsForUser(int $userId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT g.id, g.code, g.name, g.description
             FROM users AS u
             JOIN users_has_groups AS uhg ON uhg.user_id = u.id
             JOIN user_groups AS g ON g.id = uhg.group_id
             WHERE u.id = :user_id AND u.status = :status AND u.deleted_at IS NULL
             ORDER BY g.name ASC'
        );
        $stmt->execute(['user_id' => $userId, 'status' => 'active']);

        return $stmt->fetchAll();
    }

    public function servicesForUser(int $userId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT DISTINCT s.id, s.code, s.name, s.description, s.area, s.path,
                    s.http_method, s.is_menu_item, s.menu_order
             FROM users AS u
             JOIN users_has_groups AS uhg ON uhg.user_id = u.id
             JOIN user_groups AS g ON g.id = uhg.group_id
             JOIN services_has_groups AS shg ON shg.group_id = g.id
             JOIN services AS s ON s.id = shg.service_id
             WHERE u.id = :user_id AND u.status = :status AND u.deleted_at IS NULL
               AND s.is_active = 1
             ORDER BY s.area ASC, s.menu_order ASC, s.name ASC'
        );
        $stmt->execute(['user_id' => $userId, 'status' => 'active']);

        return $stmt->fetchAll();
    }

    /* ---------------------------------------------------------------------
     * Amministrazione utenti
     * ------------------------------------------------------------------- */

    public function all(): array
    {
        return $this->db()->query(
            'SELECT u.id, u.email, u.first_name, u.last_name, u.status, u.created_at,
                    (SELECT GROUP_CONCAT(g.name ORDER BY g.name SEPARATOR ", ")
                       FROM users_has_groups uhg JOIN user_groups g ON g.id = uhg.group_id
                       WHERE uhg.user_id = u.id) AS groups
             FROM users u WHERE u.deleted_at IS NULL ORDER BY u.created_at DESC'
        )->fetchAll();
    }

    public function landlordsForSelect(): array
    {
        return $this->db()->query(
            'SELECT u.id,
                    CONCAT(u.first_name, " ", u.last_name, " - ", u.email) AS name
             FROM users u
             JOIN users_has_groups uhg ON uhg.user_id = u.id
             JOIN user_groups g ON g.id = uhg.group_id
             WHERE g.code = "landlord" AND u.status = "active" AND u.deleted_at IS NULL
             ORDER BY u.last_name ASC, u.first_name ASC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $stmt = $this->db()->prepare(
            'UPDATE users SET email = :email, first_name = :first_name, last_name = :last_name,
                    phone = :phone, status = :status WHERE id = :id'
        );
        $stmt->execute([
            'email' => $data['email'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'status' => $data['status'],
            'id' => $id,
        ]);
    }

    public function updateProfile(int $id, string $firstName, string $lastName, string $phone): void
    {
        $stmt = $this->db()->prepare(
            'UPDATE users SET first_name = :fn, last_name = :ln, phone = :ph WHERE id = :id'
        );
        $stmt->execute(['fn' => $firstName, 'ln' => $lastName, 'ph' => $phone !== '' ? $phone : null, 'id' => $id]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = $this->db()->prepare('UPDATE users SET password_hash = :h WHERE id = :id');
        $stmt->execute(['h' => $passwordHash, 'id' => $id]);
    }

    public function softDelete(int $id): void
    {
        $stmt = $this->db()->prepare('UPDATE users SET deleted_at = NOW(), status = "disabled" WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function groupIdsForUser(int $userId): array
    {
        $stmt = $this->db()->prepare('SELECT group_id FROM users_has_groups WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function setGroups(int $userId, array $groupIds): void
    {
        $this->db()->prepare('DELETE FROM users_has_groups WHERE user_id = :uid')->execute(['uid' => $userId]);
        if ($groupIds === []) {
            return;
        }
        $stmt = $this->db()->prepare('INSERT IGNORE INTO users_has_groups (user_id, group_id) VALUES (:uid, :gid)');
        foreach ($groupIds as $gid) {
            $stmt->execute(['uid' => $userId, 'gid' => (int) $gid]);
        }
    }

    public function createForAdmin(array $data, array $groupIds): int
    {
        $id = $this->create($data);
        $this->setGroups($id, $groupIds);
        return $id;
    }
}
