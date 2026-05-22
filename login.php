<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
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

            <div class="text-center mt-3">
                <a href="regin.php" class="register-link">Ещё не зарегистрированы?</a>
            </div>
            <div class="text-center mt-2">
                <a href="index.php" class="text-muted small">← Вернуться к меню</a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<script>
    function applyPhoneMask(el) {
        function format(raw) {
            let d = raw.replace(/\D/g, '');
            if (d.startsWith('8')) d = '7' + d.slice(1);
            else if (d.length && !d.startsWith('7')) d = '7' + d;
            d = d.slice(0, 11);
            if (!d) return '';
            let res = '+7';
            if (d.length > 1)  res += ' (' + d.slice(1, 4);
            if (d.length >= 4) res += ') ' + d.slice(4, 7);
            if (d.length >= 7) res += '-' + d.slice(7, 9);
            if (d.length >= 9) res += '-' + d.slice(9, 11);
            return res;
        }
        el.addEventListener('input', () => { el.value = format(el.value); });
        el.addEventListener('paste', e => {
            e.preventDefault();
            el.value = (e.clipboardData || window.clipboardData).getData('text');
            el.dispatchEvent(new Event('input'));
        });
        el.addEventListener('focus', () => { if (!el.value) el.value = '+7 '; });
        el.addEventListener('blur',  () => { if (el.value === '+7 ' || el.value === '+7') el.value = ''; });
    }

    applyPhoneMask(document.getElementById('phone'));

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

        const resp = await fetch('api.php?r=/auth/login', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ phone: form.phone.value, password: form.password.value })
        });

        const data = await resp.json().catch(() => null);
        if (!resp.ok || !data || data.success !== true) {
            showError((data && (data.message || (data.errors && data.errors.join('\n')))) || 'Ошибка авторизации');
            return;
        }

        switch (data.role) {
            case 'client': location.href = 'client/client.php'; break;
            case 'courier': location.href = 'courier/courier.php'; break;
            case 'admin': location.href = 'admin/admin.php'; break;
            default: showError('Неизвестная роль пользователя');
        }
    });
</script>
