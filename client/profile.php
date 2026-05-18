<?php
session_start();
$statusMap = [
    'accepted'  => 'Принят',
    'progress'  => 'Готовится',
    'ready'     => 'Готов',
    'delivery'  => 'В доставке',
    'delivered' => 'Доставлен',
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль</title>
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
            <a class="navbar-brand" href="client.php">Меню ресторана</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a href="profile.php" class="btn btn-outline-primary">Личный кабинет</a>
                    </li>
                </ul>
                <span class="navbar-text me-3">Привет, <span id="username">—</span></span>
                <button id="logout-btn" type="button" class="btn btn-outline-danger">Выйти</button>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
        <h3>Ваши заказы</h3>
        <div class="table-responsive mb-5">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Блюда</th>
                        <th>Сумма</th>
                        <th>Адрес</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody id="orders-body"></tbody>
            </table>
        </div>

        <h3>Редактировать профиль</h3>
        <div id="errors" class="alert alert-danger d-none"></div>
        <form id="profile-form" class="mb-5">
            <div class="mb-3">
                <label class="form-label">Имя</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Фамилия</label>
                <input type="text" name="surname" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Отчество</label>
                <input type="text" name="patronomyc" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Телефон</label>
                <input type="text" name="phone" class="form-control" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label">Новый пароль</label>
                <input type="password" name="password" class="form-control" placeholder="Оставьте пустым, если не хотите менять">
            </div>
            <button type="submit" class="btn btn-accent">Сохранить изменения</button>
        </form>
    </div>
</body>
</html>

<script>
    const statusMap = <?php echo json_encode($statusMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const usernameEl = document.getElementById('username');
    const logoutBtn = document.getElementById('logout-btn');
    const ordersBody = document.getElementById('orders-body');
    const form = document.getElementById('profile-form');
    const errorsBox = document.getElementById('errors');

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
        const resp = await fetch('../api.php?r=' + encodeURIComponent(path), {
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

    function renderOrders(items) {
        ordersBody.innerHTML = '';
        items.forEach(o => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(o.Dishes || '—')}</td>
                <td>${escapeHtml(String(o.TotalSum))} ₽</td>
                <td>${escapeHtml(o.Address)}</td>
                <td>${escapeHtml(statusMap[o.Status] || o.Status)}</td>
            `;
            ordersBody.appendChild(tr);
        });
    }

    async function init() {
        hideError();
        const me = await api('/auth/me');
        if (!me.user || me.user.Role !== 'client') {
            location.href = '../index.php';
            return;
        }
        usernameEl.innerText = `${me.user.Name} ${me.user.Surname}`;

        const user = await api('/users/' + me.user.Id);
        const u = user.user;
        form.name.value = u.Name || '';
        form.surname.value = u.Surname || '';
        form.patronomyc.value = u.Patronomyc || '';
        form.phone.value = u.Phone || '';

        const orders = await api('/orders/me');
        renderOrders(orders.items || []);

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            hideError();
            const payload = {
                name: form.name.value,
                surname: form.surname.value,
                patronomyc: form.patronomyc.value,
                password: form.password.value.trim() || null,
            };
            const resp = await api('/users/me', {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            if (resp.success) {
                form.password.value = '';
            }
        });

        logoutBtn.addEventListener('click', async () => {
            try { await api('/auth/logout', { method: 'POST' }); }
            finally { location.href = '../index.php'; }
        });
    }

    init().catch(e => showError(e.message || 'Ошибка'));
</script>
