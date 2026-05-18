<?php

class ReportContext
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function fetchUsersRows(): mysqli_result
    {
        return $this->db->query('SELECT Id, Surname, Name, Patronomyc, Phone, Role FROM Users');
    }

    public function fetchDishesRows(): mysqli_result
    {
        return $this->db->query('SELECT Id, Name, `Сompound`, Price FROM Dishes');
    }

    public function fetchOrdersRows(): mysqli_result
    {
        return $this->db->query("
            SELECT
                o.Id,
                CONCAT(c.Surname, ' ', c.Name) AS Client,
                CONCAT(w.Surname, ' ', w.Name) AS Courier,
                o.TotalSum,
                CASE
                    WHEN o.Status = 'accepted'  THEN 'Принят'
                    WHEN o.Status = 'progress'  THEN 'Готовится'
                    WHEN o.Status = 'ready'     THEN 'Готов'
                    WHEN o.Status = 'delivery'  THEN 'В доставке'
                    WHEN o.Status = 'delivered' THEN 'Доставлен'
                    ELSE o.Status
                END AS Status,
                o.Address
            FROM Orders o
            JOIN Users c ON c.Id = o.IdClient
            JOIN Users w ON w.Id = o.IdCourier
        ");
    }

    public function fetchAvgCheck(): float
    {
        $row = $this->db->query('SELECT AVG(TotalSum) AS avg_check FROM Orders')->fetch_assoc();
        return $row ? (float) $row['avg_check'] : 0.0;
    }

    public function fetchPopularDish(): ?array
    {
        $row = $this->db->query("
            SELECT d.Name, SUM(od.Quantity) AS qty
            FROM OrdersDishes od
            JOIN Dishes d ON d.Id = od.IdDishes
            GROUP BY d.Id
            ORDER BY qty DESC
            LIMIT 1
        ")->fetch_assoc();
        return $row ?: null;
    }

    public function fetchOrdersCount(): int
    {
        $row = $this->db->query('SELECT COUNT(*) AS cnt FROM Orders')->fetch_assoc();
        return (int) ($row['cnt'] ?? 0);
    }
}
