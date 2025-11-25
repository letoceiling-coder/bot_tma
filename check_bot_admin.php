<?php
/**
 * Скрипт для проверки является ли бот администратором канала
 * 
 * Использование:
 * php check_bot_admin.php testingkiabot
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

$username = $argv[1] ?? null;

if (!$username) {
    echo "Использование: php check_bot_admin.php <username>\n";
    echo "Пример: php check_bot_admin.php testingkiabot\n";
    exit(1);
}

$username = ltrim($username, '@');

echo "🔍 Проверяю статус бота в канале: @{$username}\n\n";

// Сначала получаем информацию о боте
echo "1️⃣ Получаю информацию о боте...\n";
$verify = app()->environment('local') ? false : true;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getMe");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode !== 200 || !isset($data['ok']) || !$data['ok']) {
    echo "❌ Не удалось получить информацию о боте\n";
    exit(1);
}

$botId = $data['result']['id'] ?? null;
$botUsername = $data['result']['username'] ?? 'Unknown';

echo "✅ Информация о боте получена:\n";
echo "   Bot ID: {$botId}\n";
echo "   Bot Username: @{$botUsername}\n\n";

// Теперь проверяем информацию о канале
echo "2️⃣ Получаю информацию о канале...\n";

$formatsToTry = ["@{$username}", $username];

foreach ($formatsToTry as $format) {
    echo "   Пробую формат: {$format}\n";
    
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
        $chatType = $chat['type'] ?? 'unknown';
        $chatTitle = $chat['title'] ?? $username;
        
        echo "   ✅ Канал найден!\n";
        echo "   Chat ID: {$chatId}\n";
        echo "   Тип: {$chatType}\n";
        echo "   Название: {$chatTitle}\n\n";
        
        // Проверяем статус бота в канале
        echo "3️⃣ Проверяю статус бота в канале...\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getChatMember?chat_id=" . urlencode($chatId) . "&user_id={$botId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $memberData = json_decode($response, true);
        
        if ($httpCode === 200 && isset($memberData['ok']) && $memberData['ok']) {
            $member = $memberData['result'] ?? null;
            $status = $member['status'] ?? 'unknown';
            
            echo "\n📊 Результат проверки:\n";
            echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
            echo "Статус бота: {$status}\n";
            
            if ($status === 'administrator') {
                echo "✅ БОТ ЯВЛЯЕТСЯ АДМИНИСТРАТОРОМ\n";
                
                if (isset($member['can_restrict_members'])) {
                    echo "\nПрава администратора:\n";
                    echo "  • Может ограничивать участников: " . ($member['can_restrict_members'] ? 'Да' : 'Нет') . "\n";
                    echo "  • Может удалять сообщения: " . (isset($member['can_delete_messages']) && $member['can_delete_messages'] ? 'Да' : 'Нет') . "\n";
                    echo "  • Может приглашать пользователей: " . (isset($member['can_invite_users']) && $member['can_invite_users'] ? 'Да' : 'Нет') . "\n";
                }
                
                echo "\n✅ Все в порядке! Бот может проверять подписки.\n";
            } elseif ($status === 'creator') {
                echo "✅ БОТ ЯВЛЯЕТСЯ СОЗДАТЕЛЕМ КАНАЛА\n";
                echo "✅ Все в порядке! Бот может проверять подписки.\n";
            } elseif ($status === 'member') {
                echo "⚠️  БОТ ЯВЛЯЕТСЯ ОБЫЧНЫМ УЧАСТНИКОМ\n";
                echo "\n❌ ПРОБЛЕМА: Бот не может проверять подписки!\n";
                echo "\n💡 Как исправить:\n";
                echo "   1. Откройте канал @{$username} в Telegram\n";
                echo "   2. Нажмите на название канала (вверху)\n";
                echo "   3. Выберите 'Администраторы' или 'Управление каналом'\n";
                echo "   4. Нажмите 'Добавить администратора'\n";
                echo "   5. Найдите бота @{$botUsername} и добавьте его\n";
                echo "   6. Убедитесь что бот имеет права 'Просмотр подписчиков'\n";
            } elseif ($status === 'left') {
                echo "❌ БОТ НЕ В КАНАЛЕ (покинул канал)\n";
                echo "\n💡 Как исправить:\n";
                echo "   1. Откройте канал @{$username} в Telegram\n";
                echo "   2. Добавьте бота @{$botUsername} в канал\n";
                echo "   3. Назначьте бота администратором\n";
            } elseif ($status === 'kicked') {
                echo "❌ БОТ ЗАБЛОКИРОВАН В КАНАЛЕ\n";
                echo "\n💡 Как исправить:\n";
                echo "   1. Разблокируйте бота @{$botUsername} в канале\n";
                echo "   2. Добавьте бота в канал снова\n";
                echo "   3. Назначьте бота администратором\n";
            } else {
                echo "❓ Неизвестный статус: {$status}\n";
            }
            
            exit(0);
        } else {
            $errorCode = $memberData['error_code'] ?? null;
            $errorDescription = $memberData['description'] ?? 'Unknown error';
            
            echo "\n❌ Ошибка при проверке статуса бота:\n";
            echo "   Код ошибки: {$errorCode}\n";
            echo "   Описание: {$errorDescription}\n\n";
            
            if ($errorCode == 400 && stripos($errorDescription, 'user not found') !== false) {
                echo "💡 Это означает что бот не в канале или не имеет доступа.\n";
                echo "\nКак исправить:\n";
                echo "   1. Добавьте бота @{$botUsername} в канал @{$username}\n";
                echo "   2. Назначьте бота администратором\n";
                echo "   3. Убедитесь что бот имеет права 'Просмотр подписчиков'\n";
            } elseif ($errorCode == 403) {
                echo "💡 Это означает что у бота нет прав для проверки подписок.\n";
                echo "\nКак исправить:\n";
                echo "   1. Откройте канал @{$username} в Telegram\n";
                echo "   2. Перейдите в 'Администраторы'\n";
                echo "   3. Найдите бота @{$botUsername}\n";
                echo "   4. Включите права 'Просмотр подписчиков' или 'Статистика канала'\n";
            }
        }
        
        break;
    } else {
        $errorCode = $data['error_code'] ?? null;
        $errorDescription = $data['description'] ?? 'Unknown error';
        
        if ($errorCode == 400 && stripos($errorDescription, 'chat not found') !== false) {
            echo "   ❌ Канал не найден: {$errorDescription}\n";
        } else {
            echo "   ⚠️  Ошибка: {$errorDescription}\n";
        }
    }
}

echo "\n❌ Не удалось получить информацию о канале или статусе бота\n";
exit(1);

