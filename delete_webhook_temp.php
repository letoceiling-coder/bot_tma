<?php
/**
 * Временное удаление webhook для получения chat_id
 * 
 * ВАЖНО: После использования восстановите webhook через restore_webhook.php
 * 
 * Использование:
 * php delete_webhook_temp.php
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$botToken = config('services.telegram.bot_token');

if (!$botToken) {
    echo "❌ Telegram bot token не настроен\n";
    exit(1);
}

echo "⚠️ ВНИМАНИЕ: Вы собираетесь удалить webhook!\n";
echo "   После получения chat_id восстановите webhook!\n\n";
echo "Продолжить? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo "❌ Отменено\n";
    exit(0);
}

echo "\n🔍 Получаю информацию о текущем webhook...\n";

$verify = app()->environment('local') ? false : true;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/getWebhookInfo");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
curl_close($ch);

$webhookData = json_decode($response, true);

if (isset($webhookData['ok']) && $webhookData['ok']) {
    $webhookUrl = $webhookData['result']['url'] ?? null;
    
    if ($webhookUrl) {
        // Сохраняем URL для восстановления
        file_put_contents(__DIR__ . '/.webhook_backup.txt', $webhookUrl);
        echo "✅ Webhook URL сохранен: {$webhookUrl}\n";
        echo "   Файл: .webhook_backup.txt\n\n";
    }
}

echo "🗑️ Удаляю webhook...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/deleteWebhook");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['drop_pending_updates' => false]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['ok']) && $data['ok']) {
    echo "✅ Webhook успешно удален!\n\n";
    echo "📋 Теперь вы можете:\n";
    echo "   1. Запустить: php find_channel_after_bot_added.php\n";
    echo "   2. После получения chat_id восстановите webhook:\n";
    echo "      php restore_webhook.php\n";
} else {
    $error = $data['description'] ?? 'Unknown error';
    echo "❌ Ошибка при удалении webhook: {$error}\n";
    exit(1);
}

