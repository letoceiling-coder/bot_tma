<?php
/**
 * Скрипт для получения chat_id канала ПОСЛЕ добавления бота
 * 
 * Использование:
 * 1. Добавьте бота @sitesaccessbot в канал как администратора
 * 2. Отправьте любое сообщение в канал
 * 3. Запустите: php get_chat_id_after_add.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$botToken = config('services.telegram.bot_token');

if (!$botToken) {
    echo "❌ Telegram bot token не настроен\n";
    exit(1);
}

echo "🔍 Получаю chat_id канала через getUpdates...\n";
echo "⚠️ Убедитесь что бот добавлен в канал и отправлено сообщение!\n\n";

$verify = app()->environment('local') ? false : true;

// Получаем последние обновления
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getUpdates?offset=-10&limit=10");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode !== 200 || !isset($data['ok']) || !$data['ok']) {
    echo "❌ Ошибка при получении обновлений\n";
    exit(1);
}

$updates = $data['result'] ?? [];

if (empty($updates)) {
    echo "⚠️ Не найдено обновлений\n";
    echo "\n💡 Как получить chat_id:\n";
    echo "   1. Добавьте бота @sitesaccessbot в канал\n";
    echo "   2. Отправьте любое сообщение в канал\n";
    echo "   3. Запустите этот скрипт снова\n";
    exit(1);
}

echo "Найдено обновлений: " . count($updates) . "\n\n";

$channels = [];

foreach ($updates as $update) {
    $chat = null;
    
    if (isset($update['message'])) {
        $chat = $update['message']['chat'] ?? null;
    } elseif (isset($update['channel_post'])) {
        $chat = $update['channel_post']['chat'] ?? null;
    }
    
    if ($chat && isset($chat['type']) && $chat['type'] === 'channel') {
        $chatId = $chat['id'] ?? null;
        $chatTitle = $chat['title'] ?? 'Unknown';
        $chatUsername = $chat['username'] ?? null;
        
        if ($chatId && !isset($channels[$chatId])) {
            $channels[$chatId] = [
                'id' => $chatId,
                'title' => $chatTitle,
                'username' => $chatUsername
            ];
        }
    }
}

if (empty($channels)) {
    echo "⚠️ Каналы не найдены в обновлениях\n";
    echo "\n💡 Попробуйте:\n";
    echo "   1. Отправить сообщение в канал\n";
    echo "   2. Или использовать @userinfobot в канале\n";
    exit(1);
}

echo "✅ Найденные каналы:\n\n";

foreach ($channels as $channel) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Название: {$channel['title']}\n";
    echo "Chat ID: {$channel['id']}\n";
    if ($channel['username']) {
        echo "Username: @{$channel['username']}\n";
    }
    echo "\n💡 Скопируйте Chat ID: {$channel['id']}\n";
    echo "   Укажите его в админке: /admin/settings/channels\n";
    echo "   В поле 'Telegram Chat ID'\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
}

