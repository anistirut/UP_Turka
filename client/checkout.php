<?php
session_start();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Оформление заказа</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://api-maps.yandex.ru/2.1/?apikey=96123de1-75f1-4536-b4f0-c9e2efc923ba&lang=ru_RU" defer></script>
    <style>
        .toast-container {
            position: fixed;
            top: 24px;
            right: 24px;
            z-index: 9999;
        }
    </style>
</head>
<body class="bg-light">

<div class="toast-container">
    <div id="success-toast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" data-bs-autohide="false">
        <div class="d-flex">
            <div class="toast-body fs-6">
                Заказ успешно оформлен! Ожидайте доставку.
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">
    <h3 class="mb-3">Ваш заказ</h3>

    <div id="errors" class="alert alert-danger d-none"></div>

    <div id="items"></div>

        <div class="alert alert-secondary mt-3">
            <strong>Итого:</strong> <span id="total">0</span> ₽
        </div>

        <div class="mb-3">
            <label class="form-label">Адрес доставки</label>
            <input type="text" name="address" id="address" class="form-control" required>
        </div>

        <div id="map" style="width:100%; height:400px;" class="mb-3"></div>

        <button id="confirm-btn" class="btn btn-danger btn-lg w-100" type="button">Подтвердить заказ</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
const errorsBox = document.getElementById('errors');
const itemsEl = document.getElementById('items');
const totalEl = document.getElementById('total');
const confirmBtn = document.getElementById('confirm-btn');
const addressInput = document.getElementById('address');

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

function clearBasket() {
    localStorage.removeItem(basketKey());
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
    const resp = await fetch('../api.php?r=' + encodeURIComponent(path), {
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

function renderItem(name, qty, price, sum) {
    const card = document.createElement('div');
    card.className = 'card mb-2';
    card.innerHTML = `
        <div class="card-body d-flex justify-content-between">
            <div>
                <strong>${escapeHtml(name)}</strong><br>
                ${qty} × ${escapeHtml(String(price))} ₽
            </div>
            <div class="fw-bold">${escapeHtml(String(sum))} ₽</div>
        </div>
    `;
    return card;
}

async function initCheckout() {
    hideError();

    const me = await api('/auth/me');
    if (!me.user || me.user.Role !== 'client') {
        location.href = '../index.php';
        return;
    }

    const basket = readBasket();
    const ids = Object.keys(basket);
    if (ids.length === 0) {
        location.href = 'client.php';
        return;
    }

    const dishes = await api('/dishes');
    const byId = new Map();
    (dishes.items || []).forEach(d => byId.set(String(d.Id), d));

    let total = 0;
    itemsEl.innerHTML = '';

    for (const id of ids) {
        const qty = Number(basket[id] || 0);
        const d = byId.get(String(id));
        if (!d || qty <= 0) continue;
        const price = Number(d.Price);
        const sum = price * qty;
        total += sum;
        itemsEl.appendChild(renderItem(d.Name, qty, d.Price, sum.toFixed(2)));
    }

    totalEl.innerText = total.toFixed(2);

    confirmBtn.addEventListener('click', async () => {
        hideError();
        const address = addressInput.value.trim();
        if (!address) {
            showError('Укажите адрес доставки');
            return;
        }
        const resp = await api('/orders', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ address, dishes: basket })
        });
        if (resp.success) {
            clearBasket();
            const toastEl = document.getElementById('success-toast');
            const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
            toast.show();
            setTimeout(() => { location.href = 'client.php'; }, 2800);
        }
    });
}

ymaps.ready(() => {
    const map = new ymaps.Map("map", {
        center: [55.75, 37.61],
        zoom: 12
    });

    let placemark;

    map.events.add('click', e => {
        const coords = e.get('coords');

        if (!placemark) {
            placemark = new ymaps.Placemark(coords, {}, { draggable: true });
            map.geoObjects.add(placemark);
        } else {
            placemark.geometry.setCoordinates(coords);
        }

        ymaps.geocode(coords).then(res => {
            const address = res.geoObjects.get(0).getAddressLine();
            document.getElementById('address').value = address;
        });
    });
});

initCheckout().catch(e => showError(e.message || 'Ошибка'));
</script>

</body>
</html>
