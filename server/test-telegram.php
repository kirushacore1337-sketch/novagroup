<?php
declare(strict_types=1);
/**
 * Диагностика интеграции с Telegram. Запуск из корня проекта:
 *   php server/test-telegram.php
 * Проверяет PHP, config.php, токен, chat_id и отправляет тестовое сообщение.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function ok(string $m): void   { echo "  [OK]    $m\n"; }
function bad(string $m): void  { echo "  [ОШИБКА] $m\n"; }
function hint(string $m): void { echo "          -> $m\n"; }
function step(string $m): void { echo "\n$m\n"; }

function tg(string $token, string $method, array $params = []): array
{
    $ch = curl_init("https://api.telegram.org/bot{$token}/{$method}");
    curl_setopt_array($ch, [
        CURLOPT_POST => true, CURLOPT_POSTFIELDS => $params,
        CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 7, CURLOPT_TIMEOUT => 15,
    ]);
    $body = curl_exec($ch);
    $res = [(int)curl_getinfo($ch, CURLINFO_HTTP_CODE), is_string($body) ? json_decode($body, true) : null,
            curl_error($ch), curl_errno($ch)];
    curl_close($ch);
    return $res;
}

function netHint(int $errno): void
{
    match (true) {
        $errno === 60 => hint('У PHP нет сертификатов. Скачай cacert.pem и пропиши curl.cainfo и openssl.cafile в php.ini, потом перезапусти сервер.'),
        $errno === 6  => hint('Не находится адрес api.telegram.org: проверь интернет и DNS.'),
        in_array($errno, [7, 28], true) => hint('Нет соединения с Telegram. Если он у тебя блокируется, включи VPN.'),
        default       => hint('Смотри текст ошибки выше.'),
    };
}

$fail = false;
echo "=== Проверка интеграции с Telegram ===\n";

step('1. PHP и расширения');
ok('PHP ' . PHP_VERSION);
foreach (['curl', 'openssl', 'mbstring'] as $ext) {
    if (extension_loaded($ext)) { ok("расширение $ext включено"); }
    else { bad("расширение $ext выключено"); hint("В php.ini раскомментируй extension=$ext и перезапусти сервер."); $fail = true; }
}
if ($fail) { echo "\nСначала исправь расширения.\n"; exit(1); }

step('2. Файл config.php');
$file = __DIR__ . '/config.php';
if (!is_file($file)) { bad('server/config.php не найден'); exit(1); }
$cfg = require $file;
$token = trim((string)($cfg['bot_token'] ?? ''));
$chat  = trim((string)($cfg['chat_id'] ?? ''));
if ($token === '' || str_contains($token, 'ВАШ')) { bad('bot_token не заполнен'); exit(1); }
if (!preg_match('/^\d{6,}:[A-Za-z0-9_-]{30,}$/', $token)) {
    bad('bot_token выглядит неправильно (ожидается вид 123456789:AAH...)');
    hint('Скопируй токен из @BotFather целиком, без пробелов, кавычек и переносов строк.');
    exit(1);
}
ok('bot_token заполнен и похож на настоящий');
$botId = explode(':', $token)[0];

step('3. Токен (getMe)');
[$code, $json, $err, $errno] = tg($token, 'getMe');
if ($err !== '') { bad("не удалось связаться с Telegram: $err"); netHint($errno); exit(1); }
if ($code !== 200 || empty($json['ok'])) {
    bad("Telegram ответил $code: " . ($json['description'] ?? 'без описания'));
    hint('Токен неверный или отозван. Возьми новый в @BotFather командой /token и вставь в config.php.');
    exit(1);
}
ok('Бот найден: @' . ($json['result']['username'] ?? '?'));

step('4. chat_id');
if ($chat === '' || str_contains($chat, 'ВАШ')) {
    bad('chat_id не заполнен');
} elseif ($chat === $botId) {
    bad('chat_id совпадает с ID бота (числом из начала токена). Нужен ID твоего чата, а не бота.');
} else {
    ok("chat_id задан: $chat");
}
[$c2, $upd] = tg($token, 'getUpdates');
$ids = [];
foreach (($upd['result'] ?? []) as $u) {
    $chatInfo = $u['message']['chat'] ?? $u['my_chat_member']['chat'] ?? null;
    if ($chatInfo) { $ids[(string)$chatInfo['id']] = $chatInfo['title'] ?? ($chatInfo['first_name'] ?? '?'); }
}
if ($ids) {
    echo "  Чаты, которые писали боту:\n";
    foreach ($ids as $id => $name) { echo "     chat_id = $id  ($name)\n"; }
} else {
    echo "  Боту пока никто не писал. Открой бота в Telegram, нажми Start, напиши любое сообщение и запусти скрипт снова.\n";
}

step('5. Тестовое сообщение');
if ($chat === '' || str_contains($chat, 'ВАШ') || $chat === $botId) { bad('пропущено: сначала исправь chat_id'); exit(1); }
[$code, $json, $err, $errno] = tg($token, 'sendMessage', ['chat_id' => $chat, 'text' => 'Тест: интеграция лендинга с Telegram работает']);
if ($err !== '') { bad("ошибка соединения: $err"); netHint($errno); exit(1); }
if ($code === 200 && !empty($json['ok'])) { ok('Сообщение отправлено, проверь Telegram.'); echo "\nВсё работает. Можно отправлять форму на сайте.\n"; exit(0); }
$d = (string)($json['description'] ?? '');
bad("Telegram ответил $code: $d");
if (stripos($d, 'chat not found') !== false) hint('Неверный chat_id, либо ты не нажал Start у бота. Возьми число из списка выше.');
elseif (stripos($d, "can't initiate") !== false || stripos($d, 'blocked') !== false) hint('Открой бота в Telegram, нажми Start (или разблокируй) и повтори.');
elseif (stripos($d, 'not enough rights') !== false || stripos($d, 'kicked') !== false) hint('Бот не состоит в группе или у него нет прав писать. Добавь его в группу и выдай право отправлять сообщения.');
exit(1);
