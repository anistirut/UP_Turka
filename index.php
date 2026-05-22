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
            color: white;
        }
        .card {
            border-radius: 12px;
            transition: transform .2s, box-shadow .2s;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 20px rgba(0,0,0,.12) !important;
        }
        .card-img-top {
            height: 180px;
            object-fit: cover;
            border-top-left-radius: 12px;
            border-top-right-radius: 12px;
        }
        .hero {
            background: linear-gradient(135deg, #b02a37 0%, #6b1521 100%);
            color: #fff;
            padding: 56px 0 48px;
            margin-bottom: 40px;
        }
        .hero h1 {
            font-size: 2.4rem;
            font-weight: 700;
        }
        .hero p {
            font-size: 1.1rem;
            opacity: .88;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">Меню ресторана</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto gap-2">
                <li class="nav-item">
                    <a href="login.php" class="btn btn-outline-danger">Войти</a>
                </li>
                <li class="nav-item">
                    <a href="regin.php" class="btn btn-accent">Зарегистрироваться</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="hero text-center">
    <div class="container">
        <h1>Турецкая кухня</h1>
        <p class="mb-4">Свежие блюда с доставкой прямо к вашей двери</p>
        <a href="regin.php" class="btn btn-light btn-lg me-2 fw-semibold" style="color:#b02a37;">
            Начать заказ
        </a>
        <a href="login.php" class="btn btn-outline-light btn-lg">
            Уже есть аккаунт
        </a>
    </div>
</div>

<div class="container mb-5">
    <h4 class="mb-4 fw-semibold">Наше меню</h4>
    <div id="errors" class="alert alert-danger d-none"></div>
    <div id="dishes" class="row g-4"></div>
</div>

<script>
    async function loadDishes() {
        const resp = await fetch('api.php?r=' + encodeURIComponent('/dishes'));
        const data = await resp.json().catch(() => null);
        if (!data || !data.items) return;

        const container = document.getElementById('dishes');

        data.items.forEach(dish => {
            const name = escapeHtml(dish.Name || '');
            const compound = escapeHtml(dish['Сompound'] || '');
            const price = escapeHtml(String(dish.Price || ''));
            const img = dish.Img || '';

            const col = document.createElement('div');
            col.className = 'col-md-4';
            col.innerHTML = `
                <div class="card h-100 shadow-sm">
                    <img src="resources/img/${encodeURIComponent(img)}"
                         class="card-img-top" alt="${name}" loading="lazy">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${name}</h5>
                        <p class="card-text text-muted">${compound}</p>
                        <p class="card-text fw-bold fs-5 mt-auto">${price} ₽</p>
                        <a href="regin.php" class="btn btn-accent w-100 mt-2">Заказать</a>
                    </div>
                </div>
            `;
            container.appendChild(col);
        });
    }

    function escapeHtml(str) {
        return String(str)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    fetch('api.php?r=' + encodeURIComponent('/auth/me'), { credentials: 'include' })
        .then(r => r.json()).catch(() => null)
        .then(data => {
            if (data && data.user) {
                switch (data.user.Role) {
                    case 'client':  location.href = 'client/client.php';  return;
                    case 'courier': location.href = 'courier/courier.php'; return;
                    case 'admin':   location.href = 'admin/admin.php';    return;
                }
            }
            loadDishes();
        });
</script>

</body>
</html>
