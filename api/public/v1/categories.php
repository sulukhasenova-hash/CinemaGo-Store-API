<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';
require_token(); require_method('GET');
$rows=db()->query('SELECT c.id,c.name,s.id AS subcategory_id,s.name AS subcategory_name FROM categories c LEFT JOIN subcategories s ON s.category_id=c.id ORDER BY c.id,s.id')->fetchAll();
ok($rows);
