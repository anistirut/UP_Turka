<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управления заказами</title>
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
        <a class="navbar-brand" href="admin.php">Admin Panel</a>
        <div class="collapse navbar-collapse" id="navbarAdmin">
          <ul class="navbar-nav me-auto mb-2 mb-lg-0">
            <li class="nav-item">
              <a class="nav-link" href="users.php">Пользователи</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="dishes.php">Блюда</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="orders.php">Заказы</a>
            </li>
          </ul>
          <span class="navbar-text me-3">Привет, <span id="username">—</span></span>
          <button id="logout-btn" type="button" class="btn btn-outline-danger">Выйти</button>
        </div>
        </div>
    </nav>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Заказы</h3>
            <a href="add/addOrder.php" class="btn btn-accent">Добавить заказ</a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Клиент</th>
                        <th>Официант</th>
                        <th>Блюда</th>
                        <th>Сумма</th>
                        <th>Адрес</th>
                        <th>Статус</th>
                        <th>Действия</th>
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
  const tbody = document.getElementById('orders-body');

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
      throw new Error((data && data.message) || 'Ошибка запроса');
    }
    return data;
  }

  async function loadOrders() {
    const res = await api('/orders');
    const items = res.items || [];
    tbody.innerHTML = '';
    items.forEach(o => {
      const id = Number(o.Id);
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${id}</td>
        <td>${escapeHtml((o.ClientName || '') + ' ' + (o.ClientSurname || ''))}</td>
        <td>${escapeHtml((o.CourierName || '') + ' ' + (o.CourierSurname || ''))}</td>
        <td>${escapeHtml(o.Dishes || '—')}</td>
        <td>${escapeHtml(String(o.TotalSum))} ₽</td>
        <td>${escapeHtml(o.Address || '')}</td>
        <td>${escapeHtml(o.Status || '')}</td>
        <td>
          <a href="edit/editOrder.php?id=${id}" class="btn btn-sm btn-primary">Редактировать</a>
          <button type="button" class="btn btn-sm btn-danger" data-del="${id}">Удалить</button>
        </td>
      `;
      tr.querySelector(`[data-del="${id}"]`).addEventListener('click', async () => {
        if (!confirm('Удалить этот заказ?')) return;
        try {
          const del = await api('/orders/' + id, { method: 'DELETE' });
          if (del.success) await loadOrders();
        } catch (e) {
          alert(e.message || 'Ошибка');
        }
      });
      tbody.appendChild(tr);
    });
  }

  async function init() {
    const me = await api('/auth/me');
    if (!me.user || me.user.Role !== 'admin') {
      location.href = '../index.php';
      return;
    }
    usernameEl.innerText = `${me.user.Name} ${me.user.Surname}`;
    await loadOrders();

    logoutBtn.addEventListener('click', async () => {
      try { await api('/auth/logout', { method: 'POST' }); }
      finally { location.href = '../index.php'; }
    });
  }

  init().catch(e => alert(e.message || 'Ошибка'));
</script>
