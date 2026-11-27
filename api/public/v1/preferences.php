<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';
require_token(); require_method('GET');
$id=int_param('user_id',true);
$q=db()->prepare('SELECT p.id,p.code,p.name,COALESCE(up.weight,0) AS weight FROM preferences p LEFT JOIN user_preferences up ON up.preference_id=p.id AND up.user_id=? ORDER BY p.id');
$q->execute([$id]); ok($q->fetchAll());
