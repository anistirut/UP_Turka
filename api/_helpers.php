<?php

declare(strict_types=1);

function api_json(int $status, $data): void
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function api_error(int $status, string $message, array $extra = []): void
{
    api_json($status, array_merge(['success' => false, 'message' => $message], $extra));
}

function api_ok($data = null): void
{
    if ($data === null) {
        api_json(200, ['success' => true]);
    }
    api_json(200, array_merge(['success' => true], (array) $data));
}

function api_read_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function get_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function api_current_user_id(): ?int
{
    if (!isset($_SESSION['user']) || $_SESSION['user'] == -1) {
        return null;
    }
    return (int) $_SESSION['user'];
}

function api_require_auth(UserContext $userCtx): array
{
    $uid = api_current_user_id();
    if (!$uid) {
        api_error(401, 'Не авторизован');
    }

    // Кэш данных пользователя в сессии — исключает лишний SQL-запрос
    // на каждом защищённом эндпоинте в рамках одной сессии.
    if (isset($_SESSION['user_data']) && is_array($_SESSION['user_data'])) {
        return $_SESSION['user_data'];
    }

    $row = $userCtx->findRoleAndNameById($uid);
    if (!$row) {
        api_error(401, 'Не авторизован');
    }

    $userData = [
        'Id'      => $uid,
        'Role'    => (string) $row['Role'],
        'Name'    => (string) $row['Name'],
        'Surname' => (string) $row['Surname'],
    ];

    $_SESSION['user_data'] = $userData;
    return $userData;
}

function api_require_role(UserContext $userCtx, array $roles): array
{
    $user = api_require_auth($userCtx);
    if (!in_array($user['Role'], $roles, true)) {
        api_error(403, 'Доступ запрещён');
    }
    return $user;
}
