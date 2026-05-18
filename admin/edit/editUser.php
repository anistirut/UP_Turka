<?php
session_start();
$userToEdit = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница изменения пользователя</title>
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
    <h3>Редактировать пользователя</h3>

    <div id="errors" class="alert alert-danger d-none"></div>

    <form id="user-form" class="mt-3">
        <div class="mb-3">
            <label for="surname" class="form-label">Фамилия</label>
            <input type="text" class="form-control" id="surname" name="surname" required>
        </div>
        <div class="mb-3">
            <label for="name" class="form-label">Имя</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="patronomyc" class="form-label">Отчество</label>
            <input type="text" class="form-control" id="patronomyc" name="patronomyc" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Телефон</label>
            <input type="tel" class="form-control" id="phone" name="phone" readonly>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Новый пароль (оставьте пустым, чтобы не менять)</label>
            <input type="password" class="form-control" id="password" name="password">
        </div>
        <div class="mb-3">
            <label for="role" class="form-label">Роль</label>
            <select class="form-select" id="role" name="role" required>
                <option value="client">client</option>
                <option value="courier">courier</option>
                <option value="admin">admin</option>
            </select>
        </div>
        <button type="submit" class="btn btn-accent">Сохранить изменения</button>
    </form>
</div>
</body>
</html>

<script>
  const API_URL = '../../api.php';
  const usernameEl = document.getElementById('username');
  const logoutBtn = document.getElementById('logout-btn');
  const errorsBox = document.getElementById('errors');
  const form = document.getElementById('user-form');
  const userId = <?php echo (int) $userToEdit; ?>;

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
    if (!resp.ok || !data) throw new Error((data && data.message) || 'Ошибка запроса');
    return data;
  }

  async function init() {
    hideError();
    if (!userId) {
      location.href = '../users.php';
      return;
    }

    const me = await api('/auth/me');
    if (!me.user || me.user.Role !== 'admin') {
      location.href = '../../index.php';
      return;
    }
    usernameEl.innerText = `${me.user.Name} ${me.user.Surname}`;

    const userResp = await api('/users/' + userId);
    const u = userResp.user;
    form.surname.value = u.Surname || '';
    form.name.value = u.Name || '';
    form.patronomyc.value = u.Patronomyc || '';
    form.phone.value = u.Phone || '';
    form.role.value = u.Role || 'client';

    logoutBtn.addEventListener('click', async () => {
      try { await api('/auth/logout', { method: 'POST' }); }
      finally { location.href = '../../index.php'; }
    });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      hideError();
      const payload = {
        surname: form.surname.value,
        name: form.name.value,
        patronomyc: form.patronomyc.value,
        role: form.role.value,
        password: form.password.value.trim() || null,
      };
      try {
        const res = await api('/users/' + userId, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });
        if (res.success) location.href = '../users.php';
      } catch (e2) {
        showError(e2.message || 'Ошибка');
      }
    });
  }

  init().catch(e => showError(e.message || 'Ошибка'));
</script>
