<?php

declare(strict_types=1);

/* Quartieri e poli universitari. */
class GeoRepository extends Repository
{
    /* ---------- Quartieri ---------- */
    public function allNeighborhoods(): array
    {
        return $this->db()->query('SELECT id, code, name, description FROM neighborhoods ORDER BY name ASC')->fetchAll();
    }

    public function findNeighborhood(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM neighborhoods WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function neighborhoodCodeExists(string $code, int $exceptId = 0): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM neighborhoods WHERE code = :code AND id <> :id LIMIT 1');
        $stmt->execute(['code' => $code, 'id' => $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    public function createNeighborhood(string $code, string $name, ?string $description): int
    {
        $stmt = $this->db()->prepare('INSERT INTO neighborhoods (code, name, description) VALUES (:c, :n, :d)');
        $stmt->execute(['c' => $code, 'n' => $name, 'd' => $description]);
        return (int) $this->db()->lastInsertId();
    }

    public function updateNeighborhood(int $id, string $code, string $name, ?string $description): void
    {
        $stmt = $this->db()->prepare('UPDATE neighborhoods SET code = :c, name = :n, description = :d WHERE id = :id');
        $stmt->execute(['c' => $code, 'n' => $name, 'd' => $description, 'id' => $id]);
    }

    public function deleteNeighborhood(int $id): void
    {
        $this->db()->prepare('DELETE FROM neighborhoods WHERE id = :id')->execute(['id' => $id]);
    }

    public function neighborhoodInUse(int $id): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM properties WHERE neighborhood_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }

    /* ---------- Poli ---------- */
    public function allPoles(): array
    {
        return $this->db()->query('SELECT id, code, name, description FROM university_poles ORDER BY name ASC')->fetchAll();
    }

    public function findPole(int $id): ?array
    {
        $stmt = $this->db()->prepare('SELECT * FROM university_poles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function poleCodeExists(string $code, int $exceptId = 0): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM university_poles WHERE code = :code AND id <> :id LIMIT 1');
        $stmt->execute(['code' => $code, 'id' => $exceptId]);
        return (bool) $stmt->fetchColumn();
    }

    public function createPole(string $code, string $name, ?string $description): int
    {
        $stmt = $this->db()->prepare('INSERT INTO university_poles (code, name, description) VALUES (:c, :n, :d)');
        $stmt->execute(['c' => $code, 'n' => $name, 'd' => $description]);
        return (int) $this->db()->lastInsertId();
    }

    public function updatePole(int $id, string $code, string $name, ?string $description): void
    {
        $stmt = $this->db()->prepare('UPDATE university_poles SET code = :c, name = :n, description = :d WHERE id = :id');
        $stmt->execute(['c' => $code, 'n' => $name, 'd' => $description, 'id' => $id]);
    }

    public function deletePole(int $id): void
    {
        $this->db()->prepare('DELETE FROM university_poles WHERE id = :id')->execute(['id' => $id]);
    }

    public function poleInUse(int $id): bool
    {
        $stmt = $this->db()->prepare('SELECT 1 FROM property_has_poles WHERE pole_id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetchColumn();
    }
}
