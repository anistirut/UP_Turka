<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
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
            color: white;
        }
        .card {
            border-radius: 12px;
        }
        .qty-control {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .qty-control .qty-val {
            min-width: 28px;
            text-align: center;
            font-weight: 600;
            font-size: 1.1rem;
        }
        .dish-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            flex-shrink: 0;
        }
        .cart-badge {
            font-size: .7rem;
            padding: 2px 6px;
        }
        .qty-input {
            width: 65px;
            text-align: center;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="client.php">Меню ресторана</a>
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
                <button id="logout-btn" type="button" class="btn btn-outline-danger">Выйти</button>
            </div>
        </div>
    </nav>

    <div class="container mt-2">
        <h3 class="mb-4">Корзина</h3>
        <div id="errors" class="alert alert-danger d-none"></div>
        <div id="empty-msg" class="alert alert-info d-none">Корзина пуста. <a href="client.php">Вернуться в меню</a></div>

        <div id="cart-items"></div>

        <div id="limit-warning" class="alert alert-warning d-none">
            Куда так много? Мы не успеем столько приготовить, закажи менее 99 блюд
        </div>

        <div id="cart-footer" class="d-none">
            <div class="card shadow-sm p-4 mt-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="fs-5">Итого:</span>
                    <span class="fs-4 fw-bold" id="total-sum">0 ₽</span>
                </div>
                <div class="d-flex gap-2">
                    <button id="clear-btn" type="button" class="btn btn-outline-secondary">Очистить корзину</button>
                    <a id="checkout-link" href="checkout.php" class="btn btn-accent btn-lg ms-auto px-5">Оформить заказ</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

<script>
    const errorsBox = document.getElementById('errors');
    const usernameEl = document.getElementById('username');
    const logoutBtn = document.getElementById('logout-btn');
    const cartCount = document.getElementById('cart-count');
    const cartItems = document.getElementById('cart-items');
    const cartFooter = document.getElementById('cart-footer');
    const emptyMsg = document.getElementById('empty-msg');
    const totalSum = document.getElementById('total-sum');
    const clearBtn = document.getElementById('clear-btn');
    const limitWarning = document.getElementById('limit-warning');
    const checkoutLink = document.getElementById('checkout-link');

    const MAX_QTY = 99;

    function showError(text) {
        errorsBox.classList.remove('d-none');
        errorsBox.innerText = text;
    }
    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function readBasket() {
        try {
            const obj = JSON.parse(localStorage.getItem('basket_dishes') || '{}');
            return (obj && typeof obj === 'object') ? obj : {};
        } catch { return {}; }
    }
    function writeBasket(basket) {
        localStorage.setItem('basket_dishes', JSON.stringify(basket));
    }
    function updateCartBadge() {
        const total = Object.values(readBasket()).reduce((s, v) => s + Number(v), 0);
        if (total > 0) {
            cartCount.innerText = total;
            cartCount.classList.remove('d-none');
        } else {
            cartCount.classList.add('d-none');
        }
    }

    function getTotalQty(basket) {
        return Object.values(basket).reduce((s, v) => s + Number(v), 0);
    }

    function calcTotal(basket, dishMap) {
        let sum = 0;
        for (const [id, qty] of Object.entries(basket)) {
            const d = dishMap.get(String(id));
            if (d) sum += Number(d.Price) * Number(qty);
        }
        return sum;
    }

    function applyLimitUI(basket) {
        const over = getTotalQty(basket) > MAX_QTY;
        limitWarning.classList.toggle('d-none', !over);
        if (over) {
            checkoutLink.classList.add('disabled');
            checkoutLink.setAttribute('aria-disabled', 'true');
            checkoutLink.removeAttribute('href');
        } else {
            checkoutLink.classList.remove('disabled');
            checkoutLink.removeAttribute('aria-disabled');
            checkoutLink.setAttribute('href', 'checkout.php');
        }
    }

    function renderCart(basket, dishMap) {
        cartItems.innerHTML = '';
        const ids = Object.keys(basket).filter(id => basket[id] > 0 && dishMap.has(id));

        if (ids.length === 0) {
            emptyMsg.classList.remove('d-none');
            cartFooter.classList.add('d-none');
            limitWarning.classList.add('d-none');
            updateCartBadge();
            return;
        }

        emptyMsg.classList.add('d-none');
        cartFooter.classList.remove('d-none');

        ids.forEach(id => {
            const d   = dishMap.get(id);
            const qty = Number(basket[id]);
            const sum = (Number(d.Price) * qty).toFixed(2);

            const row = document.createElement('div');
            row.className = 'card shadow-sm mb-3';
            row.innerHTML = `
                <div class="card-body d-flex align-items-center gap-3">
                    <img src="../resources/img/${encodeURIComponent(d.Img || '')}"
                         class="dish-img" alt="${escapeHtml(d.Name)}" loading="lazy">
                    <div class="flex-grow-1">
                        <div class="fw-semibold fs-6">${escapeHtml(d.Name)}</div>
                        <div class="text-muted small">${escapeHtml(String(d.Price))} ₽ за шт.</div>
                    </div>
                    <div class="qty-control">
                        <button class="btn btn-outline-secondary btn-sm px-3" data-action="minus">−</button>
                        <input type="number" class="form-control form-control-sm qty-input"
                               value="${qty}" min="1" max="99" data-id="${id}">
                        <button class="btn btn-outline-secondary btn-sm px-3" data-action="plus">+</button>
                    </div>
                    <div class="fw-bold text-end" style="min-width:80px">${sum} ₽</div>
                    <button class="btn btn-outline-danger btn-sm" data-action="remove" title="Удалить">✕</button>
                </div>
            `;

            const input = row.querySelector('.qty-input');

            row.querySelector('[data-action="minus"]').addEventListener('click', () => {
                const b = readBasket();
                if ((Number(b[id]) || 0) > 1) b[id] = Number(b[id]) - 1;
                else delete b[id];
                writeBasket(b);
                renderCart(b, dishMap);
            });
            row.querySelector('[data-action="plus"]').addEventListener('click', () => {
                const b = readBasket();
                const newQty = (Number(b[id]) || 0) + 1;
                b[id] = newQty;
                writeBasket(b);
                renderCart(b, dishMap);
            });
            input.addEventListener('change', () => {
                let v = parseInt(input.value, 10);
                if (isNaN(v) || v < 1) v = 1;
                if (v > 99) v = 99;
                input.value = v;
                const b = readBasket();
                b[id] = v;
                writeBasket(b);
                renderCart(b, dishMap);
            });
            row.querySelector('[data-action="remove"]').addEventListener('click', () => {
                const b = readBasket();
                delete b[id];
                writeBasket(b);
                renderCart(b, dishMap);
            });

            cartItems.appendChild(row);
        });

        totalSum.innerText = calcTotal(basket, dishMap).toFixed(2) + ' ₽';
        applyLimitUI(basket);
        updateCartBadge();
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
        updateCartBadge();

        const [me, dishes] = await Promise.all([
            api('/auth/me'),
            api('/dishes'),
        ]);

        if (!me.user || me.user.Role !== 'client') {
            location.href = '../index.php';
            return;
        }
        usernameEl.innerText = `${me.user.Name} ${me.user.Surname}`;

        const dishMap = new Map();
        (dishes.items || []).forEach(d => dishMap.set(String(d.Id), d));

        renderCart(readBasket(), dishMap);

        clearBtn.addEventListener('click', () => {
            if (!confirm('Очистить корзину?')) return;
            writeBasket({});
            renderCart({}, dishMap);
        });

        logoutBtn.addEventListener('click', async () => {
            try { await api('/auth/logout', { method: 'POST' }); }
            finally { location.href = '../index.php'; }
        });
    }

    init().catch(e => showError(e.message || 'Ошибка'));
</script>
