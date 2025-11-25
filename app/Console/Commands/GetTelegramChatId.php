<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Channel;

class GetTelegramChatId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:get-chat-id {username? : Telegram channel username (without @)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Получить chat_id Telegram канала через Bot API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $botToken = config('services.telegram.bot_token');
        
        if (!$botToken) {
            $this->error('❌ Telegram bot token не настроен в config/services.php');
            $this->info('Добавьте TELEGRAM_BOT_TOKEN в .env файл');
            return 1;
        }

        $username = $this->argument('username');
        
        if (!$username) {
            // Если username не указан, показываем список каналов из БД
            $channels = Channel::where('is_active', true)->get();
            
            if ($channels->isEmpty()) {
                $this->info('📋 Каналы не найдены в базе данных');
                $username = $this->ask('Введите username канала (без @)');
            } else {
                $this->info('📋 Доступные каналы:');
                $channelsList = [];
                foreach ($channels as $channel) {
                    $channelsList[] = $channel->username;
                    $this->line("  • {$channel->title} (@{$channel->username})");
                    if ($channel->telegram_chat_id) {
                        $this->line("    Chat ID: {$channel->telegram_chat_id}");
                    }
                }
                
                $username = $this->choice('Выберите канал или введите новый username', array_merge($channelsList, ['Другой...']));
                
                if ($username === 'Другой...') {
                    $username = $this->ask('Введите username канала (без @)');
                }
            }
        }

        if (!$username) {
            $this->error('❌ Username не указан');
            return 1;
        }

        // Убираем @ если указан
        $username = ltrim($username, '@');
        
        $this->info("🔍 Ищу канал: @{$username}");
        $this->newLine();

        // Пробуем разные форматы
        $formatsToTry = [
            "@{$username}",
            $username
        ];

        $success = false;

        foreach ($formatsToTry as $format) {
            $this->line("Пробую формат: {$format}");
            
            try {
                // Для локальной разработки отключаем проверку SSL
                $verify = app()->environment('local') ? false : true;
                
                $response = Http::timeout(10)
                    ->withoutVerifying($verify === false)
                    ->get("https://api.telegram.org/bot{$botToken}/getChat", [
                        'chat_id' => $format
                    ]);

                $data = $response->json();

                if ($response->successful() && isset($data['ok']) && $data['ok']) {
                    $chat = $data['result'];
                    $chatId = $chat['id'] ?? null;
                    $chatType = $chat['type'] ?? 'unknown';
                    $chatTitle = $chat['title'] ?? $chat['first_name'] ?? $username;
                    
                    $this->newLine();
                    $this->info('✅ Канал найден!');
                    $this->table(
                        ['Параметр', 'Значение'],
                        [
                            ['Username', "@{$username}"],
                            ['Chat ID', $chatId ?? 'N/A'],
                            ['Тип', $chatType],
                            ['Название', $chatTitle],
                            ['Формат для API', $format]
                        ]
                    );

                    // Проверяем есть ли канал в БД
                    $channel = Channel::where('username', $username)->first();
                    if ($channel) {
                        $this->newLine();
                        if ($channel->telegram_chat_id != $chatId) {
                            if ($this->confirm("Обновить telegram_chat_id в базе данных для канала '{$channel->title}'?")) {
                                $channel->telegram_chat_id = $chatId;
                                $channel->save();
                                $this->info("✅ Chat ID обновлен в базе данных");
                            }
                        } else {
                            $this->info("ℹ️  Chat ID уже соответствует значению в базе данных");
                        }
                    } else {
                        $this->newLine();
                        $this->warn("⚠️  Канал не найден в базе данных. Создайте его через админку: /admin/settings/channels");
                    }

                    // Проверяем, является ли бот администратором
                    $this->newLine();
                    $this->info('🔐 Проверка прав доступа бота...');
                    
                    $verify = app()->environment('local') ? false : true;
                    $memberResponse = Http::timeout(10)
                        ->withoutVerifying($verify === false)
                        ->get("https://api.telegram.org/bot{$botToken}/getChatMember", [
                            'chat_id' => $chatId,
                            'user_id' => $this->getBotUserId($botToken)
                        ]);

                    $memberData = $memberResponse->json();
                    if ($memberResponse->successful() && isset($memberData['ok']) && $memberData['ok']) {
                        $status = $memberData['result']['status'] ?? null;
                        if (in_array($status, ['administrator', 'creator'])) {
                            $this->info("✅ Бот является администратором канала (статус: {$status})");
                        } else {
                            $this->warn("⚠️  Бот НЕ является администратором (статус: {$status})");
                            $this->info("💡 Добавьте бота как администратора в канал для проверки подписок");
                        }
                    } else {
                        $this->error("❌ Не удалось проверить права бота");
                        $this->line("Ответ: " . json_encode($memberData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
                    }

                    $success = true;
                    break;
                } else {
                    $errorCode = $data['error_code'] ?? null;
                    $errorDescription = $data['description'] ?? 'Unknown error';
                    
                    if ($errorCode == 400 && stripos($errorDescription, 'chat not found') !== false) {
                        $this->warn("  ❌ Канал не найден: {$errorDescription}");
                    } else {
                        $this->warn("  ⚠️  Ошибка: {$errorDescription} (код: {$errorCode})");
                    }
                }
            } catch (\Exception $e) {
                $this->warn("  ❌ Исключение: {$e->getMessage()}");
            }
            
            $this->newLine();
        }

        if (!$success) {
            $this->newLine();
            $this->error('❌ Не удалось получить информацию о канале');
            $this->newLine();
            $this->info('Возможные причины:');
            $this->line('  1. Канал не существует с таким username');
            $this->line('  2. Канал приватный (для приватных каналов нужно использовать числовой chat_id)');
            $this->line('  3. Бот не имеет доступа к каналу');
            $this->newLine();
            $this->info('💡 Решения:');
            $this->line('  • Убедитесь что username правильный (без @)');
            $this->line('  • Добавьте бота в канал как участника');
            $this->line('  • Для приватных каналов:');
            $this->line('    1. Добавьте бота как администратора');
            $this->line('    2. Отправьте любое сообщение в канал');
            $this->line('    3. Используйте getUpdates API для получения chat_id');
            $this->line('    4. Или используйте команду: php artisan telegram:get-chat-id --private');
            
            return 1;
        }

        return 0;
    }

    /**
     * Получить ID бота через getMe
     */
    protected function getBotUserId(string $botToken): ?int
    {
        try {
            $verify = app()->environment('local') ? false : true;
            $response = Http::timeout(10)
                ->withoutVerifying($verify === false)
                ->get("https://api.telegram.org/bot{$botToken}/getMe");
            $data = $response->json();
            
            if ($response->successful() && isset($data['ok']) && $data['ok']) {
                return $data['result']['id'] ?? null;
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки
        }
        
        return null;
    }
}
