<?php
/**
 * Скрипт для получения chat_id приватного канала через getUpdates
 * 
 * Использование:
 * 1. Добавьте бота как администратора в канал
 * 2. Отправьте любое сообщение в канал от бота или любое сообщение (если бот администратор)
 * 3. Запустите: php get_chat_id_from_updates.php
 * 
 * Затем удалите этот файл!
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$botToken = config('services.telegram.bot_token');

if (!$botToken) {
    echo "❌ Telegram bot token не настроен в config/services.php\n";
    echo "Добавьте TELEGRAM_BOT_TOKEN в .env файл\n";
    exit(1);
}

echo "🔍 Получаю последние обновления от бота...\n\n";

// Получаем последние обновления
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getUpdates?offset=-10&limit=10");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode !== 200 || !isset($data['ok']) || !$data['ok']) {
    echo "❌ Ошибка при получении обновлений\n";
    $errorDescription = $data['description'] ?? 'Unknown error';
    echo "Ошибка: {$errorDescription}\n";
    exit(1);
}

$updates = $data['result'] ?? [];

if (empty($updates)) {
    echo "⚠️  Не найдено обновлений\n\n";
    echo "💡 Как получить chat_id:\n";
    echo "  1. Добавьте бота как администратора в канал\n";
    echo "  2. Отправьте любое сообщение в канал\n";
    echo "  3. Запустите этот скрипт снова\n";
    echo "\n";
    echo "Или используйте альтернативные способы:\n";
    echo "  • Добавьте бота в канал и отправьте сообщение через бота\n";
    echo "  • Используйте @userinfobot в канале - он покажет chat_id\n";
    echo "  • Используйте @RawDataBot в канале - он покажет все данные\n";
    exit(1);
}

echo "Найдено обновлений: " . count($updates) . "\n\n";

$chats = [];

foreach ($updates as $update) {
    // Проверяем различные типы обновлений
    $chat = null;
    
    if (isset($update['message'])) {
        $chat = $update['message']['chat'] ?? null;
    } elseif (isset($update['channel_post'])) {
        $chat = $update['channel_post']['chat'] ?? null;
    } elseif (isset($update['edited_message'])) {
        $chat = $update['edited_message']['chat'] ?? null;
    } elseif (isset($update['edited_channel_post'])) {
        $chat = $update['edited_channel_post']['chat'] ?? null;
    }
    
    if ($chat) {
        $chatId = $chat['id'] ?? null;
        $chatType = $chat['type'] ?? 'unknown';
        $chatTitle = $chat['title'] ?? ($chat['first_name'] ?? 'Unknown');
        $chatUsername = $chat['username'] ?? null;
        
        if ($chatId && !isset($chats[$chatId])) {
            $chats[$chatId] = [
                'id' => $chatId,
                'type' => $chatType,
                'title' => $chatTitle,
                'username' => $chatUsername
            ];
        }
    }
}

if (empty($chats)) {
    echo "❌ Не найдено чатов в обновлениях\n";
    echo "Попробуйте отправить сообщение в канал и запустить скрипт снова\n";
    exit(1);
}

echo "✅ Найдены чаты:\n\n";

$table = [];
$table[] = ['Chat ID', 'Тип', 'Название', 'Username'];

foreach ($chats as $chat) {
    $table[] = [
        $chat['id'],
        $chat['type'],
        $chat['title'],
        $chat['username'] ? '@' . $chat['username'] : '-'
    ];
}

// Простой вывод таблицы
$maxLengths = [];
foreach ($table[0] as $i => $header) {
    $maxLengths[$i] = strlen($header);
}

foreach ($table as $row) {
    foreach ($row as $i => $cell) {
        $maxLengths[$i] = max($maxLengths[$i], strlen($cell));
    }
}

echo str_repeat('=', array_sum($maxLengths) + (count($maxLengths) * 3) + 1) . "\n";
foreach ($table as $rowIndex => $row) {
    $line = '| ';
    foreach ($row as $i => $cell) {
        $line .= str_pad($cell, $maxLengths[$i]) . ' | ';
    }
    echo $line . "\n";
    if ($rowIndex === 0) {
        echo str_repeat('-', array_sum($maxLengths) + (count($maxLengths) * 3) + 1) . "\n";
    }
}
echo str_repeat('=', array_sum($maxLengths) + (count($maxLengths) * 3) + 1) . "\n\n";

// Фильтруем только каналы (type = 'channel' или id отрицательный и большой)
$channels = array_filter($chats, function($chat) {
    return $chat['type'] === 'channel' || ($chat['id'] < 0 && abs($chat['id']) > 1000000000);
});

if (!empty($channels)) {
    echo "📢 Найденные каналы:\n\n";
    foreach ($channels as $channel) {
        echo "Chat ID: {$channel['id']}\n";
        echo "Тип: {$channel['type']}\n";
        echo "Название: {$channel['title']}\n";
        if ($channel['username']) {
            echo "Username: @{$channel['username']}\n";
        }
        echo "\n";
        echo "💡 Скопируйте Chat ID: {$channel['id']}\n";
        echo "   Укажите его в админке: /admin/settings/channels\n";
        echo "   В поле 'Telegram Chat ID' для соответствующего канала\n\n";
    }
} else {
    echo "⚠️  Каналы не найдены в обновлениях\n";
    echo "Найдены только группы или личные чаты\n\n";
    
    $firstChat = reset($chats);
    echo "Если вы искали канал, попробуйте:\n";
    echo "  1. Добавить бота как администратора в канал\n";
    echo "  2. Отправить сообщение в канал\n";
    echo "  3. Запустить скрипт снова\n\n";
    
    echo "Или используйте альтернативные боты:\n";
    echo "  • @userinfobot - добавьте в канал, он покажет chat_id\n";
    echo "  • @RawDataBot - добавьте в канал, он покажет все данные\n";
}

echo "\n";

