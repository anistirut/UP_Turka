<?php

require_once __DIR__ . '/CacheContext.php';

class ReportContext
{
    private mysqli       $db;
    private CacheContext $cache;

    public function __construct(mysqli $db, CacheContext $cache)
    {
        $this->db    = $db;
        $this->cache = $cache;
    }

    public function fetchUsersRows(): mysqli_result
    {
        return $this->db->query(
            'SELECT Id, Surname, Name, Patronomyc, Phone, Role FROM Users'
        );
    }

    public function fetchDishesRows(): mysqli_result
    {
        return $this->db->query(
            'SELECT Id, Name, `Сompound`, Price FROM Dishes'
        );
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

    public function fetchStats(): array
    {
        $cached = $this->cache->get('admin_stats');
        if ($cached !== null) {
            return $cached;
        }

        $row = $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM Orders)                          AS orders_count,
                (SELECT COUNT(*) FROM Dishes)                          AS dishes_count,
                (SELECT COUNT(*) FROM Users WHERE Role = 'client')     AS clients_count
        ")->fetch_assoc();

        $result = [
            'orders_count'  => (int) ($row['orders_count']  ?? 0),
            'dishes_count'  => (int) ($row['dishes_count']  ?? 0),
            'clients_count' => (int) ($row['clients_count'] ?? 0),
        ];

        $this->cache->set('admin_stats', $result, ttl: 60);
        return $result;
    }

    public function fetchAnalyticsCombined(): array
    {
        $cached = $this->cache->get('analytics_summary');
        if ($cached !== null) {
            return $cached;
        }

        $row = $this->db->query("
            SELECT
                (SELECT AVG(TotalSum) FROM Orders)  AS avg_check,
                (SELECT COUNT(*)      FROM Orders)  AS count_orders,
                (SELECT d.Name
                   FROM OrdersDishes od
                   JOIN Dishes d ON d.Id = od.IdDishes
                   GROUP BY od.IdDishes
                   ORDER BY SUM(od.Quantity) DESC
                   LIMIT 1)                         AS popular_name,
                (SELECT SUM(od2.Quantity)
                   FROM OrdersDishes od2
                   GROUP BY od2.IdDishes
                   ORDER BY SUM(od2.Quantity) DESC
                   LIMIT 1)                         AS popular_qty
        ")->fetch_assoc();

        $result = [
            'avg_check'    => (float) ($row['avg_check']    ?? 0),
            'count_orders' => (int)   ($row['count_orders'] ?? 0),
            'popular'      => $row['popular_name']
                ? ['Name' => $row['popular_name'], 'qty' => (int) $row['popular_qty']]
                : null,
        ];

        $this->cache->set('analytics_summary', $result, ttl: 300);
        return $result;
    }
}
