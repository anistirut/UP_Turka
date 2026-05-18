<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страница авторизации</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="resources/css/style.css">
</head>
<body>
    <div class="card shadow-lg login-card">
    <div class="card-body p-4">
        <h3 class="text-center mb-4 login-title">Авторизация</h3>
        <p class="text-center text-muted mb-4">Онлайн-ресторан турецкой кухни</p>

        <div id="errors" class="alert alert-danger d-none"></div>

        <form id="login-form">
            <div class="mb-3">
                <label for="phone" class="form-label">Номер телефона</label>
                <input 
                    type="tel"
                    class="form-control"
                    id="phone"
                    name="phone"
                    placeholder="+7 (___) ___-__-__"
                    pattern="^\+?[0-9\s\-\(\)]{10,20}$"
                    required
                >
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <div class="d-grid mt-4">
                <button type="submit" class="btn btn-danger btn-lg">Войти</button>
            </div>
        </form>
        <div class="text-center">
            <a href="regin.php" class="register-link">
                Ещё не зарегистрированы?
            </a>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<script>
    document.getElementById('phone').addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9+\-\(\)\s]/g, '');
    });

    const form = document.getElementById('login-form');
    const errorsBox = document.getElementById('errors');

    function showError(text) {
        errorsBox.classList.remove('d-none');
        errorsBox.innerText = text;
    }

    function hideError() {
        errorsBox.classList.add('d-none');
        errorsBox.innerText = '';
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        hideError();

        const phone = form.phone.value;
        const password = form.password.value;

        const resp = await fetch('api.php?r=/auth/login', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone, password })
        });

        const data = await resp.json().catch(() => null);
        if (!resp.ok || !data || data.success !== true) {
            const msg = (data && (data.message || (data.errors && data.errors.join('\n')))) || 'Ошибка авторизации';
            showError(msg);
            return;
        }

        switch (data.role) {
            case 'client':
                location.href = 'client/client.php';
                return;
            case 'courier':
                location.href = 'courier/courier.php';
                return;
            case 'admin':
                location.href = 'admin/admin.php';
                return;
            default:
                showError('Неизвестная роль пользователя');
        }
    });
</script>
