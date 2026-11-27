
<?php

$base = __DIR__;
$configFile = $base . '/migration.local.php';
$sqliteFile = $base . '/api/storage/cinemago.sqlite';

if (!file_exists($configFile)) {
    exit("ОШИБКА: нет файла migration.local.php\n");
}

if (!file_exists($sqliteFile)) {
    exit("ОШИБКА: не найдена исходная SQLite база\n");
}

$config = require $configFile;

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $source = new PDO('sqlite:' . $sqliteFile, null, null, $options);

    $target = new PDO(
        'mysql:host=' . $config['host'] .
        ';dbname=' . $config['database'] .
        ';charset=utf8mb4',
        $config['username'],
        $config['password'],
        $options
    );

    $tables = [
        'categories',
        'customer_groups',
        'preferences',
        'subcategories',
        'users',
        'products',
        'group_categories',
        'group_preferences',
        'user_preferences',
        'product_preferences',
        'product_similarity',
        'events',
        'orders',
        'order_items',
    ];

    echo "Подключение успешно.\n";

    // Проверяем, что таблицы назначения пустые.
    foreach ($tables as $table) {
        $count = (int)$target->query(
            "SELECT COUNT(*) FROM `$table`"
        )->fetchColumn();

        if ($count > 0) {
            exit("ОСТАНОВКА: таблица $table уже содержит данные.\n");
        }
    }

    // Сохраняем исходные количества записей.
    $sourceCounts = [];

    foreach ($tables as $table) {
        $sourceCounts[$table] = (int)$source
            ->query("SELECT COUNT(*) FROM `$table`")
            ->fetchColumn();
    }

    echo "Начинаем перенос данных...\n\n";

    $target->beginTransaction();

    foreach ($tables as $table) {
        $rows = $source->query(
            "SELECT * FROM `$table`"
        )->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) === 0) {
            echo "$table: 0 записей\n";
            continue;
        }

        $columns = array_keys($rows[0]);

        $columnSql = implode(
            ', ',
            array_map(fn($column) => "`$column`", $columns)
        );

        $placeholders = implode(
            ', ',
            array_fill(0, count($columns), '?')
        );

        $insert = $target->prepare(
            "INSERT INTO `$table` ($columnSql) VALUES ($placeholders)"
        );

        foreach ($rows as $row) {
            $insert->execute(array_values($row));
        }

        echo "$table: перенесено " . count($rows) . " записей\n";
    }

    $target->commit();

    echo "\nПРОВЕРКА КОЛИЧЕСТВА ЗАПИСЕЙ:\n";

    $total = 0;
    $allCorrect = true;

    foreach ($tables as $table) {
        $actual = (int)$target->query(
            "SELECT COUNT(*) FROM `$table`"
        )->fetchColumn();

        $expected = $sourceCounts[$table];
        $total += $actual;

        $status = ($actual === $expected) ? 'OK' : 'ОШИБКА';

        if ($actual !== $expected) {
            $allCorrect = false;
        }

        echo "$table: SQLite=$expected, MySQL=$actual — $status\n";
    }

    echo "\nВсего записей в MySQL: $total\n";

    if ($allCorrect) {
        echo "МИГРАЦИЯ УСПЕШНО ЗАВЕРШЕНА!\n";
    } else {
        echo "ВНИМАНИЕ: количество записей отличается.\n";
    }

} catch (Throwable $e) {
    if (isset($target) && $target->inTransaction()) {
        $target->rollBack();
    }

    echo "\nОШИБКА МИГРАЦИИ: " . $e->getMessage() . "\n";
    exit(1);
}
