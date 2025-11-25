<?php
/**
 * Проверка данных канала в базе
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Проверка данных канала в базе...\n\n";

// Проверяем все каналы
$channels = \App\Models\Channel::all();

echo "Всего каналов в базе: " . $channels->count() . "\n\n";

foreach ($channels as $channel) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "ID: {$channel->id}\n";
    echo "Название: {$channel->title}\n";
    echo "Username: {$channel->username}\n";
    echo "URL: {$channel->url}\n";
    echo "is_active (raw): " . var_export($channel->getRawOriginal('is_active'), true) . "\n";
    echo "is_active (cast): " . var_export($channel->is_active, true) . " (type: " . gettype($channel->is_active) . ")\n";
    echo "is_required (raw): " . var_export($channel->getRawOriginal('is_required'), true) . "\n";
    echo "is_required (cast): " . var_export($channel->is_required, true) . " (type: " . gettype($channel->is_required) . ")\n";
    echo "telegram_chat_id: " . ($channel->telegram_chat_id ?? 'null') . "\n";
    echo "getChatIdForCheck(): {$channel->getChatIdForCheck()}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
}

// Проверяем getRequiredChannels
echo "📋 Проверка метода getRequiredChannels():\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

$requiredChannels = \App\Models\Channel::getRequiredChannels();

echo "Найдено обязательных каналов: " . $requiredChannels->count() . "\n\n";

if ($requiredChannels->isEmpty()) {
    echo "⚠️ Не найдено обязательных каналов!\n";
    echo "\n💡 Возможные причины:\n";
    echo "   - is_active = false или 0\n";
    echo "   - is_required = false или 0\n";
    echo "   - Проблема с типами данных (boolean cast)\n";
} else {
    foreach ($requiredChannels as $channel) {
        echo "✅ Канал: {$channel->title} (@{$channel->username})\n";
    }
}

