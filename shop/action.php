<?php
session_start();
require __DIR__.'/lib/api.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php#login');
    exit;
}

$pid = (int)($_POST['product_id'] ?? 0);
$type = (string)($_POST['event_type'] ?? 'cart');

if ($pid <= 0) {
    header('Location: index.php#catalog');
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$key = (string)$pid;
$_SESSION['cart'][$key] = ($_SESSION['cart'][$key] ?? 0) + 1;

try {
    $prefs = api_request('preferences.php', 'GET', ['user_id' => $_SESSION['user_id']]);
    $changes = [];
    $boost = ($type === 'purchase' ? 0.35 : ($type === 'ticket' ? 0.2 : 0.12));

    foreach ($prefs as $p) {
        $changes[$p['id']] = max(-5, min(5, (float)$p['weight'] + $boost));
    }

    api_request('events.php', 'POST', [
        'event_id' => bin2hex(random_bytes(12)),
        'user_id' => (int)$_SESSION['user_id'],
        'product_id' => $pid,
        'event_type' => $type,
        'preference_changes' => $changes
    ]);

    $_SESSION['flash'] = 'Товар добавлен в корзину!';
} catch (Throwable $e) {
    $_SESSION['flash'] = 'Товар добавлен в корзину. Не удалось обновить рекомендации.';
}

header('Location: cart.php');
exit;