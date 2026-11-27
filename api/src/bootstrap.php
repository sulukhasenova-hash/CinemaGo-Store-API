
<?php


$config = require dirname(__DIR__) . '/config.php';

function db(): PDO
{
    global $config;

    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $database = $config['database'];

    $dsn = 'mysql:host=' . $database['host']
        . ';port=' . $database['port']
        . ';dbname=' . $database['name']
        . ';charset=' . $database['charset'];

    $pdo = new PDO(
        $dsn,
        $database['username'],
        $database['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    return $pdo;
}

function json_out(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

function ok(mixed $data, int $status = 200): never
{
    json_out(['ok' => true, 'data' => $data], $status);
}

function fail(string $code, string $message, int $status): never
{
    json_out([
        'ok' => false,
        'error' => [
            'code' => $code,
            'message' => $message,
        ],
    ], $status);
}

function require_token(): void
{
    global $config;

    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (
        !preg_match('/^Bearer\s+(.+)$/i', $header, $m) ||
        !hash_equals((string)$config['token'], trim($m[1]))
    ) {
        fail('UNAUTHORIZED', 'Требуется действительный токен доступа.', 401);
    }
}

function require_method(string $method): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== $method) {
        header('Allow: ' . $method);
        fail('METHOD_NOT_ALLOWED', 'Метод запроса не поддерживается.', 405);
    }
}

function input_json(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);

    if (!is_array($data)) {
        fail('INVALID_JSON', 'Некорректный JSON.', 400);
    }

    return $data;
}

function int_param(string $name, bool $required = false): ?int
{
    $value = $_GET[$name] ?? null;

    if ($value === null || $value === '') {
        if ($required) {
            fail('INVALID_INPUT', 'Не указан параметр: ' . $name, 400);
        }

        return null;
    }

    if (
        filter_var($value, FILTER_VALIDATE_INT) === false ||
        (int)$value < 1
    ) {
        fail('INVALID_INPUT', 'Некорректный параметр: ' . $name, 400);
    }

    return (int)$value;
}
