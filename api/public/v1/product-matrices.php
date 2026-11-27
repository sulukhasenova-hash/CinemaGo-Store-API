<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';
require_token(); require_method('GET');
$pp=db()->query('SELECT product_id,preference_id,weight FROM product_preferences ORDER BY product_id,preference_id')->fetchAll();
$sim=db()->query('SELECT product_id,related_product_id,weight FROM product_similarity ORDER BY product_id,related_product_id')->fetchAll();
ok(['product_preferences'=>$pp,'product_similarity'=>$sim]);
