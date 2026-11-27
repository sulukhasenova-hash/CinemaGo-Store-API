<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';
require_token(); require_method('GET');
$id=int_param('user_id',true);
$q=db()->prepare('SELECT u.id,u.login,u.display_name,u.group_id,g.name AS group_name FROM users u JOIN customer_groups g ON g.id=u.group_id WHERE u.id=?');
$q->execute([$id]); $row=$q->fetch();
if (!$row) fail('NOT_FOUND','Пользователь не найден',404);
ok($row);
