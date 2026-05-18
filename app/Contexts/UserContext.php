<?php

class UserContext
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT Id, Surname, Name, Patronomyc, Phone, Password, Role FROM Users WHERE Id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findRoleAndNameById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT Role, Name, Surname FROM Users WHERE Id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->db->prepare('SELECT Id, Password, Role FROM Users WHERE Phone = ?');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findAll(): mysqli_result
    {
        return $this->db->query('SELECT * FROM Users ORDER BY Id');
    }

    public function findClients(): mysqli_result
    {
        return $this->db->query("SELECT Id, Name, Surname FROM Users WHERE Role = 'client'");
    }

    public function findCouriers(): mysqli_result
    {
        return $this->db->query("SELECT Id, Name, Surname FROM Users WHERE Role = 'courier'");
    }

    public function phoneExists(string $phone): bool
    {
        $stmt = $this->db->prepare('SELECT Id FROM Users WHERE Phone = ?');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }

    public function insert(string $surname, string $name, string $patronomyc, string $phone, string $hash, string $role): int
    {
        $stmt = $this->db->prepare('INSERT INTO Users (Surname, Name, Patronomyc, Phone, Password, Role) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssssss', $surname, $name, $patronomyc, $phone, $hash, $role);
        $stmt->execute();
        return (int) $stmt->insert_id;
    }

    public function updateProfile(int $id, string $name, string $surname, string $patronomyc): bool
    {
        $stmt = $this->db->prepare('UPDATE Users SET Name = ?, Surname = ?, Patronomyc = ? WHERE Id = ?');
        $stmt->bind_param('sssi', $name, $surname, $patronomyc, $id);
        return $stmt->execute();
    }

    public function updateProfileWithPassword(int $id, string $name, string $surname, string $patronomyc, string $hash): bool
    {
        $stmt = $this->db->prepare('UPDATE Users SET Name = ?, Surname = ?, Patronomyc = ?, Password = ? WHERE Id = ?');
        $stmt->bind_param('ssssi', $name, $surname, $patronomyc, $hash, $id);
        return $stmt->execute();
    }

    public function updateAdmin(int $id, string $surname, string $name, string $patronomyc, string $role): bool
    {
        $stmt = $this->db->prepare('UPDATE Users SET Surname = ?, Name = ?, Patronomyc = ?, Role = ? WHERE Id = ?');
        $stmt->bind_param('ssssi', $surname, $name, $patronomyc, $role, $id);
        return $stmt->execute();
    }

    public function updateAdminWithPassword(int $id, string $surname, string $name, string $patronomyc, string $hash, string $role): bool
    {
        $stmt = $this->db->prepare('UPDATE Users SET Surname = ?, Name = ?, Patronomyc = ?, Password = ?, Role = ? WHERE Id = ?');
        $stmt->bind_param('sssssi', $surname, $name, $patronomyc, $hash, $role, $id);
        return $stmt->execute();
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM Users WHERE Id = ?');
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function findClientOrdersWithDishes(int $clientId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                o.Id, o.TotalSum, o.Address, o.Status, o.IdCourier,
                GROUP_CONCAT(CONCAT(d.Name, ' (', od.Quantity, 'шт)') SEPARATOR ', ') AS Dishes
            FROM Orders o
            LEFT JOIN OrdersDishes od ON od.IdOrder = o.Id
            LEFT JOIN Dishes d ON d.Id = od.IdDishes
            WHERE o.IdClient = ?
            GROUP BY o.Id
            ORDER BY o.Id DESC
        ");
        $stmt->bind_param('i', $clientId);
        $stmt->execute();
        $rows = [];
        $res  = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
}
