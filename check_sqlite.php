<?php
$db = new PDO('sqlite:' . __DIR__ . '/api/storage/cinemago.sqlite');
$count = $db->query('SELECT COUNT(*) FROM products')->fetchColumn();

echo "SQLite products: " . $count . PHP_EOL;
?>
