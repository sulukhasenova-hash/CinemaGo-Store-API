<?php
require dirname(__DIR__, 2) . '/src/bootstrap.php';
require_token(); require_method('GET');
try { db()->query('SELECT 1'); ok(['service'=>'available','database'=>'available']); }
catch (Throwable $e) { fail('SERVICE_UNAVAILABLE','База данных временно недоступна',503); }
