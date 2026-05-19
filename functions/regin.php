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

$res = $authController->registerClient(
    $_POST['surname'] ?? '',
    $_POST['name'] ?? '',
    $_POST['patronomyc'] ?? '',
    $_POST['phone'] ?? '',
    $_POST['password'] ?? '',
    $_POST['confirm_password'] ?? ''
);

if ($res['success']) {
    $_SESSION['user'] = $res['user_id'];
    $logCtx->logAction((int) $res['user_id'], $ip, 'Регистрация (веб-форма)');
    header('Location: ../client/client.php');
    exit;
} else {
    $logCtx->logError(null, $ip, 'Неудачная регистрация (веб-форма): ' . implode('; ', $res['errors']));
}
