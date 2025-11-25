<?php
/**
 * Восстановление webhook после получения chat_id
 * 
 * Использование:
 * php restore_webhook.php [webhook_url]
 * 
 * Если URL не указан, будет использован сохраненный в .webhook_backup.txt
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$botToken = config('services.telegram.bot_token');

if (!$botToken) {
    echo "❌ Telegram bot token не настроен\n";
    exit(1);
}

$webhookUrl = $argv[1] ?? null;

// Если URL не указан, пробуем восстановить из файла
if (!$webhookUrl) {
    $backupFile = __DIR__ . '/.webhook_backup.txt';
    if (file_exists($backupFile)) {
        $webhookUrl = trim(file_get_contents($backupFile));
        echo "📋 Восстанавливаю webhook из файла: {$webhookUrl}\n\n";
    } else {
        echo "❌ Файл с webhook URL не найден: .webhook_backup.txt\n";
        echo "Укажите webhook URL вручную:\n";
        echo "php restore_webhook.php https://your-domain.com/webhook\n";
        exit(1);
    }
}

echo "🔧 Восстанавливаю webhook: {$webhookUrl}\n\n";

$verify = app()->environment('local') ? false : true;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.telegram.org/bot{$botToken}/setWebhook");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['url' => $webhookUrl]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($httpCode === 200 && isset($data['ok']) && $data['ok']) {
    echo "✅ Webhook успешно восстановлен!\n";
    echo "   URL: {$webhookUrl}\n";
} else {
    $error = $data['description'] ?? 'Unknown error';
    echo "❌ Ошибка при восстановлении webhook: {$error}\n";
    exit(1);
}

