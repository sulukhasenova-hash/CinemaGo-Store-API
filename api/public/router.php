<?php
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH);
$file=__DIR__ . $path;
if (is_file($file)) return false;
http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['ok'=>false,'error'=>['code'=>'NOT_FOUND','message'=>'Маршрут не найден']],JSON_UNESCAPED_UNICODE);
