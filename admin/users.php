<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
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
            <h3>Пользователи</h3>
            <a href="add/addUser.php" class="btn btn-accent">Добавить пользователя</a>
        </div>

        <div id="errors" class="alert alert-danger d-none"></div>

        <div class="table-responsive">
            <table class="table table-striped table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Фамилия</th>
                        <th>Имя</th>
                        <th>Отчество</th>
                        <th>Телефон</th>
                        <th>Роль</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="users-body"></tbody>
            </table>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<script>
  const API_URL = '../api.php';
    const logoutBtn = document.getElementById('logout-btn');
    const usernameEl = document.getElementById('username');
  const errorsBox = document.getElementById('errors');
  const tbody = document.getElementById('users-body');

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
      throw new Error((data && data.message) || 'Ошибка запроса');
    }
    return data;
  }

  async function loadUsers() {
    const res = await api('/users');
    const items = res.items || [];
    tbody.innerHTML = '';
    items.forEach(u => {
      const id = Number(u.Id);
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td>${id}</td>
        <td>${escapeHtml(u.Surname || '')}</td>
        <td>${escapeHtml(u.Name || '')}</td>
        <td>${escapeHtml(u.Patronomyc || '')}</td>
        <td>${escapeHtml(u.Phone || '')}</td>
        <td>${escapeHtml(u.Role || '')}</td>
        <td>
          <a href="edit/editUser.php?id=${id}" class="btn btn-sm btn-primary">Редактировать</a>
          <button type="button" class="btn btn-sm btn-danger" data-del="${id}">Удалить</button>
        </td>
      `;
      tr.querySelector(`[data-del="${id}"]`).addEventListener('click', async () => {
        if (!confirm('Удалить этого пользователя?')) return;
        hideError();
        try {
          const del = await api('/users/' + id, { method: 'DELETE' });
          if (del.success) await loadUsers();
        } catch (e) {
          showError(e.message || 'Ошибка');
        }
      });
      tbody.appendChild(tr);
    });
  }

  async function init() {
    hideError();
    const me = await api('/auth/me');
    if (!me.user || me.user.Role !== 'admin') {
      location.href = '../index.php';
      return;
    }

    usernameEl.innerText = `${me.user.Name} ${me.user.Surname}`;

    await loadUsers();

    logoutBtn.addEventListener('click', async () => {
      try { await api('/auth/logout', { method: 'POST' }); }
      finally { location.href = '../index.php'; }
    });
  }

  init().catch(e => showError(e.message || 'Ошибка'));
</script>
