<?php

session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../settings/connect_database.php';
require_once __DIR__ . '/../app/Contexts/UserContext.php';
require_once __DIR__ . '/../app/Controllers/AuthController.php';

$userCtx        = new UserContext($mysqli);
$authController = new AuthController($userCtx);
$res = $authController->attemptLogin($_POST['phone'] ?? '', $_POST['password'] ?? '');

if ($res['success']) {
    $_SESSION['user'] = $res['user_id'];
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
}
