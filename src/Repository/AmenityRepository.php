<?php

declare(strict_types=1);

class AmenityRepository extends Repository
{
    public function all(): array
    {
        return $this->db()->query('SELECT id, code, name, icon FROM amenities ORDER BY name ASC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM amenities WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function codeExists(string $code, int $exceptId = 0): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM amenities WHERE code = :code AND id <> :id LIMIT 1');
        $stmt->execute(['code' => $code, 'id' => $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    public function create(string $code, string $name, ?string $icon): int
    {
        $stmt = $this->db()->prepare('INSERT INTO amenities (code, name, icon) VALUES (:c, :n, :i)');
        $stmt->execute(['c' => $code, 'n' => $name, 'i' => $icon]);
        return (int) $this->db()->lastInsertId();
    }

    public function update(int $id, string $code, string $name, ?string $icon): void
    {
        $stmt = $this->db()->prepare('UPDATE amenities SET code = :c, name = :n, icon = :i WHERE id = :id');
        $stmt->execute(['c' => $code, 'n' => $name, 'i' => $icon, 'id' => $id]);
    }

    public function delete(int $id): void
    {
        $this->db()->prepare('DELETE FROM amenities WHERE id = :id')->execute(['id' => $id]);
    }
}
