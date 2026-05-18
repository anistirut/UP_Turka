<?php

require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/_helpers.php';

require_once __DIR__ . '/../app/Contexts/UserContext.php';
require_once __DIR__ . '/../app/Contexts/DishContext.php';
require_once __DIR__ . '/../app/Contexts/OrderContext.php';
require_once __DIR__ . '/../app/Contexts/ReportContext.php';

require_once __DIR__ . '/../app/Controllers/AuthController.php';
require_once __DIR__ . '/../app/Controllers/DishController.php';
require_once __DIR__ . '/../app/Controllers/UserController.php';
require_once __DIR__ . '/../app/Controllers/OrderController.php';
require_once __DIR__ . '/../app/Controllers/ReportController.php';

// Контексты создаются один раз и переиспользуются во всех запросах
$userCtx   = new UserContext($mysqli);
$dishCtx   = new DishContext($mysqli);
$orderCtx  = new OrderContext($mysqli);
$reportCtx = new ReportContext($mysqli);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = $_SERVER['REQUEST_URI'] ?? '/';

if (isset($_GET['r']) && $_GET['r'] !== '') {
    $path = (string) $_GET['r'];
} elseif (!empty($_SERVER['PATH_INFO'])) {
    $path = (string) $_SERVER['PATH_INFO'];
} else {
    $path = (string) (parse_url($uri, PHP_URL_PATH) ?: '/');
    $path = preg_replace('#^/[^/]+/api\.php#', '', $path);
}

$path = preg_replace('#^/api(\.php)?#', '', $path);
$path = rtrim($path, '/');
if ($path === '') {
    $path = '/';
}

try {
    // ── Auth ──────────────────────────────────────────────────────────────────

    if ($method === 'POST' && $path === '/auth/login') {
        $data     = api_read_json_body();
        $authCtrl = new AuthController($userCtx);
        $res      = $authCtrl->attemptLogin($data['phone'] ?? '', $data['password'] ?? '');
        if (!$res['success']) {
            api_error(400, (string) $res['message']);
        }
        $_SESSION['user'] = (int) $res['user_id'];
        api_ok(['user_id' => (int) $res['user_id'], 'role' => (string) $res['role']]);
    }

    if ($method === 'POST' && $path === '/auth/register') {
        $data     = api_read_json_body();
        $authCtrl = new AuthController($userCtx);
        $res      = $authCtrl->registerClient(
            $data['surname']          ?? '',
            $data['name']             ?? '',
            $data['patronomyc']       ?? '',
            $data['phone']            ?? '',
            $data['password']         ?? '',
            $data['confirm_password'] ?? ''
        );
        if (!$res['success']) {
            api_json(400, ['success' => false, 'errors' => $res['errors']]);
        }
        $_SESSION['user'] = (int) $res['user_id'];
        api_ok(['user_id' => (int) $res['user_id'], 'role' => 'client']);
    }

    if ($method === 'POST' && $path === '/auth/logout') {
        $_SESSION = [];
        session_destroy();
        api_ok();
    }

    if ($method === 'GET' && $path === '/auth/me') {
        $u = api_require_auth($userCtx);
        api_ok(['user' => $u]);
    }

    // ── Dishes ────────────────────────────────────────────────────────────────

    if ($method === 'GET' && $path === '/dishes') {
        $res  = $dishCtx->findAll();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        api_ok(['items' => $rows]);
    }

    if ($method === 'GET' && preg_match('#^/dishes/(\d+)$#', $path, $m)) {
        $dish = $dishCtx->findById((int) $m[1]);
        if (!$dish) {
            api_error(404, 'Блюдо не найдено');
        }
        api_ok(['dish' => $dish]);
    }

    if ($method === 'POST' && $path === '/dishes') {
        api_require_role($userCtx, ['admin']);
        $dishCtrl = new DishController($dishCtx);
        $res      = $dishCtrl->createDish(
            $_POST['name']     ?? '',
            $_POST['compound'] ?? '',
            $_POST['price']    ?? 0,
            $_FILES['img']     ?? []
        );
        if (!$res['success']) {
            api_error(400, (string) $res['message']);
        }
        api_ok();
    }

    if ($method === 'POST' && preg_match('#^/dishes/(\d+)$#', $path, $m)) {
        api_require_role($userCtx, ['admin']);
        $dishId  = (int) $m[1];
        $current = $dishCtx->findById($dishId);
        if (!$current) {
            api_error(404, 'Блюдо не найдено');
        }
        $dishCtrl = new DishController($dishCtx);
        $res      = $dishCtrl->updateDish(
            $dishId,
            $_POST['name']     ?? ($current['Name']      ?? ''),
            $_POST['compound'] ?? ($current['Сompound']  ?? ''),
            $_POST['price']    ?? ($current['Price']     ?? 0),
            $_FILES['img']     ?? [],
            $current['Img']    ?? ''
        );
        if (!$res['success']) {
            api_error(400, (string) $res['message']);
        }
        api_ok();
    }

    if (($method === 'PUT' || $method === 'PATCH') && preg_match('#^/dishes/(\d+)$#', $path, $m)) {
        api_require_role($userCtx, ['admin']);
        $dishId  = (int) $m[1];
        $current = $dishCtx->findById($dishId);
        if (!$current) {
            api_error(404, 'Блюдо не найдено');
        }
        $data     = api_read_json_body();
        $dishCtrl = new DishController($dishCtx);
        $res      = $dishCtrl->updateDish(
            $dishId,
            $data['name']     ?? ($current['Name']     ?? ''),
            $data['compound'] ?? ($current['Сompound'] ?? ''),
            $data['price']    ?? ($current['Price']    ?? 0),
            [],
            $current['Img']   ?? ''
        );
        if (!$res['success']) {
            api_error(400, (string) $res['message']);
        }
        api_ok();
    }

    if ($method === 'DELETE' && preg_match('#^/dishes/(\d+)$#', $path, $m)) {
        api_require_role($userCtx, ['admin']);
        if (!$dishCtx->deleteById((int) $m[1])) {
            api_error(400, 'Ошибка удаления');
        }
        api_ok();
    }

    // ── Users ─────────────────────────────────────────────────────────────────

    if ($method === 'GET' && $path === '/users') {
        api_require_role($userCtx, ['admin']);
        $res  = $userCtx->findAll();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            unset($row['Password']);
            $rows[] = $row;
        }
        api_ok(['items' => $rows]);
    }

    if ($method === 'GET' && preg_match('#^/users/(\d+)$#', $path, $m)) {
        $me = api_require_auth($userCtx);
        $id = (int) $m[1];
        if ($me['Role'] !== 'admin' && $me['Id'] !== $id) {
            api_error(403, 'Доступ запрещён');
        }
        $u = $userCtx->findById($id);
        if (!$u) {
            api_error(404, 'Пользователь не найден');
        }
        unset($u['Password']);
        api_ok(['user' => $u]);
    }

    if ($method === 'POST' && $path === '/users') {
        api_require_role($userCtx, ['admin']);
        $data     = api_read_json_body();
        $userCtrl = new UserController($userCtx);
        $res      = $userCtrl->createUserAdmin(
            $data['surname']          ?? '',
            $data['name']             ?? '',
            $data['patronomyc']       ?? '',
            $data['phone']            ?? '',
            $data['password']         ?? '',
            $data['confirm_password'] ?? '',
            $data['role']             ?? 'client'
        );
        if (!$res['success']) {
            api_error(400, (string) $res['message']);
        }
        api_ok();
    }

    if (($method === 'PUT' || $method === 'PATCH') && preg_match('#^/users/(\d+)$#', $path, $m)) {
        api_require_role($userCtx, ['admin']);
        $data     = api_read_json_body();
        $userCtrl = new UserController($userCtx);
        $res      = $userCtrl->updateUserAdmin(
            (int) $m[1],
            $data['surname']    ?? '',
            $data['name']       ?? '',
            $data['patronomyc'] ?? '',
            $data['role']       ?? 'client',
            $data['password']   ?? null
        );
        if (!$res['success']) {
            api_error(400, (string) $res['message']);
        }
        api_ok();
    }

    if ($method === 'DELETE' && preg_match('#^/users/(\d+)$#', $path, $m)) {
        api_require_role($userCtx, ['admin']);
        $userCtrl = new UserController($userCtx);
        $res      = $userCtrl->deleteUser((int) $m[1], api_current_user_id() ?? 0);
        if (!$res['success']) {
            api_error(400, (string) $res['message'], ['code' => $res['code'] ?? null]);
        }
        api_ok();
    }

    if (($method === 'PUT' || $method === 'PATCH') && $path === '/users/me') {
        $me       = api_require_role($userCtx, ['client']);
        $data     = api_read_json_body();
        $userCtrl = new UserController($userCtx);
        $res      = $userCtrl->updateClientProfile(
            $me['Id'],
            $data['name']       ?? '',
            $data['surname']    ?? '',
            $data['patronomyc'] ?? '',
            $data['password']   ?? null
        );
        if (!$res['success']) {
            api_error(400, (string) $res['message']);
        }
        api_ok();
    }

    // ── Orders ────────────────────────────────────────────────────────────────

    if ($method === 'POST' && $path === '/orders') {
        $me        = api_require_role($userCtx, ['client']);
        $data      = api_read_json_body();
        $orderCtrl = new OrderController($orderCtx, $dishCtx);
        $res       = $orderCtrl->createOrderFromClient($me['Id'], $data['address'] ?? '', $data['dishes'] ?? []);
        if (!$res['success']) {
            api_error(400, (string) $res['message']);
        }
        api_ok(['order_id' => (int) $res['order_id']]);
    }

    if ($method === 'POST' && $path === '/orders/admin') {
        api_require_role($userCtx, ['admin']);
        $data      = api_read_json_body();
        $orderCtrl = new OrderController($orderCtx, $dishCtx);
        $res       = $orderCtrl->createOrderAdmin(
            (int) ($data['client_id']  ?? 0),
            (int) ($data['courier_id'] ?? 0),
            $data['address']           ?? '',
            $data['status']            ?? 'accepted',
            $data['dishes']            ?? []
        );
        if (!$res['success']) {
            api_error(400, (string) $res['message']);
        }
        api_ok(['order_id' => (int) $res['order_id']]);
    }

    if ($method === 'GET' && $path === '/orders/me') {
        $me    = api_require_role($userCtx, ['client']);
        $orders = $userCtx->findClientOrdersWithDishes($me['Id']);
        api_ok(['items' => $orders]);
    }

    if ($method === 'GET' && $path === '/orders') {
        api_require_role($userCtx, ['admin']);
        $res  = $orderCtx->findAllForAdmin();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        api_ok(['items' => $rows]);
    }

    if ($method === 'GET' && $path === '/admin/order-form-data') {
        api_require_role($userCtx, ['admin']);
        $clientsRes = $userCtx->findClients();
        $couriersRes = $userCtx->findCouriers();
        $dishesRes  = $dishCtx->findForSelect();

        $clients = [];
        while ($r = $clientsRes->fetch_assoc()) {
            $clients[] = $r;
        }
        $couriers = [];
        while ($r = $couriersRes->fetch_assoc()) {
            $couriers[] = $r;
        }
        $dishes = [];
        while ($r = $dishesRes->fetch_assoc()) {
            $dishes[] = $r;
        }
        api_ok(['clients' => $clients, 'couriers' => $couriers, 'dishes' => $dishes]);
    }

    if ($method === 'GET' && preg_match('#^/admin/orders/(\d+)/form-data$#', $path, $m)) {
        api_require_role($userCtx, ['admin']);
        $orderId = (int) $m[1];
        $order   = $orderCtx->findById($orderId);
        if (!$order) {
            api_error(404, 'Заказ не найден');
        }
        $selected  = $orderCtx->findDishQuantities($orderId);
        $dishesRes = $dishCtx->findForSelect();
        $dishes    = [];
        while ($r = $dishesRes->fetch_assoc()) {
            $dishes[] = $r;
        }
        api_ok(['order' => $order, 'selected' => $selected, 'dishes' => $dishes]);
    }

    if (($method === 'PUT' || $method === 'PATCH') && preg_match('#^/orders/(\d+)$#', $path, $m)) {
        api_require_role($userCtx, ['admin']);
        $data      = api_read_json_body();
        $orderCtrl = new OrderController($orderCtx, $dishCtx);
        $res       = $orderCtrl->updateOrderAdmin(
            (int) $m[1],
            $data['address'] ?? '',
            $data['status']  ?? 'accepted',
            $data['dishes']  ?? []
        );
        if (!$res['success']) {
            api_error(400, (string) $res['message']);
        }
        api_ok();
    }

    if ($method === 'DELETE' && preg_match('#^/orders/(\d+)$#', $path, $m)) {
        api_require_role($userCtx, ['admin']);
        if (!$orderCtx->deleteById((int) $m[1])) {
            api_error(400, 'Ошибка удаления');
        }
        api_ok();
    }

    if ($method === 'GET' && $path === '/courier/orders') {
        $me   = api_require_role($userCtx, ['courier']);
        $res  = $orderCtx->findAllByCourier($me['Id']);
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        api_ok(['items' => $rows]);
    }

    if (($method === 'PUT' || $method === 'PATCH') && preg_match('#^/courier/orders/(\d+)/status$#', $path, $m)) {
        $me        = api_require_role($userCtx, ['courier']);
        $data      = api_read_json_body();
        $orderCtrl = new OrderController($orderCtx, $dishCtx);
        $res       = $orderCtrl->updateOrderStatusByCourier((int) $m[1], $me['Id'], $data['status'] ?? '');
        if (!$res['success']) {
            api_error(400, (string) $res['message']);
        }
        api_ok();
    }

    api_error(404, 'Endpoint не найден');
} catch (Throwable $e) {
    api_error(500, 'Ошибка сервера');
}
