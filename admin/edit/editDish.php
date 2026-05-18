<?php
session_start();
$dishToEdit = (int) ($_GET['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница изменения блюда</title>
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
    <h3>Редактировать блюдо</h3>

    <div id="errors" class="alert alert-danger d-none"></div>

    <form id="dish-form" enctype="multipart/form-data" class="mt-3">
        <div class="mb-3">
            <label for="name" class="form-label">Название</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="compound" class="form-label">Состав</label>
            <input type="text" class="form-control" id="compound" name="compound" required>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Цена</label>
            <input type="number" step="0.01" min="0" class="form-control" id="price" name="price" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Текущее изображение</label><br>
            <img id="current-img" src="" style="width:120px; border-radius:10px;">
        </div>

        <div class="mb-3">
            <label for="img" class="form-label">Новое изображение</label>
            <input type="file" class="form-control" id="img" name="img" accept="image/*">
            <small class="text-muted">Оставьте пустым, если не хотите менять изображение</small>
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
  const form = document.getElementById('dish-form');
  const dishId = <?php echo (int) $dishToEdit; ?>;

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
    if (!dishId) {
      location.href = '../dishes.php';
      return;
    }
    const me = await api('/auth/me');
    if (!me.user || me.user.Role !== 'admin') {
      location.href = '../../index.php';
      return;
    }
    usernameEl.innerText = `${me.user.Name} ${me.user.Surname}`;

    const dishResp = await api('/dishes/' + dishId);
    const d = dishResp.dish;
    form.name.value = d.Name || '';
    form.compound.value = d['Сompound'] || '';
    form.price.value = d.Price || 0;
    document.getElementById('current-img').src = '../../resources/img/' + encodeURIComponent(d.Img || '');

    logoutBtn.addEventListener('click', async () => {
      try { await api('/auth/logout', { method: 'POST' }); }
      finally { location.href = '../../index.php'; }
    });

    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      hideError();
      try {
        const fd = new FormData(form);
        const resp = await fetch(API_URL + '?r=' + encodeURIComponent('/dishes/' + dishId), {
          method: 'POST',
          credentials: 'include',
          body: fd,
        });
        const data = await resp.json().catch(() => null);
        if (!resp.ok || !data || data.success !== true) {
          throw new Error((data && data.message) || 'Ошибка сохранения');
        }
        location.href = '../dishes.php';
      } catch (e2) {
        showError(e2.message || 'Ошибка');
      }
    });
  }

  init().catch(e => showError(e.message || 'Ошибка'));
</script>
