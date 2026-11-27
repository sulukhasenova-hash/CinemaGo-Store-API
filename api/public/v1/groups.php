<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';
require_token(); require_method('GET');
$groups=db()->query('SELECT id,name,description FROM customer_groups ORDER BY id')->fetchAll();
$gp=db()->query('SELECT group_id,preference_id,weight FROM group_preferences ORDER BY group_id,preference_id')->fetchAll();
$gc=db()->query('SELECT group_id,category_id,weight FROM group_categories ORDER BY group_id,category_id')->fetchAll();
ok(['groups'=>$groups,'group_preferences'=>$gp,'group_categories'=>$gc]);
