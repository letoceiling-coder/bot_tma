<?php
/**
 * Скрипт для поиска канала ПОСЛЕ добавления бота
 * Использует getUpdates для поиска канала через обновления
 * 
 * Использование:
 * 1. Добавьте бота @sitesaccessbot в канал как администратора
 * 2. Отправьте ЛЮБОЕ сообщение в канал (любым пользователем)
 * 3. Запустите: php find_channel_after_bot_added.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$botToken = config('services.telegram.bot_token');

if (!$botToken) {
    echo "❌ Telegram bot token не настроен\n";
    exit(1);
}

echo "🔍 Поиск канала через getUpdates...\n";
echo "⚠️ Убедитесь что:\n";
echo "   1. Бот @sitesaccessbot добавлен в канал\n";
echo "   2. В канал отправлено сообщение\n\n";

$verify = app()->environment('local') ? false : true;

// Получаем последние обновления
echo "1️⃣ Получаю последние обновления от бота...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getUpdates?offset=-50&limit=50");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode !== 200 || !isset($data['ok']) || !$data['ok']) {
    echo "❌ Ошибка при получении обновлений\n";
    $error = $data['description'] ?? 'Unknown error';
    echo "Ошибка: {$error}\n";
    exit(1);
}

$updates = $data['result'] ?? [];

echo "✅ Получено обновлений: " . count($updates) . "\n\n";

if (empty($updates)) {
    echo "⚠️ Не найдено обновлений\n";
    echo "\n💡 Как получить обновления:\n";
    echo "   1. Добавьте бота @sitesaccessbot в канал @testkiabot\n";
    echo "   2. Назначьте бота администратором\n";
    echo "   3. Отправьте любое сообщение в канал (от любого пользователя)\n";
    echo "   4. Запустите скрипт снова\n";
    exit(1);
}

// Ищем каналы в обновлениях
echo "2️⃣ Ищу каналы в обновлениях...\n\n";

$channels = [];

foreach ($updates as $update) {
    $chat = null;
    $updateType = 'unknown';
    
    if (isset($update['message'])) {
        $chat = $update['message']['chat'] ?? null;
        $updateType = 'message';
    } elseif (isset($update['channel_post'])) {
        $chat = $update['channel_post']['chat'] ?? null;
        $updateType = 'channel_post';
    } elseif (isset($update['edited_message'])) {
        $chat = $update['edited_message']['chat'] ?? null;
        $updateType = 'edited_message';
    } elseif (isset($update['edited_channel_post'])) {
        $chat = $update['edited_channel_post']['chat'] ?? null;
        $updateType = 'edited_channel_post';
    }
    
    if ($chat) {
        $chatType = $chat['type'] ?? 'unknown';
        $chatId = $chat['id'] ?? null;
        
        // Ищем каналы (type = 'channel') или супергруппы (type = 'supergroup')
        if (($chatType === 'channel' || $chatType === 'supergroup') && $chatId) {
            $chatTitle = $chat['title'] ?? 'Unknown';
            $chatUsername = $chat['username'] ?? null;
            
            if (!isset($channels[$chatId])) {
                $channels[$chatId] = [
                    'id' => $chatId,
                    'type' => $chatType,
                    'title' => $chatTitle,
                    'username' => $chatUsername,
                    'found_in' => $updateType
                ];
            }
        }
    }
}

if (empty($channels)) {
    echo "⚠️ Каналы не найдены в обновлениях\n";
    echo "\n💡 Возможные причины:\n";
    echo "   - Бот не в канале\n";
    echo "   - В канал не отправлялись сообщения\n";
    echo "   - Канал слишком старый (обновления очищены)\n\n";
    echo "💡 Решения:\n";
    echo "   1. Добавьте бота @sitesaccessbot в канал\n";
    echo "   2. Отправьте НОВОЕ сообщение в канал\n";
    echo "   3. Запустите скрипт снова\n";
    exit(1);
}

echo "✅ Найдено каналов: " . count($channels) . "\n\n";

// Показываем найденные каналы
foreach ($channels as $channel) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Название: {$channel['title']}\n";
    echo "Chat ID: {$channel['id']}\n";
    echo "Тип: {$channel['type']}\n";
    if ($channel['username']) {
        echo "Username: @{$channel['username']}\n";
        
        // Проверяем совпадает ли username с искомым
        if (strtolower($channel['username']) === 'testkiabot') {
            echo "✅ Это искомый канал!\n";
        }
    } else {
        echo "Username: не указан (приватный канал)\n";
    }
    echo "Найден в: {$channel['found_in']}\n";
    echo "\n💡 Информация для админки:\n";
    echo "   Telegram Chat ID: {$channel['id']}\n";
    
    if ($channel['username']) {
        echo "   Или используйте: @{$channel['username']}\n";
    }
    
    echo "\n📝 Скопируйте Chat ID и укажите в админке:\n";
    echo "   /admin/settings/channels\n";
    echo "   В поле 'Telegram Chat ID': {$channel['id']}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
}

// Теперь проверяем статус бота в найденных каналах
echo "3️⃣ Проверяю статус бота в найденных каналах...\n\n";

// Получаем ID бота
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getMe");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$botData = json_decode($response, true);
$botId = $botData['result']['id'] ?? null;

if (!$botId) {
    echo "⚠️ Не удалось получить ID бота\n";
    exit(1);
}

foreach ($channels as $channel) {
    $chatId = $channel['id'];
    
    echo "Проверяю канал: {$channel['title']} (ID: {$chatId})...\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getChatMember?chat_id={$chatId}&user_id={$botId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $memberData = json_decode($response, true);
    
    if ($httpCode === 200 && isset($memberData['ok']) && $memberData['ok']) {
        $status = $memberData['result']['status'] ?? 'unknown';
        
        if ($status === 'administrator' || $status === 'creator') {
            echo "  ✅ Бот является администратором (статус: {$status})\n";
            echo "  ✅ Все готово! Проверка подписок будет работать.\n\n";
        } elseif ($status === 'member') {
            echo "  ⚠️ Бот является участником, но не администратором\n";
            echo "  💡 Назначьте бота администратором для проверки подписок\n\n";
        } else {
            echo "  ❌ Статус бота: {$status}\n\n";
        }
    } else {
        $error = $memberData['description'] ?? 'Unknown error';
        echo "  ❌ Не удалось проверить статус: {$error}\n\n";
    }
}

echo "✅ Проверка завершена!\n";

