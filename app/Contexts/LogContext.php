<?php

class LogContext
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function logAction(?int $userId, string $ip, string $action): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO logs (user_id, ip, action) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iss', $userId, $ip, $action);
        $stmt->execute();
    }

    public function logError(?int $userId, string $ip, string $message): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO error_logs (user_id, ip, message) VALUES (?, ?, ?)'
        );
        $stmt->bind_param('iss', $userId, $ip, $message);
        $stmt->execute();
    }
}
