<?php

declare(strict_types=1);

class ServiceRepository extends Repository
{
    public function all(): array
    {
        return $this->db()->query(
            'SELECT s.id, s.code, s.name, s.area, s.path, s.http_method, s.is_menu_item, s.is_active,
                    (SELECT COUNT(*) FROM services_has_groups shg WHERE shg.service_id = s.id) AS group_count
             FROM services s ORDER BY s.area ASC, s.menu_order ASC, s.name ASC'
        )->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM services WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function codeExists(string $code, int $exceptId = 0): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM services WHERE code = :code AND id <> :id LIMIT 1');
        $stmt->execute(['code' => $code, 'id' => $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(array $d): int
    {
        $stmt = $this->db()->prepare(
            'INSERT INTO services (code, name, description, area, path, http_method, is_menu_item, menu_order, is_active)
             VALUES (:code, :name, :description, :area, :path, :http_method, :is_menu_item, :menu_order, :is_active)'
        );
        $stmt->execute($d);
        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, array $d): void
    {
        $d['id'] = $id;
        $stmt = $this->db()->prepare(
            'UPDATE services SET code = :code, name = :name, description = :description, area = :area,
                    path = :path, http_method = :http_method, is_menu_item = :is_menu_item,
                    menu_order = :menu_order, is_active = :is_active WHERE id = :id'
        );
        $stmt->execute($d);
    }

    public function delete(int $id): void
    {
        $this->db()->prepare('DELETE FROM services WHERE id = :id')->execute(['id' => $id]);
    }

    /** @return int[] id dei gruppi che hanno il servizio */
    public function groupIds(int $serviceId): array
    {
        $stmt = $this->db()->prepare('SELECT group_id FROM services_has_groups WHERE service_id = :sid');
        $stmt->execute(['sid' => $serviceId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function setGroups(int $serviceId, array $groupIds): void
    {
        $this->db()->prepare('DELETE FROM services_has_groups WHERE service_id = :sid')->execute(['sid' => $serviceId]);
        if ($groupIds === []) {
            return;
        }
        $stmt = $this->db()->prepare('INSERT IGNORE INTO services_has_groups (service_id, group_id) VALUES (:sid, :gid)');
        foreach ($groupIds as $gid) {
            $stmt->execute(['sid' => $serviceId, 'gid' => (int) $gid]);
        }
    }
}
