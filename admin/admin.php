<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ панель</title>
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
    <a class="navbar-brand" href="#">Admin Panel</a>
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

<div class="container">
    <div class="card shadow-sm p-4 mb-4">
        <h3 class="mb-3">Добро пожаловать в панель администратора</h3>
        <p>Через меню выше вы можете управлять пользователями, блюдами и заказами.</p>
        <p>На каждой странице можно добавлять, изменять и удалять записи.</p>
        <a href="../functions/createReport.php" class="btn btn-success">
            Сгенерировать отчёт
        </a>
    </div>
    <div id="errors" class="alert alert-danger d-none"></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<script>
  const API_URL = '../api.php';
  const usernameEl = document.getElementById('username');
  const logoutBtn = document.getElementById('logout-btn');
  const errorsBox = document.getElementById('errors');

  function showError(text) {
    errorsBox.classList.remove('d-none');
    errorsBox.innerText = text;
  }
  function hideError() {
    errorsBox.classList.add('d-none');
    errorsBox.innerText = '';
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

  async function init() {
    hideError();
    const me = await api('/auth/me');
    if (!me.user || me.user.Role !== 'admin') {
      if (me.user && me.user.Role === 'client') location.href = '../client/client.php';
      else if (me.user && me.user.Role === 'courier') location.href = '../courier/courier.php';
      else location.href = '../index.php';
      return;
    }
    usernameEl.innerText = `${me.user.Name} ${me.user.Surname}`;

    logoutBtn.addEventListener('click', async () => {
      try { await api('/auth/logout', { method: 'POST' }); }
      finally { location.href = '../index.php'; }
    });
  }

  init().catch(e => showError(e.message || 'Ошибка'));
</script>
