<?php

class DishContext
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function findAll(): mysqli_result
    {
        return $this->db->query('SELECT * FROM Dishes ORDER BY Name ASC');
    }

    public function findAllById(): mysqli_result
    {
        return $this->db->query('SELECT * FROM Dishes ORDER BY Id');
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
        $types        = str_repeat('i', count($ids));
        $stmt         = $this->db->prepare("SELECT * FROM Dishes WHERE Id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function findForSelect(): mysqli_result
    {
        return $this->db->query('SELECT Id, Name, Price FROM Dishes');
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
        $stmt = $this->db->prepare("INSERT INTO Dishes (`Name`, `Сompound`, `Price`, `Img`) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssds', $name, $compound, $price, $imgName);
        return $stmt->execute();
    }

    public function update(int $id, string $name, string $compound, float $price, string $imgPath): bool
    {
        $stmt = $this->db->prepare("UPDATE Dishes SET `Name` = ?, `Сompound` = ?, `Price` = ?, `Img` = ? WHERE `Id` = ?");
        $stmt->bind_param('ssdsi', $name, $compound, $price, $imgPath, $id);
        return $stmt->execute();
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM Dishes WHERE Id = ?');
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
