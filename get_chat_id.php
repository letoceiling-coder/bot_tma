<?php
/**
 * Временный скрипт для получения chat_id Telegram канала
 * 
 * Использование:
 * php get_chat_id.php testingkiabot
 * 
 * Затем удалите этот файл!
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$botToken = config('services.telegram.bot_token');
$username = $argv[1] ?? null;

if (!$botToken) {
    echo "❌ Telegram bot token не настроен в config/services.php\n";
    echo "Добавьте TELEGRAM_BOT_TOKEN в .env файл\n";
    exit(1);
}

if (!$username) {
    echo "Использование: php get_chat_id.php <username>\n";
    echo "Пример: php get_chat_id.php testingkiabot\n";
    exit(1);
}

$username = ltrim($username, '@');

echo "🔍 Ищу канал: @{$username}\n\n";

$formatsToTry = ["@{$username}", $username];

foreach ($formatsToTry as $format) {
    echo "Пробую формат: {$format}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getChat?chat_id=" . urlencode($format));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['ok']) && $data['ok']) {
        $chat = $data['result'];
        $chatId = $chat['id'] ?? null;
        $chatType = $chat['type'] ?? 'unknown';
        $chatTitle = $chat['title'] ?? $chat['first_name'] ?? $username;
        
        echo "\n✅ Канал найден!\n\n";
        echo "Username: @{$username}\n";
        echo "Chat ID: {$chatId}\n";
        echo "Тип: {$chatType}\n";
        echo "Название: {$chatTitle}\n";
        echo "\n💡 Скопируйте Chat ID и укажите его в поле 'telegram_chat_id' в админке\n";
        echo "   (/admin/settings/channels)\n";
        exit(0);
    } else {
        $errorDescription = $data['description'] ?? 'Unknown error';
        if (isset($data['error_code']) && $data['error_code'] == 400 && stripos($errorDescription, 'chat not found') !== false) {
            echo "  ❌ Канал не найден: {$errorDescription}\n";
        } else {
            echo "  ⚠️  Ошибка: {$errorDescription}\n";
        }
    }
}

echo "\n❌ Не удалось получить информацию о канале\n\n";
echo "Возможные причины:\n";
echo "  1. Канал не существует с таким username\n";
echo "  2. Канал приватный (для приватных каналов нужно использовать числовой chat_id)\n";
echo "  3. Бот не имеет доступа к каналу\n\n";
echo "💡 Решения:\n";
echo "  • Убедитесь что username правильный (без @)\n";
echo "  • Добавьте бота в канал как участника или администратора\n";
echo "  • Для приватных каналов получите chat_id через getUpdates API\n";
exit(1);

