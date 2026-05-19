<?php

session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../settings/connect_database.php';
require_once __DIR__ . '/../app/Contexts/UserContext.php';
require_once __DIR__ . '/../app/Contexts/LogContext.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

$userCtx = new UserContext($mysqli);
$logCtx = new LogContext($mysqli);
$authController = new AuthController($userCtx);
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

$res = $authController->attemptLogin($_POST['phone'] ?? '', $_POST['password'] ?? '');

if ($res['success']) {
    $_SESSION['user'] = $res['user_id'];
    $logCtx->logAction((int) $res['user_id'], $ip, 'Авторизация (веб-форма)');
    switch ($res['role']) {
        case 'client':
            header('Location: ../client/client.php');
            exit;
        case 'courier':
            header('Location: ../courier/courier.php');
            exit;
        case 'admin':
            header('Location: ../admin/admin.php');
            exit;
    }
} else {
    $logCtx->logError(null, $ip, 'Неудачная авторизация (веб-форма): ' . $res['message']);
}
