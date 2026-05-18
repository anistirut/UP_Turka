<?php
session_start();
$orderId = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница изменения заказа</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f7f7f7;
        }
        .navbar-brand {
            font-weight: 600;
            color: #b02a37 !important;
        }
        .btn-accent {
            background-color: #b02a37;
            border: none;
            color: white;
        }
        .btn-accent:hover {
            background-color: #8f1f2a;
        }
        .card {
            border-radius: 12px;
        }
    </style> 
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
        <div class="container-fluid">
        <a class="navbar-brand" href="../admin.php">Admin Panel</a>
        <div class="collapse navbar-collapse" id="navbarAdmin">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link" href="../users.php">Пользователи</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../dishes.php">Блюда</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="../orders.php">Заказы</a>
            </li>
          </ul>
          <span class="navbar-text me-3">Привет, <span id="username">—</span></span>
          <button id="logout-btn" type="button" class="btn btn-outline-danger">Выйти</button>
        </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h3>Редактировать заказ</h3>
        <div id="errors" class="alert alert-danger d-none"></div>
        <form id="order-form">
            <label class="form-label">Блюда</label>
            <div id="dishes-list"></div>

            <div class="mt-3">
                <label>Итого</label>
                <input type="text" id="total" class="form-control" value="0 ₽" readonly>
            </div>

            <div class="mb-3">
                <label for="address" class="form-label">Адрес доставки</label>
                <input type="text" id="address" name="address" class="form-control" required>
            </div>


            <div class="mb-3">
                <label>Статус</label>
                <select name="status" class="form-select">
                    <option value="accepted">Принят</option>
                    <option value="progress">Готовится</option>
                    <option value="ready">Готов</option>
                    <option value="delivery">В доставке</option>
                    <option value="delivered">Доставлен</option>
                </select>
            </div>

            <button class="btn btn-danger" type="submit">Сохранить</button>
        </form>
    </div>
</body>
</html>

<script>
    const API_URL = '../../api.php';
    const orderId = <?php echo (int) $orderId; ?>;
    const usernameEl = document.getElementById('username');
    const logoutBtn = document.getElementById('logout-btn');
    const errorsBox = document.getElementById('errors');
    const form = document.getElementById('order-form');
    const dishesList = document.getElementById('dishes-list');
    const totalInput = document.getElementById('total');

    function showError(text) {
        errorsBox.classList.remove('d-none');
        errorsBox.innerText = text;
    }
    function hideError() {
        errorsBox.classList.add('d-none');
        errorsBox.innerText = '';
    }
    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    async function api(path, options = {}) {
        const resp = await fetch(API_URL + '?r=' + encodeURIComponent(path), {
            credentials: 'include',
            ...options,
        });
        const data = await resp.json().catch(() => null);
        if (!resp.ok || !data) throw new Error((data && data.message) || 'Ошибка запроса');
        return data;
    }

    function calc() {
        let sum = 0;
        document.querySelectorAll('.dish-qty').forEach(input => {
            const price = parseFloat(input.dataset.price);
            const qty = parseInt(input.value) || 0;
            sum += price * qty;
        });
        totalInput.value = sum.toFixed(2) + ' ₽';
    }

    function renderDishRow(d, selectedQty) {
        const wrap = document.createElement('div');
        wrap.className = 'mb-2';
        const qty = selectedQty || 0;
        wrap.innerHTML = `
            <span>${escapeHtml(d.Name)} (${escapeHtml(String(d.Price))} ₽)</span>
            <input type="number" class="form-control dish-qty" data-price="${escapeHtml(String(d.Price))}" name="dishes[${Number(d.Id)}]"
                   min="0" value="${qty}" style="width:80px; display:inline-block; margin-left:10px;">
        `;
        wrap.querySelector('input').addEventListener('input', calc);
        return wrap;
    }

    async function init() {
        hideError();
        if (!orderId) {
            location.href = '../orders.php';
            return;
        }
        const me = await api('/auth/me');
        if (!me.user || me.user.Role !== 'admin') {
            location.href = '../../index.php';
            return;
        }
        usernameEl.innerText = `${me.user.Name} ${me.user.Surname}`;

        const data = await api('/admin/orders/' + orderId + '/form-data');
        const order = data.order;
        form.address.value = order.Address || '';
        form.status.value = order.Status || 'accepted';

        dishesList.innerHTML = '';
        (data.dishes || []).forEach(d => {
            const qty = (data.selected && data.selected[d.Id]) ? Number(data.selected[d.Id]) : 0;
            dishesList.appendChild(renderDishRow(d, qty));
        });
        calc();

        logoutBtn.addEventListener('click', async () => {
            try { await api('/auth/logout', { method: 'POST' }); }
            finally { location.href = '../../index.php'; }
        });

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideError();
            const dishes = {};
            document.querySelectorAll('.dish-qty').forEach(inp => {
                const m = inp.name.match(/dishes\[(\d+)\]/);
                if (!m) return;
                const id = m[1];
                const qty = parseInt(inp.value) || 0;
                if (qty > 0) dishes[id] = qty;
            });

            const payload = {
                address: form.address.value,
                status: form.status.value,
                dishes,
            };

            try {
                const res = await api('/orders/' + orderId, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload),
                });
                if (res.success) location.href = '../orders.php';
            } catch (e2) {
                showError(e2.message || 'Ошибка');
            }
        });
    }

    init().catch(e => showError(e.message || 'Ошибка'));
</script>
