<?php

class OrderContext
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function getDb(): mysqli
    {
        return $this->db;
    }

    public function findLeastBusyCourierId(): int
    {
        $res = $this->db->query("
            SELECT u.Id, COUNT(o.Id) AS orders_count
            FROM Users u
            LEFT JOIN Orders o ON o.IdCourier = u.Id
            WHERE u.Role = 'courier'
            GROUP BY u.Id
            ORDER BY orders_count ASC
            LIMIT 1
        ");
        $row = $res ? $res->fetch_assoc() : null;
        return $row ? (int) $row['Id'] : 0;
    }

    public function findAllForAdmin(): mysqli_result
    {
        return $this->db->query("
            SELECT
                o.Id, o.TotalSum, o.Address, o.Status,
                c.Name AS ClientName, c.Surname AS ClientSurname,
                w.Name AS CourierName, w.Surname AS CourierSurname,
                GROUP_CONCAT(CONCAT(d.Name, ' (', od.Quantity, ')') SEPARATOR ', ') AS Dishes
            FROM Orders o
            JOIN Users c ON c.Id = o.IdClient
            JOIN Users w ON w.Id = o.IdCourier
            LEFT JOIN OrdersDishes od ON od.IdOrder = o.Id
            LEFT JOIN Dishes d ON d.Id = od.IdDishes
            GROUP BY o.Id
            ORDER BY o.Id DESC
        ");
    }

    public function findAllByCourier(int $courierId): mysqli_result
    {
        $stmt = $this->db->prepare("
            SELECT
                o.Id, o.TotalSum, o.Address, o.Status,
                c.Name AS ClientName, c.Surname AS ClientSurname,
                GROUP_CONCAT(CONCAT(d.Name, ' (', od.Quantity, ')') SEPARATOR ', ') AS Dishes
            FROM Orders o
            JOIN Users c ON c.Id = o.IdClient
            LEFT JOIN OrdersDishes od ON od.IdOrder = o.Id
            LEFT JOIN Dishes d ON d.Id = od.IdDishes
            WHERE o.IdCourier = ?
            GROUP BY o.Id
            ORDER BY o.Id DESC
        ");
        $stmt->bind_param('i', $courierId);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM Orders WHERE Id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc() ?: null;
    }

    public function findDishQuantities(int $orderId): array
    {
        $stmt = $this->db->prepare('SELECT IdDishes, Quantity FROM OrdersDishes WHERE IdOrder = ?');
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $map = [];
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $map[(int) $row['IdDishes']] = (int) $row['Quantity'];
        }
        return $map;
    }

    public function insertOrder(int $clientId, int $courierId, float $total, string $address, string $status): int
    {
        $stmt = $this->db->prepare('INSERT INTO Orders (IdClient, IdCourier, TotalSum, Address, Status) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('iidss', $clientId, $courierId, $total, $address, $status);
        $stmt->execute();
        return (int) $stmt->insert_id;
    }

    public function insertOrderDish(int $orderId, int $dishId, int $qty): bool
    {
        $stmt = $this->db->prepare('INSERT INTO OrdersDishes (IdOrder, IdDishes, Quantity) VALUES (?, ?, ?)');
        $stmt->bind_param('iii', $orderId, $dishId, $qty);
        return $stmt->execute();
    }

    public function updateTotal(int $orderId, float $total): bool
    {
        $stmt = $this->db->prepare('UPDATE Orders SET TotalSum = ? WHERE Id = ?');
        $stmt->bind_param('di', $total, $orderId);
        return $stmt->execute();
    }

    public function updateOrderFull(int $orderId, float $total, string $address, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE Orders SET TotalSum = ?, Address = ?, Status = ? WHERE Id = ?');
        $stmt->bind_param('dssi', $total, $address, $status, $orderId);
        return $stmt->execute();
    }

    public function updateStatusByCourier(int $orderId, int $courierId, string $status): bool
    {
        $stmt = $this->db->prepare('UPDATE Orders SET Status = ? WHERE Id = ? AND IdCourier = ?');
        $stmt->bind_param('sii', $status, $orderId, $courierId);
        return $stmt->execute();
    }

    public function deleteOrderDishes(int $orderId): bool
    {
        $stmt = $this->db->prepare('DELETE FROM OrdersDishes WHERE IdOrder = ?');
        $stmt->bind_param('i', $orderId);
        return $stmt->execute();
    }

    public function deleteById(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM Orders WHERE Id = ?');
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
