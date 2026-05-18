<?php

require_once __DIR__ . '/../Contexts/OrderContext.php';
require_once __DIR__ . '/../Contexts/DishContext.php';

class OrderController
{
    private OrderContext $orderCtx;
    private DishContext  $dishCtx;

    public function __construct(OrderContext $orderCtx, DishContext $dishCtx)
    {
        $this->orderCtx = $orderCtx;
        $this->dishCtx  = $dishCtx;
    }

    private function normalizeDishesMap($dishesMap): array
    {
        $out = [];
        foreach ((array) $dishesMap as $dishId => $qty) {
            $qty = (int) $qty;
            if ($qty <= 0) {
                continue;
            }
            $out[(int) $dishId] = $qty;
        }
        return $out;
    }

    private function calcTotalAndInsertLines(int $orderId, array $lines): float
    {
        $total = 0.0;
        foreach ($lines as $dishId => $qty) {
            $price = $this->dishCtx->findDishPrice($dishId);
            if ($price === null) {
                throw new RuntimeException('dish not found');
            }
            $total += $price * $qty;
            if (!$this->orderCtx->insertOrderDish($orderId, $dishId, $qty)) {
                throw new RuntimeException('insert line failed');
            }
        }
        return $total;
    }

    public function buildCheckoutPreview($lines)
    {
        $lines = $this->normalizeDishesMap($lines);
        if (empty($lines)) {
            return ['success' => false, 'message' => 'Нет позиций', 'total' => 0.0, 'rows' => []];
        }

        $res = $this->dishCtx->findByIds(array_keys($lines));
        if (!$res) {
            return ['success' => false, 'message' => 'Нет позиций', 'total' => 0.0, 'rows' => []];
        }

        $rows  = [];
        $total = 0.0;
        while ($d = $res->fetch_assoc()) {
            $qty   = $lines[(int) $d['Id']];
            $price = (float) $d['Price'];
            $sum   = $price * $qty;
            $total += $sum;
            $rows[] = ['data' => $d, 'qty' => $qty, 'sum' => $sum];
        }

        return ['success' => true, 'message' => '', 'total' => $total, 'rows' => $rows];
    }

    public function createOrderFromClient($client_id, $address, $dishesMap)
    {
        $address = trim((string) $address);
        if ($address === '') {
            return ['success' => false, 'message' => 'Укажите адрес'];
        }

        $lines = $this->normalizeDishesMap($dishesMap);
        if (empty($lines)) {
            return ['success' => false, 'message' => 'Выберите хотя бы одно блюдо'];
        }

        $courierId = $this->orderCtx->findLeastBusyCourierId();
        if ($courierId <= 0) {
            return ['success' => false, 'message' => 'Нет доступного курьера'];
        }

        $db = $this->orderCtx->getDb();
        $db->begin_transaction();
        try {
            $orderId = $this->orderCtx->insertOrder((int) $client_id, $courierId, 0.0, $address, 'accepted');
            $total   = $this->calcTotalAndInsertLines($orderId, $lines);
            if (!$this->orderCtx->updateTotal($orderId, $total)) {
                throw new RuntimeException('update total failed');
            }
            $db->commit();
            return ['success' => true, 'message' => '', 'order_id' => $orderId];
        } catch (Throwable $e) {
            $db->rollback();
            return ['success' => false, 'message' => 'Не удалось оформить заказ'];
        }
    }

    public function updateOrderStatusByCourier($order_id, $courier_id, $status)
    {
        $allowed = ['accepted', 'progress', 'ready', 'delivery', 'delivered'];
        if (!in_array((string) $status, $allowed, true)) {
            return ['success' => false, 'message' => 'Некорректный статус'];
        }

        if ($this->orderCtx->updateStatusByCourier((int) $order_id, (int) $courier_id, (string) $status)) {
            return ['success' => true, 'message' => 'Сохранено'];
        }

        return ['success' => false, 'message' => 'Ошибка обновления'];
    }

    public function createOrderAdmin($client_id, $courier_id, $address, $status, $dishesMap)
    {
        $address = trim((string) $address);
        $allowed = ['accepted', 'progress', 'ready', 'delivery', 'delivered'];
        if (!in_array((string) $status, $allowed, true)) {
            return ['success' => false, 'message' => 'Некорректный статус'];
        }

        $lines = $this->normalizeDishesMap($dishesMap);
        if (empty($lines)) {
            return ['success' => false, 'message' => 'Добавьте позиции в заказ'];
        }

        $db = $this->orderCtx->getDb();
        $db->begin_transaction();
        try {
            $orderId = $this->orderCtx->insertOrder((int) $client_id, (int) $courier_id, 0.0, $address, $status);
            $total   = $this->calcTotalAndInsertLines($orderId, $lines);
            $this->orderCtx->updateTotal($orderId, $total);
            $db->commit();
            return ['success' => true, 'message' => '', 'order_id' => $orderId];
        } catch (Throwable $e) {
            $db->rollback();
            return ['success' => false, 'message' => 'Ошибка создания заказа'];
        }
    }

    public function updateOrderAdmin($order_id, $address, $status, $dishesMap)
    {
        $address = trim((string) $address);
        $allowed = ['accepted', 'progress', 'ready', 'delivery', 'delivered'];
        if (!in_array((string) $status, $allowed, true)) {
            return ['success' => false, 'message' => 'Некорректный статус'];
        }

        $lines = $this->normalizeDishesMap($dishesMap);

        $db = $this->orderCtx->getDb();
        $db->begin_transaction();
        try {
            $this->orderCtx->deleteOrderDishes((int) $order_id);
            $total = $this->calcTotalAndInsertLines((int) $order_id, $lines);
            if (!$this->orderCtx->updateOrderFull((int) $order_id, $total, $address, $status)) {
                throw new RuntimeException('update failed');
            }
            $db->commit();
            return ['success' => true, 'message' => 'Сохранено'];
        } catch (Throwable $e) {
            $db->rollback();
            return ['success' => false, 'message' => 'Ошибка сохранения заказа'];
        }
    }
}
