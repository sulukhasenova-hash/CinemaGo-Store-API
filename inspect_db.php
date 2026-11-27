<?php
$db = new PDO('sqlite:' . __DIR__ . '/api/storage/cinemago.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$tables = $db->query("
    SELECT name, sql
    FROM sqlite_master
    WHERE type = 'table'
      AND name NOT LIKE 'sqlite_%'
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

foreach ($tables as $table) {
    echo "\n========== " . $table['name'] . " ==========\n";
    echo $table['sql'] . "\n";

    $name = str_replace('"', '""', $table['name']);
    $count = $db->query('SELECT COUNT(*) FROM "' . $name . '"')->fetchColumn();
    echo "ROWS: " . $count . "\n";
}
?>
