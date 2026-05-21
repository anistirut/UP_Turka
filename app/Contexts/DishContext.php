<?php

require_once __DIR__ . '/CacheContext.php';

class DishContext
{
    private mysqli $db;
    private CacheContext $cache;

    public function __construct(mysqli $db, CacheContext $cache)
    {
        $this->db = $db;
        $this->cache = $cache;
    }

    public function findAll(): array
    {
        $cached = $this->cache->get('dishes_all');
        if ($cached !== null) {
            return $cached;
        }

        $rows = [];
        $res  = $this->db->query('SELECT * FROM Dishes ORDER BY Name ASC');
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        $this->cache->set('dishes_all', $rows, ttl: 60);
        return $rows;
    }

    public function findAllById(): array
    {
        $cached = $this->cache->get('dishes_all_by_id');
        if ($cached !== null) {
            return $cached;
        }

        $rows = [];
        $res  = $this->db->query('SELECT * FROM Dishes ORDER BY Id');
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        $this->cache->set('dishes_all_by_id', $rows, ttl: 60);
        return $rows;
    }

    public function findForSelect(): array
    {
        $cached = $this->cache->get('dishes_select');
        if ($cached !== null) {
            return $cached;
        }

        $rows = [];
        $res  = $this->db->query('SELECT Id, Name, Price FROM Dishes');
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        $this->cache->set('dishes_select', $rows, ttl: 60);
        return $rows;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM Dishes WHERE Id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findByIds(array $ids): mysqli_result|false
    {
        $ids = array_filter(array_map('intval', $ids));
        if (empty($ids)) {
            return false;
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        $stmt = $this->db->prepare("SELECT * FROM Dishes WHERE Id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function findDishPrice(int $id): ?float
    {
        $stmt = $this->db->prepare('SELECT Price FROM Dishes WHERE Id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ? (float) $row['Price'] : null;
    }

    public function insert(string $name, string $compound, float $price, string $imgName): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO Dishes (`Name`, `Сompound`, `Price`, `Img`) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('ssds', $name, $compound, $price, $imgName);
        $ok = $stmt->execute();

        if ($ok) {
            $this->cache->deleteByPrefix('dishes_');
        }

        return $ok;
    }

    public function update(int $id, string $name, string $compound, float $price, string $imgPath): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE Dishes SET `Name` = ?, `Сompound` = ?, `Price` = ?, `Img` = ? WHERE `Id` = ?"
        );
        $stmt->bind_param('ssdsi', $name, $compound, $price, $imgPath, $id);
        $ok = $stmt->execute();

        if ($ok) {
            $this->cache->deleteByPrefix('dishes_');
        }

        return $ok;
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM Dishes WHERE Id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();

        if ($ok) {
            $this->cache->deleteByPrefix('dishes_');
        }

        return $ok;
    }
}
