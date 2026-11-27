<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';
require_token(); require_method('POST');
$in = input_json();
$login = trim((string)($in['login'] ?? ''));
$password = (string)($in['password'] ?? '');
if ($login === '' || $password === '') fail('INVALID_INPUT','Укажите логин и пароль',400);
$q = db()->prepare('SELECT u.id,u.login,u.display_name,u.group_id,u.password_hash,g.name AS group_name FROM users u JOIN customer_groups g ON g.id=u.group_id WHERE u.login=?');
$q->execute([$login]); $u = $q->fetch();
if (!$u || !password_verify($password, $u['password_hash'])) fail('INVALID_CREDENTIALS','Неверный логин или пароль',401);
unset($u['password_hash']); ok($u);
