
<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';

require_token();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uid = int_param('user_id', true);

    $q = $pdo->prepare(
        'SELECT * FROM orders WHERE user_id=? ORDER BY id DESC'
    );

    $q->execute([$uid]);

    ok($q->fetchAll());
}

require_method('POST');

$in = input_json();

$uid = (int)($in['user_id'] ?? 0);
$name = trim((string)($in['customer_name'] ?? ''));
$phone = trim((string)($in['phone'] ?? ''));
$items = $in['items'] ?? [];

if (
    $uid < 1 ||
    $name === '' ||
    $phone === '' ||
    !is_array($items) ||
    !$items
) {
    fail('INVALID_INPUT', 'Заполните все данные заказа', 400);
}

$check = $pdo->prepare('SELECT id FROM users WHERE id=?');
$check->execute([$uid]);

if (!$check->fetch()) {
    fail('NOT_FOUND', 'Пользователь не найден', 404);
}

$prepared = [];
$total = 0;

foreach ($items as $item) {
    $pid = (int)($item['product_id'] ?? 0);
    $qty = (int)($item['quantity'] ?? 0);

    if ($pid < 1 || $qty < 1 || $qty > 99) {
        fail(
            'INVALID_INPUT',
            'Некорректный товар или количество',
            400
        );
    }

    $q = $pdo->prepare(
        'SELECT id, name, price, is_active
         FROM products
         WHERE id=?'
    );

    $q->execute([$pid]);
    $product = $q->fetch();

    if (!$product || !(int)$product['is_active']) {
        fail(
            'NOT_FOUND',
            'Товар не найден или недоступен',
            404
        );
    }

    $price = (float)$product['price'];
    $subtotal = $price * $qty;
    $total += $subtotal;

    $prepared[] = [
        'id' => $pid,
        'name' => $product['name'],
        'price' => $price,
        'quantity' => $qty,
        'subtotal' => $subtotal
    ];
}

$orderNumber = 'CG' . date('YmdHis') . random_int(100, 999);

try {
    $pdo->beginTransaction();

    $q = $pdo->prepare(
        'INSERT INTO orders
         (order_number, user_id, customer_name, phone, total)
         VALUES (?, ?, ?, ?, ?)'
    );

    $q->execute([
        $orderNumber,
        $uid,
        $name,
        $phone,
        $total
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $q = $pdo->prepare(
        'INSERT INTO order_items
         (order_id, product_id, product_name,
          unit_price, quantity, subtotal)
         VALUES (?, ?, ?, ?, ?, ?)'
    );

    foreach ($prepared as $item) {
        $q->execute([
            $orderId,
            $item['id'],
            $item['name'],
            $item['price'],
            $item['quantity'],
            $item['subtotal']
        ]);
    }

    $pdo->commit();

    ok([
        'id' => $orderId,
        'order_number' => $orderNumber,
        'customer_name' => $name,
        'phone' => $phone,
        'total' => $total,
        'items' => $prepared,
        'status' => 'new'
    ], 201);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fail(
        'SERVER_ERROR',
        'Не удалось сохранить заказ',
        500
    );
}
