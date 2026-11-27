
<?php
require __DIR__ . '/api/src/bootstrap.php';

try {
    $pdo = db();

    echo "РџРѕРґРєР»СЋС‡РµРЅРёРµ Рє MySQL СѓСЃРїРµС€РЅРѕ!\n";

    $count = $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

    echo "РљРѕР»РёС‡РµСЃС‚РІРѕ С‚РѕРІР°СЂРѕРІ РІ MySQL: " . $count . "\n";

    $count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    echo "РљРѕР»РёС‡РµСЃС‚РІРѕ РїРѕР»СЊР·РѕРІР°С‚РµР»РµР№ РІ MySQL: " . $count . "\n";

} catch (Throwable $e) {
    echo "РћС€РёР±РєР° РїРѕРґРєР»СЋС‡РµРЅРёСЏ РёР»Рё Р·Р°РїСЂРѕСЃР°:\n";
    echo $e->getMessage() . "\n";
}
