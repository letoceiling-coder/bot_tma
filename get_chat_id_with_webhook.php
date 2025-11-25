<?php
/**
 * Скрипт для получения chat_id канала при активном webhook
 * 
 * Использование:
 * php get_chat_id_with_webhook.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$botToken = config('services.telegram.bot_token');

if (!$botToken) {
    echo "❌ Telegram bot token не настроен\n";
    exit(1);
}

echo "🔍 Получение chat_id канала при активном webhook...\n\n";

$verify = app()->environment('local') ? false : true;

// Проверяем статус webhook
echo "1️⃣ Проверяю статус webhook...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getWebhookInfo");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$webhookData = json_decode($response, true);

if ($httpCode === 200 && isset($webhookData['ok']) && $webhookData['ok']) {
    $webhookInfo = $webhookData['result'] ?? [];
    $url = $webhookInfo['url'] ?? null;
    $pendingUpdateCount = $webhookInfo['pending_update_count'] ?? 0;
    $lastErrorDate = $webhookInfo['last_error_date'] ?? null;
    $lastErrorMessage = $webhookInfo['last_error_message'] ?? null;
    
    echo "Webhook URL: " . ($url ?: 'не настроен') . "\n";
    echo "Ожидающих обновлений: {$pendingUpdateCount}\n";
    
    if ($url) {
        echo "⚠️ Webhook активен! getUpdates недоступен.\n\n";
        
        if ($pendingUpdateCount > 0) {
            echo "💡 У вас есть {$pendingUpdateCount} ожидающих обновлений в webhook\n";
            echo "   Можно получить chat_id из этих обновлений через webhook endpoint\n\n";
        }
        
        echo "📋 Варианты получения chat_id:\n\n";
        
        echo "Вариант 1: Удалить webhook временно\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "1. Запустите: php delete_webhook_temp.php\n";
        echo "2. Запустите: php find_channel_after_bot_added.php\n";
        echo "3. Запустите: php restore_webhook.php (для восстановления webhook)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        echo "Вариант 2: Использовать @userinfobot в канале\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "1. Добавьте @userinfobot в канал\n";
        echo "2. Отправьте любое сообщение в канал\n";
        echo "3. @userinfobot покажет chat_id канала\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        
        echo "Вариант 3: Попробовать через getChat (если канал публичный)\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        
        // Пробуем получить через getChat с username из базы
        $channel = \App\Models\Channel::where('is_active', true)
            ->where('is_required', true)
            ->first();
        
        if ($channel && $channel->username) {
            $username = ltrim($channel->username, '@');
            echo "Пробую получить через getChat: @{$username}\n";
            
            $formats = ["@{$username}", $username];
            
            foreach ($formats as $format) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getChat?chat_id=" . urlencode($format));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                $data = json_decode($response, true);
                
                if ($httpCode === 200 && isset($data['ok']) && $data['ok']) {
                    $chat = $data['result'];
                    $chatId = $chat['id'] ?? null;
                    $chatTitle = $chat['title'] ?? 'Unknown';
                    $chatUsername = $chat['username'] ?? null;
                    
                    echo "✅ Канал найден!\n";
                    echo "   Chat ID: {$chatId}\n";
                    echo "   Название: {$chatTitle}\n";
                    if ($chatUsername) {
                        echo "   Username: @{$chatUsername}\n";
                    }
                    
                    echo "\n💡 Скопируйте Chat ID: {$chatId}\n";
                    echo "   Укажите в админке: /admin/settings/channels\n";
                    echo "   В поле 'Telegram Chat ID'\n";
                    
                    // Проверяем статус бота
                    echo "\n2️⃣ Проверяю статус бота в канале...\n";
                    
                    $botId = $webhookData['result']['id'] ?? null;
                    if (!$botId) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getMe");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        $response = curl_exec($ch);
                        curl_close($ch);
                        $botData = json_decode($response, true);
                        $botId = $botData['result']['id'] ?? null;
                    }
                    
                    if ($botId) {
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getChatMember?chat_id={$chatId}&user_id={$botId}");
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                        
                        $response = curl_exec($ch);
                        curl_close($ch);
                        
                        $memberData = json_decode($response, true);
                        
                        if (isset($memberData['ok']) && $memberData['ok']) {
                            $status = $memberData['result']['status'] ?? 'unknown';
                            
                            if ($status === 'administrator' || $status === 'creator') {
                                echo "✅ Бот является администратором (статус: {$status})\n";
                                echo "✅ Все готово! Проверка подписок будет работать.\n";
                            } else {
                                echo "⚠️ Бот НЕ является администратором (статус: {$status})\n";
                                echo "💡 Добавьте бота как администратора в канал\n";
                            }
                        }
                    }
                    
                    exit(0);
                } else {
                    $error = $data['description'] ?? 'Unknown error';
                    echo "   ❌ Формат '{$format}': {$error}\n";
                }
            }
            
            echo "❌ Не удалось получить канал через getChat\n";
            echo "   Это может означать что:\n";
            echo "   - Канал приватный\n";
            echo "   - Бот не в канале\n";
            echo "   - Канал не существует\n\n";
        }
        
        echo "Рекомендуется использовать Вариант 1 или Вариант 2\n";
    } else {
        echo "✅ Webhook не настроен, можно использовать getUpdates\n";
        echo "Запустите: php find_channel_after_bot_added.php\n";
    }
} else {
    echo "❌ Не удалось получить информацию о webhook\n";
}

