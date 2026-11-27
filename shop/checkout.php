
<?php
session_start();
require __DIR__ . '/lib/api.php';

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

if (empty($_SESSION['user_id'])) {
    header('Location: index.php#login');
    exit;
}

$cartItems = $_SESSION['cart'] ?? [];
$products = api_request('products.php');
$map = [];

foreach ($products as $p) {
    $map[(string)$p['id']] = $p;
}

$total = 0;
$count = 0;
$items = [];

foreach ($cartItems as $id => $qty) {
    if (!isset($map[(string)$id])) {
        continue;
    }

    $p = $map[(string)$id];
    $qty = (int)$qty;

    if ($qty <= 0) {
        continue;
    }

    $subtotal = (float)$p['price'] * $qty;
    $total += $subtotal;
    $count += $qty;

    $items[] = [
        'product_id' => (int)$id,
        'quantity' => $qty
    ];
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cartItems) {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name !== '' && $phone !== '' && !empty($items)) {
        try {
            $result = api_request('orders.php', 'POST', [
                'user_id' => (int)$_SESSION['user_id'],
                'customer_name' => $name,
                'phone' => $phone,
                'items' => $items
            ]);

            $_SESSION['last_order'] = $result;
            $_SESSION['cart'] = [];

            header('Location: checkout.php?success=1');
            exit;

        } catch (Throwable $e) {
            $error = 'Ошибка сохранения заказа: ' . $e->getMessage();
        }
    } else {
        $error = 'Заполните все поля.';
    }
}

$order = !empty($_GET['success'])
    ? ($_SESSION['last_order'] ?? null)
    : null;
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Оформление заказа CinemaGo</title>
<link rel="stylesheet" href="assets/style.css">

<style>
.checkout {
    max-width: 650px;
    margin: 50px auto;
    padding: 25px;
    background: #1b1b23;
    border-radius: 14px;
    color: white;
}

.checkout input {
    width: 100%;
    padding: 13px;
    margin: 10px 0 20px;
    border-radius: 7px;
    border: 1px solid #444;
    background: #24242e;
    color: white;
    box-sizing: border-box;
}

.checkout button {
    padding: 13px 22px;
    background: #e50914;
    color: white;
    border: 0;
    border-radius: 7px;
    cursor: pointer;
}

.checkout a {
    color: white;
}
</style>
</head>

<body>

<header>
    <a class="logo" href="index.php">CINEMA<span>GO</span></a>

    <nav>
        <a href="index.php#catalog">Каталог</a>
        <a href="cart.php">Корзина</a>
    </nav>
</header>

<main class="checkout">

<?php if ($order): ?>

    <h1>Заказ успешно оформлен!</h1>

    <p>Спасибо за покупку, <?= h($order['customer_name']) ?>!</p>

    <p>
        Номер заказа:
        <strong><?= h($order['order_number']) ?></strong>
    </p>

    <p>Телефон: <?= h($order['phone']) ?></p>

    <p>
        Сумма заказа:
        <strong><?= number_format($order['total'], 0, '.', ' ') ?> ₸</strong>
    </p>

    <p>Заказ сохранён в базе данных MySQL.</p>

    <p>
        Оплата демонстрационная.
        Реальные деньги не списываются.
    </p>

    <a href="index.php">Вернуться в магазин</a>

<?php elseif (!$cartItems): ?>

    <h1>Корзина пуста</h1>

    <a href="index.php#catalog">Вернуться в каталог</a>

<?php else: ?>

    <h1>Оформление заказа</h1>

    <p>Количество товаров: <?= $count ?></p>

    <h2>
        Итого: <?= number_format($total, 0, '.', ' ') ?> ₸
    </h2>

    <?php if (!empty($error)): ?>
        <p style="color:#ff5555"><?= h($error) ?></p>
    <?php endif; ?>

    <form method="post">

        <label>Ваше имя</label>
        <input type="text" name="name" required>

        <label>Номер телефона</label>
        <input
            type="tel"
            name="phone"
            placeholder="+7 777 000 00 00"
            required
        >

        <button type="submit">Подтвердить заказ</button>

    </form>

    <p>
        Оплата демонстрационная.
        Реальные деньги не списываются.
    </p>

<?php endif; ?>

</main>

</body>
</html>
