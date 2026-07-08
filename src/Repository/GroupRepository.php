<?php

declare(strict_types=1);

class GroupRepository extends Repository
{
    public function all(): array
    {
        return $this->db()->query(
            'SELECT g.id, g.code, g.name, g.description, g.is_system,
                    (SELECT COUNT(*) FROM users_has_groups uhg WHERE uhg.group_id = g.id) AS member_count
             FROM user_groups g ORDER BY g.name ASC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM user_groups WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function codeExists(string $code, int $exceptId = 0): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM user_groups WHERE code = :code AND id <> :id LIMIT 1');
        $stmt->execute(['code' => $code, 'id' => $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(string $code, string $name, ?string $description): int
    {
        $stmt = $this->db()->prepare('INSERT INTO user_groups (code, name, description) VALUES (:c, :n, :d)');
        $stmt->execute(['c' => $code, 'n' => $name, 'd' => $description]);
        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, string $code, string $name, ?string $description): void
    {
        $stmt = $this->db()->prepare('UPDATE user_groups SET code = :c, name = :n, description = :d WHERE id = :id');
        $stmt->execute(['c' => $code, 'n' => $name, 'd' => $description, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db()->prepare('DELETE FROM user_groups WHERE id = :id')->execute(['id' => $id]);
    }

    public function isSystem(int $id): bool
    {
        $stmt = $this->db()->prepare('SELECT is_system FROM user_groups WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    /** @return int[] id dei servizi concessi al gruppo */
    public function serviceIds(int $groupId): array
    {
        $stmt = $this->db()->prepare('SELECT service_id FROM services_has_groups WHERE group_id = :gid');
        $stmt->execute(['gid' => $groupId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function setServices(int $groupId, array $serviceIds): void
    {
        $this->db()->prepare('DELETE FROM services_has_groups WHERE group_id = :gid')->execute(['gid' => $groupId]);
        if ($serviceIds === []) {
            return;
        }
        $stmt = $this->db()->prepare('INSERT IGNORE INTO services_has_groups (service_id, group_id) VALUES (:sid, :gid)');
        foreach ($serviceIds as $sid) {
            $stmt->execute(['sid' => (int) $sid, 'gid' => $groupId]);
        }
    }
}
