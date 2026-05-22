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
        .card:hover {
            box-shadow: 0 6px 20px rgba(0,0,0,.12) !important;
        }
        .btn-accent {
            background-color: #b02a37;
            border: none;
            color: white;
        }
        .btn-accent:hover {
            background-color: #8f1f2a;
            color: white;
        }
        .qty-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .qty-control .qty-val {
            min-width: 28px;
            text-align: center;
            font-weight: 600;
        }
        .cart-badge {
            font-size: .7rem;
            padding: 2px 6px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Меню ресторана</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto gap-2">
                    <li class="nav-item">
                        <a href="profile.php" class="btn btn-outline-primary">Личный кабинет</a>
                    </li>
                    <li class="nav-item">
                        <a href="cart.php" class="btn btn-accent position-relative">
                            Корзина
                            <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-warning text-dark cart-badge d-none">0</span>
                        </a>
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
    </div>
</body>
</html>

<script>
    const errorsBox = document.getElementById('errors');
    const dishesEl = document.getElementById('dishes');
    const usernameEl = document.getElementById('username');
    const logoutBtn = document.getElementById('logout-btn');
    const cartCount = document.getElementById('cart-count');

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

    const MAX_QTY = 99;

    function readBasket() {
        try {
            const obj = JSON.parse(localStorage.getItem('basket_dishes') || '{}');
            return (obj && typeof obj === 'object') ? obj : {};
        } catch { return {}; }
    }
    function writeBasket(basket) {
        localStorage.setItem('basket_dishes', JSON.stringify(basket));
        updateCartBadge();
    }
    function getTotalQty(basket) {
        return Object.values(basket).reduce((s, v) => s + Number(v), 0);
    }
    function updateCartBadge() {
        const total = getTotalQty(readBasket());
        if (total > 0) {
            cartCount.innerText = total;
            cartCount.classList.remove('d-none');
        } else {
            cartCount.classList.add('d-none');
        }
    }

    function renderDishCard(dish) {
        const id       = Number(dish.Id);
        const img      = dish.Img || '';
        const name     = dish.Name || '';
        const compound = dish['Сompound'] || '';
        const price    = dish.Price || '';

        const col = document.createElement('div');
        col.className = 'col-md-4';
        col.innerHTML = `
            <div class="card h-100 shadow-sm">
                <img src="../resources/img/${encodeURIComponent(img)}" class="card-img-top" alt="${escapeHtml(name)}" loading="lazy">
                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">${escapeHtml(name)}</h5>
                    <p class="card-text text-muted">${escapeHtml(compound)}</p>
                    <p class="card-text fw-bold fs-5">${escapeHtml(String(price))} ₽</p>
                    <div class="mt-auto" id="ctrl-${id}"></div>
                </div>
            </div>
        `;

        renderCardControl(col, id);
        return col;
    }

    function renderCardControl(col, id) {
        const ctrl = col.querySelector(`#ctrl-${id}`);
        const basket = readBasket();
        const qty = Number(basket[id] || 0);

        if (qty === 0) {
            ctrl.innerHTML = `<button class="btn btn-accent w-100">В корзину</button>`;
            ctrl.querySelector('button').addEventListener('click', () => {
                const b = readBasket();
                if (getTotalQty(b) >= MAX_QTY) {
                    showError('Куда так много? Мы не успеем столько приготовить, закажи менее 99 блюд');
                    return;
                }
                b[id] = 1;
                writeBasket(b);
                hideError();
                renderCardControl(col, id);
            });
        } else {
            ctrl.innerHTML = `
                <div class="qty-control">
                    <button class="btn btn-outline-secondary btn-sm px-3" data-action="minus">−</button>
                    <span class="qty-val">${qty}</span>
                    <button class="btn btn-outline-secondary btn-sm px-3" data-action="plus">+</button>
                </div>`;
            ctrl.querySelector('[data-action="minus"]').addEventListener('click', () => {
                const b = readBasket();
                if ((b[id] || 0) > 1) b[id] = Number(b[id]) - 1;
                else delete b[id];
                writeBasket(b);
                hideError();
                renderCardControl(col, id);
            });
            ctrl.querySelector('[data-action="plus"]').addEventListener('click', () => {
                const b = readBasket();
                if (getTotalQty(b) >= MAX_QTY) {
                    showError('Куда так много? Мы не успеем столько приготовить, закажи менее 99 блюд');
                    return;
                }
                b[id] = (Number(b[id]) || 0) + 1;
                writeBasket(b);
                hideError();
                renderCardControl(col, id);
            });
        }
    }

    async function api(path, options = {}) {
        const resp = await fetch('../api.php?r=' + encodeURIComponent(path), {
            credentials: 'include',
            ...options,
        });
        const data = await resp.json().catch(() => null);
        if (!resp.ok || !data) throw new Error((data && data.message) || 'Ошибка запроса');
        return data;
    }

    async function init() {
        hideError();
        updateCartBadge();

        const [me, dishes] = await Promise.all([
            api('/auth/me'),
            api('/dishes'),
        ]);

        if (!me.user || me.user.Role !== 'client') {
            if (me.user && me.user.Role === 'admin') location.href = '../admin/admin.php';
            else if (me.user && me.user.Role === 'courier') location.href = '../courier/courier.php';
            else location.href = '../index.php';
            return;
        }

        usernameEl.innerText = `${me.user.Name} ${me.user.Surname}`;

        dishesEl.innerHTML = '';
        (dishes.items || []).forEach(d => dishesEl.appendChild(renderDishCard(d)));

        logoutBtn.addEventListener('click', async () => {
            try { await api('/auth/logout', { method: 'POST' }); }
            finally { location.href = '../index.php'; }
        });
    }

    init().catch(e => showError(e.message || 'Ошибка'));
</script>
