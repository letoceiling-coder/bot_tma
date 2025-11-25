<?php
/**
 * Тестовый скрипт для проверки реального API
 * 
 * Использование:
 * php test_api_real.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Тестирование реального API...\n\n";

// Получаем конфигурацию
$appUrl = config('app.url', 'http://localhost');
$baseUrl = rtrim($appUrl, '/');

echo "Base URL: {$baseUrl}\n\n";

// Тест 1: Получение списка каналов
echo "1️⃣ Тест: GET /api/v1/subscriptions/channels\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "{$baseUrl}/api/v1/subscriptions/channels");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";

if ($error) {
    echo "❌ cURL Error: {$error}\n";
    echo "\n💡 Попробуйте указать правильный APP_URL в .env\n";
    exit(1);
}

$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['success']) && $data['success']) {
    echo "✅ Успешно!\n\n";
    
    $channels = $data['data'] ?? [];
    
    echo "Найдено каналов: " . count($channels) . "\n\n";
    
    foreach ($channels as $channel) {
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "ID: {$channel['id']}\n";
        echo "Название: {$channel['title']}\n";
        echo "Username: {$channel['username']}\n";
        echo "URL: {$channel['url']}\n";
        echo "Telegram Chat ID: " . ($channel['telegram_chat_id'] ?? 'не указан (будет использован @username)') . "\n";
        echo "Обязательный: " . ($channel['isRequired'] ? 'Да' : 'Нет') . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }
    
    // Проверка на несоответствие username и URL
    foreach ($channels as $channel) {
        if (isset($channel['url']) && isset($channel['username'])) {
            $urlUsername = str_replace(['https://t.me/', 'http://t.me/', '@'], '', $channel['url']);
            $dbUsername = $channel['username'];
            
            if ($urlUsername !== $dbUsername) {
                echo "⚠️ ВНИМАНИЕ: Несоответствие username!\n";
                echo "   В базе: {$dbUsername}\n";
                echo "   В URL: {$urlUsername}\n";
                echo "   Рекомендуется исправить URL или username\n\n";
            }
        }
    }
    
} else {
    echo "❌ Ошибка!\n";
    echo "Ответ: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// Тест 2: Проверка через модель
echo "\n2️⃣ Тест: Получение каналов через модель\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$channels = \App\Models\Channel::where('is_active', true)
    ->where('is_required', true)
    ->orderBy('sort_order')
    ->get();

echo "Найдено каналов через модель: " . $channels->count() . "\n\n";

foreach ($channels as $channel) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "ID: {$channel->id}\n";
    echo "Название: {$channel->title}\n";
    echo "Username: {$channel->username}\n";
    echo "Telegram Chat ID: " . ($channel->telegram_chat_id ?? 'не указан') . "\n";
    echo "URL: {$channel->url}\n";
    echo "Chat ID для проверки (getChatIdForCheck): {$channel->getChatIdForCheck()}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
}

echo "✅ Тестирование завершено!\n";

