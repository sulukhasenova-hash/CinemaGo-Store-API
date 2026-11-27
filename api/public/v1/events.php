
<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';

require_token();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $uid = int_param('user_id', true);

    $q = db()->prepare(
        'SELECT event_id, user_id, product_id, event_type, created_at
         FROM events
         WHERE user_id=?
         ORDER BY created_at DESC
         LIMIT 100'
    );

    $q->execute([$uid]);
    ok($q->fetchAll());
}

require_method('POST');

$in = input_json();

$eventId = (string)($in['event_id'] ?? '');
$uid = (int)($in['user_id'] ?? 0);
$pid = (int)($in['product_id'] ?? 0);
$type = (string)($in['event_type'] ?? '');

$allowed = ['view', 'cart', 'purchase', 'favorite', 'ticket'];
$changes = $in['preference_changes'] ?? [];

if (
    !preg_match('/^[a-zA-Z0-9_-]{8,80}$/', $eventId) ||
    $uid < 1 ||
    $pid < 1 ||
    !in_array($type, $allowed, true) ||
    !is_array($changes)
) {
    fail('INVALID_INPUT', 'Некорректные данные события', 400);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $check = $pdo->prepare('SELECT id FROM users WHERE id=?');
    $check->execute([$uid]);

    if (!$check->fetch()) {
        $pdo->rollBack();
        fail('NOT_FOUND', 'Пользователь не найден', 404);
    }

    $check = $pdo->prepare('SELECT id FROM products WHERE id=?');
    $check->execute([$pid]);

    if (!$check->fetch()) {
        $pdo->rollBack();
        fail('NOT_FOUND', 'Товар не найден', 404);
    }

    $existing = $pdo->prepare(
        'SELECT event_id FROM events WHERE event_id=?'
    );
    $existing->execute([$eventId]);

    if ($existing->fetch()) {
        $pdo->commit();
        ok(['event_id' => $eventId, 'duplicate' => true]);
    }

    $st = $pdo->prepare(
        'INSERT INTO user_preferences
         (user_id, preference_id, weight)
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE weight = ?'
    );

    foreach ($changes as $prefId => $weight) {
        $prefId = (int)$prefId;
        $weight = (float)$weight;

        if (
            $prefId < 1 ||
            !is_finite($weight) ||
            $weight < -5 ||
            $weight > 5
        ) {
            $pdo->rollBack();
            fail(
                'INVALID_INPUT',
                'Недопустимый коэффициент предпочтения',
                400
            );
        }

        $st->execute([$uid, $prefId, $weight, $weight]);
    }

    $st = $pdo->prepare(
        'INSERT INTO events
         (event_id, user_id, product_id, event_type)
         VALUES (?, ?, ?, ?)'
    );

    $st->execute([$eventId, $uid, $pid, $type]);

    $pdo->commit();

    ok(['event_id' => $eventId, 'duplicate' => false], 201);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    fail(
        'SERVER_ERROR',
        'Не удалось сохранить событие',
        500
    );
}
