<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';
require_token(); require_method('GET');
$id = int_param('id');
$sql = 'SELECT p.id,p.name,p.description,p.price,p.stock,p.product_type,p.category_id,p.subcategory_id,p.image,p.is_active,c.name AS category_name,s.name AS subcategory_name FROM products p JOIN categories c ON c.id=p.category_id JOIN subcategories s ON s.id=p.subcategory_id WHERE p.is_active=1';
$args=[];
if ($id !== null) { $sql .= ' AND p.id=?'; $args[]=$id; }
$sql .= ' ORDER BY p.id';
$q=db()->prepare($sql); $q->execute($args); $rows=$q->fetchAll();
if ($id !== null && !$rows) fail('NOT_FOUND','Товар не найден',404);
ok($rows);
