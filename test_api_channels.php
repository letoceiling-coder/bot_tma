<?php
/**
 * Тестовый скрипт для проверки API каналов
 * 
 * Использование:
 * php test_api_channels.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Тестирование API каналов...\n\n";

// Тест 1: Получение списка каналов
echo "1️⃣ Тест: GET /api/v1/subscriptions/channels\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/api/v1/subscriptions/channels");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: {$httpCode}\n";

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
        echo "Telegram Chat ID: " . ($channel['telegram_chat_id'] ?? 'не указан') . "\n";
        echo "Обязательный: " . ($channel['isRequired'] ? 'Да' : 'Нет') . "\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    }
} else {
    echo "❌ Ошибка!\n";
    echo "Ответ: " . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// Тест 2: Проверка через модель напрямую
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
    echo "Chat ID для проверки: " . ($channel->getChatIdForCheck() ?? 'не определен') . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
}

echo "✅ Тестирование завершено!\n";

