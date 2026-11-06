<?php
declare(strict_types=1);

/**
 * Приём заявок с лендинга и отправка в Telegram.
 * Ожидает POST с JSON: {"type":"consult|first","name":"...","tg":"@username"}
 * Настройки (токен и chat_id) лежат в config.php рядом с этим файлом.
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

function respond(int $code, array $data): never
{
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Только POST
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, ['ok' => false, 'error' => 'method_not_allowed']);
}

// 2. Конфиг
$configFile = __DIR__ . '/config.php';
if (!is_file($configFile)) {
    error_log('send.php: config.php not found');
    respond(500, ['ok' => false, 'error' => 'server_misconfigured']);
}
$config = require $configFile;
$token  = (string)($config['bot_token'] ?? '');
$chatId = (string)($config['chat_id'] ?? '');
$allowedHosts = (array)($config['allowed_hosts'] ?? []);
if ($token === '' || $chatId === '' || str_contains($token, 'ВАШ_ТОКЕН')) {
    error_log('send.php: bot_token or chat_id is empty');
    respond(500, ['ok' => false, 'error' => 'server_misconfigured']);
}

// 3. Проверка источника запроса (если в config.php указаны домены)
if ($allowedHosts) {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? '';
    $host   = (string)parse_url($origin, PHP_URL_HOST);
    if ($host === '' || !in_array($host, $allowedHosts, true)) {
        respond(403, ['ok' => false, 'error' => 'forbidden']);
    }
}

// 4. Ограничение частоты: не более 5 заявок за 10 минут с одного IP
$ip     = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$rlFile = sys_get_temp_dir() . '/lead_rl_' . md5($ip) . '.json';
$now    = time();
$hits   = [];
if (is_file($rlFile)) {
    $saved = json_decode((string)file_get_contents($rlFile), true);
    if (is_array($saved)) {
        $hits = array_values(array_filter($saved, fn($t) => is_int($t) && $t > $now - 600));
    }
}
if (count($hits) >= 5) {
    respond(429, ['ok' => false, 'error' => 'too_many_requests']);
}

// 5. Чтение и проверка данных
$raw = file_get_contents('php://input', false, null, 0, 4096);
$in  = json_decode((string)$raw, true);
if (!is_array($in)) {
    respond(400, ['ok' => false, 'error' => 'bad_json']);
}

$type = (string)($in['type'] ?? '');
$name = trim((string)($in['name'] ?? ''));
$tg   = trim((string)($in['tg'] ?? ''));

$titles = [
    'consult' => 'Заявка на консультацию',
    'first'   => 'Подписка «Узнать первыми»',
];
if (!isset($titles[$type])) {
    respond(422, ['ok' => false, 'error' => 'bad_type']);
}
if ($name === '' || mb_strlen($name) > 100) {
    respond(422, ['ok' => false, 'error' => 'bad_name']);
}
if (!preg_match('/^@?[A-Za-z0-9_]{4,32}$/', $tg)) {
    respond(422, ['ok' => false, 'error' => 'bad_telegram']);
}
$tg = '@' . ltrim($tg, '@');

// 6. Сообщение
$e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$text = "<b>{$e($titles[$type])}</b>\n\n"
      . "Имя: {$e($name)}\n"
      . "Telegram: {$e($tg)}\n"
      . "Время: " . date('d.m.Y H:i') . "\n"
      . "Согласие на обработку данных: да";

// 7. Отправка в Telegram
if (!function_exists('curl_init')) {
    error_log('send.php: cURL extension is not enabled');
    respond(500, ['ok' => false, 'error' => 'server_misconfigured']);
}
$ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => [
        'chat_id'                  => $chatId,
        'text'                     => $text,
        'parse_mode'               => 'HTML',
        'disable_web_page_preview' => true,
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT        => 10,
]);
$result = curl_exec($ch);
$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err    = curl_error($ch);
curl_close($ch);

$reply = is_string($result) ? json_decode($result, true) : null;
if ($status !== 200 || empty($reply['ok'])) {
    error_log('send.php: Telegram error ' . $status . ' ' . $err . ' ' . (string)$result);
    respond(502, ['ok' => false, 'error' => 'telegram_failed']);
}

// 8. Успех: фиксируем попытку для лимита и отвечаем
$hits[] = $now;
@file_put_contents($rlFile, json_encode($hits), LOCK_EX);
respond(200, ['ok' => true]);
