<?php
session_start();
require __DIR__.'/lib/api.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove'])) {
        unset($_SESSION['cart'][(string)(int)$_POST['remove']]);
    }

    if (isset($_POST['clear'])) {
        $_SESSION['cart'] = [];
    }

    header('Location: cart.php');
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

function h($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Корзина CinemaGo</title>
<link rel="stylesheet" href="assets/style.css">
<style>
.cart-page{max-width:1000px;margin:50px auto;padding:20px}
.cart-item{display:flex;align-items:center;gap:20px;padding:18px;margin:15px 0;background:#1b1b23;border-radius:12px}
.cart-item img{width:100px;height:130px;object-fit:cover;border-radius:8px}
.cart-info{flex:1}
.cart-info h3{margin:0 0 10px}
.cart-actions{display:flex;gap:10px;align-items:center}
.cart-actions button,.cart-actions a,.cart-button{padding:10px 16px;border:0;border-radius:7px;cursor:pointer;text-decoration:none}
.remove{background:#e50914;color:white}
.cart-total{font-size:22px;margin:25px 0}
.empty{padding:40px;text-align:center;background:#1b1b23;border-radius:12px}
@media(max-width:600px){.cart-item{flex-wrap:wrap}.cart-item img{width:75px;height:100px}}
</style>
</head>
<body>
<header>
<a class="logo" href="index.php">CINEMA<span>GO</span></a>
<nav><a href="index.php#catalog">Каталог</a><a href="cart.php">Корзина</a></nav>
<div class="account"><a href="index.php">На главную</a></div>
</header>

<main class="cart-page">
<h1>Моя корзина</h1>

<?php if (!empty($_SESSION['flash'])): ?>
<div class="notice"><?=h($_SESSION['flash'])?></div>
<?php unset($_SESSION['flash']); endif; ?>

<?php if (!$cartItems): ?>
<div class="empty">
<h2>Корзина пока пуста</h2>
<p>Добавь товары из каталога CinemaGo.</p>
<a class="button" href="index.php#catalog">Перейти в каталог</a>
</div>
<?php else: ?>

<?php foreach ($cartItems as $id => $qty):
    if (!isset($map[(string)$id])) continue;
    $p = $map[(string)$id];
    $subtotal = (float)$p['price'] * (int)$qty;
    $total += $subtotal;
    $count += (int)$qty;
?>
<div class="cart-item">
<img src="<?=h($p['image'])?>" alt="">
<div class="cart-info">
<h3><?=h($p['name'])?></h3>
<p>Цена: <?=number_format((float)$p['price'], 0, '.', ' ')?> ₸</p>
<p>Количество: <?= (int)$qty ?></p>
<strong>Сумма: <?=number_format($subtotal, 0, '.', ' ')?> ₸</strong>
</div>
<form method="post">
<button class="remove" type="submit" name="remove" value="<?=h($id)?>">Удалить</button>
</form>
</div>
<?php endforeach; ?>

<div class="cart-total">
<strong>Товаров: <?=$count?></strong><br>
<strong>Итого: <?=number_format($total, 0, '.', ' ')?> ₸</strong>
</div>

<form method="post">
<button class="remove" type="submit" name="clear" value="1">Очистить корзину</button>
</form>

<p>Оплата в этом учебном проекте демонстрационная. Реальные платежи не выполняются.</p>
<a class="button" href="checkout.php" style="display:inline-block;margin-bottom:15px">Оформить заказ</a><br><br><a class="button" href="index.php#catalog">Продолжить покупки</a>

<?php endif; ?>
</main>
</body>
</html>