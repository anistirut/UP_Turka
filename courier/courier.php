<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Заказы официанта</title>
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
            <a class="navbar-brand" href="courier.php">Заказы</a>
            <div class="collapse navbar-collapse" id="navbarWaiter">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                </ul>
                <span class="navbar-text me-3">Привет, <span id="username">—</span></span>
                <button id="logout-btn" type="button" class="btn btn-outline-danger">Выйти</button>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h3>Мои заказы</h3>
        <div id="errors" class="alert alert-danger d-none"></div>
        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Клиент</th>
                        <th>Блюда</th>
                        <th>Сумма</th>
                        <th>Адрес</th>
                        <th>Статус</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody id="orders-body"></tbody>
            </table>
        </div>
    </div>
</body>
</html>

<script>
    const API_URL = '../api.php';
    const usernameEl = document.getElementById('username');
    const logoutBtn = document.getElementById('logout-btn');
    const errorsBox = document.getElementById('errors');
    const ordersBody = document.getElementById('orders-body');

    const statusOptions = [
        { value: 'accepted', label: 'Принят' },
        { value: 'progress', label: 'Готовится' },
        { value: 'ready', label: 'Готов' },
        { value: 'delivery', label: 'В доставке' },
        { value: 'delivered', label: 'Доставлен' },
    ];

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
        if (!resp.ok || !data) {
            const msg = (data && data.message) || 'Ошибка запроса';
            throw new Error(msg);
        }
        return data;
    }

    function renderRow(o) {
        const tr = document.createElement('tr');
        const orderId = Number(o.Id);
        const selected = String(o.Status || 'accepted');

        const selectHtml = statusOptions.map(opt => {
            const sel = opt.value === selected ? 'selected' : '';
            return `<option value="${opt.value}" ${sel}>${escapeHtml(opt.label)}</option>`;
        }).join('');

        tr.innerHTML = `
            <td>${orderId}</td>
            <td>${escapeHtml((o.ClientSurname || '') + ' ' + (o.ClientName || ''))}</td>
            <td>${escapeHtml(o.Dishes || '—')}</td>
            <td>${escapeHtml(String(o.TotalSum))} ₽</td>
            <td>${escapeHtml(o.Address || '')}</td>
            <td>${escapeHtml(String(o.Status || ''))}</td>
            <td>
                <div class="d-flex gap-1">
                    <select class="form-select form-select-sm" data-order-id="${orderId}">
                        ${selectHtml}
                    </select>
                    <button class="btn btn-sm btn-primary" data-save-id="${orderId}">Сохранить</button>
                </div>
            </td>
        `;

        const saveBtn = tr.querySelector(`[data-save-id="${orderId}"]`);
        const select = tr.querySelector(`select[data-order-id="${orderId}"]`);

        saveBtn.addEventListener('click', async () => {
            hideError();
            saveBtn.disabled = true;
            try {
                await api(`/courier/orders/${orderId}/status`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ status: select.value })
                });
                await loadOrders();
            } catch (e) {
                showError(e.message || 'Ошибка');
            } finally {
                saveBtn.disabled = false;
            }
        });

        return tr;
    }

    async function loadOrders() {
        const res = await api('/courier/orders');
        const items = res.items || [];
        ordersBody.innerHTML = '';
        items.forEach(o => ordersBody.appendChild(renderRow(o)));
    }

    async function init() {
        hideError();

        const [me, ordersRes] = await Promise.all([
            api('/auth/me'),
            api('/courier/orders').catch(() => null),
        ]);

        if (!me.user || me.user.Role !== 'courier') {
            if (me.user && me.user.Role === 'admin') location.href = '../admin/admin.php';
            else if (me.user && me.user.Role === 'client') location.href = '../client/client.php';
            else location.href = '../index.php';
            return;
        }

        usernameEl.innerText = `${me.user.Name} ${me.user.Surname}`;

        const items = ordersRes?.items || [];
        ordersBody.innerHTML = '';
        items.forEach(o => ordersBody.appendChild(renderRow(o)));

        logoutBtn.addEventListener('click', async () => {
            try { await api('/auth/logout', { method: 'POST' }); }
            finally { location.href = '../index.php'; }
        });
    }

    init().catch(e => showError(e.message || 'Ошибка'));
</script>
