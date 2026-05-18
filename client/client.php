<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Меню ресторана</title>
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
        .card:hover {
            transform: translateY(-5px); 
        }
        .card-img-top { 
            height: 180px; object-fit: cover; border-top-left-radius:12px; border-top-right-radius:12px; 
        }
        .quantity-input { 
            width: 70px; 
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Меню ресторана</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a href="profile.php" class="btn btn-outline-primary">Личный кабинет</a>
                    </li>
                </ul>
                <span class="navbar-text me-3">Привет, <span id="username">—</span></span>
                <button id="logout-btn" class="btn btn-outline-danger" type="button">Выйти</button>
            </div>
        </div>
    </nav>
    <div class="container">
        <div id="errors" class="alert alert-danger d-none"></div>

        <div id="dishes" class="row g-4"></div>

            <div class="mt-4 text-center">
                <button id="checkout-btn" type="button" class="btn btn-accent btn-lg">Оформить заказ</button>
            </div>
    </div>
</body>
</html>

<script>
    const errorsBox = document.getElementById('errors');
    const dishesEl = document.getElementById('dishes');
    const usernameEl = document.getElementById('username');
    const checkoutBtn = document.getElementById('checkout-btn');
    const logoutBtn = document.getElementById('logout-btn');

    function showError(text) {
        errorsBox.classList.remove('d-none');
        errorsBox.innerText = text;
    }

    function hideError() {
        errorsBox.classList.add('d-none');
        errorsBox.innerText = '';
    }

    function basketKey() {
        return 'basket_dishes';
    }

    function readBasket() {
        try {
            const raw = localStorage.getItem(basketKey());
            const obj = raw ? JSON.parse(raw) : {};
            return (obj && typeof obj === 'object') ? obj : {};
        } catch (e) {
            return {};
        }
    }

    function writeBasket(basket) {
        localStorage.setItem(basketKey(), JSON.stringify(basket));
    }

    function renderDishCard(dish, basket) {
        const id = Number(dish.Id);
        const img = dish.Img || '';
        const name = dish.Name || '';
        const compound = dish['Сompound'] || '';
        const price = dish.Price || '';

        const qty = Number(basket[id] || 0);

        const col = document.createElement('div');
        col.className = 'col-md-4';

        col.innerHTML = `
            <div class="card h-100 shadow-sm">
                <img src="../resources/img/${encodeURIComponent(img)}" class="card-img-top" alt="${escapeHtml(name)}">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${escapeHtml(name)}</h5>
                    <p class="card-text">${escapeHtml(compound)}</p>
                    <p class="card-text fw-bold">${escapeHtml(String(price))} ₽</p>
                    <div class="mt-auto d-flex gap-2 align-items-center">
                        <input type="number" min="0" value="${qty}" class="form-control form-control-sm quantity-input" data-id="${id}">
                    </div>
                </div>
            </div>
        `;

        const input = col.querySelector('input.quantity-input');
        input.addEventListener('input', () => {
            const v = Math.max(0, parseInt(input.value || '0', 10));
            input.value = String(v);
            if (v > 0) basket[id] = v;
            else delete basket[id];
            writeBasket(basket);
        });

        return col;
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    const API_URL = '../api.php';

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

    async function init() {
        hideError();

        const me = await api('/auth/me');
        if (!me.user || me.user.Role !== 'client') {
            if (me.user && me.user.Role === 'admin') location.href = '../admin/admin.php';
            else if (me.user && me.user.Role === 'courier') location.href = '../courier/courier.php';
            else location.href = '../index.php';
            return;
        }

        usernameEl.innerText = `${me.user.Name} ${me.user.Surname}`;

        const dishes = await api('/dishes');
        const basket = readBasket();

        dishesEl.innerHTML = '';
        (dishes.items || []).forEach(d => {
            dishesEl.appendChild(renderDishCard(d, basket));
        });

        checkoutBtn.addEventListener('click', () => {
            const b = readBasket();
            if (!b || Object.keys(b).length === 0) {
                showError('Выберите хотя бы одно блюдо.');
                return;
            }
            location.href = 'checkout.php';
        });

        logoutBtn.addEventListener('click', async () => {
            try {
                await api('/auth/logout', { method: 'POST' });
            } finally {
                location.href = '../index.php';
            }
        });
    }

    init().catch(e => showError(e.message || 'Ошибка'));
</script>
