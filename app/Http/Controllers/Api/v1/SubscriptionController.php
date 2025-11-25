<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LogsRequestParameters;
use App\Helpers\TelegramInitDataValidator;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SubscriptionController extends Controller
{
    use LogsRequestParameters;
    /**
     * Проверка подписок пользователя на каналы
     * 
     * @OA\Get(
     *     path="/subscriptions/check",
     *     tags={"Subscriptions"},
     *     summary="Проверить подписки пользователя",
     *     description="Проверяет подписку пользователя на все обязательные каналы через Telegram Bot API",
     *     @OA\Parameter(
     *         name="initData",
     *         in="query",
     *         required=true,
     *         description="InitData от Telegram WebApp",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="allSubscribed", type="boolean", example=true),
     *             @OA\Property(property="channels", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="subscribed", type="boolean")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Неавторизован"
     *     )
     * )
     */
    public function check(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'subscriptions/check');
        
        try {
            $initData = $request->get('initData');
            $force = $request->boolean('force', false); // Принудительная проверка (игнорировать кеш)
            
            if (!$initData) {
                return response()->json([
                    'success' => false,
                    'message' => 'initData обязателен'
                ], 400);
            }

            // Валидируем initData
            $botToken = config('services.telegram.bot_token');
            
            if (!$botToken) {
                Log::error('Telegram bot token not configured');
                return response()->json([
                    'success' => false,
                    'message' => 'Сервис временно недоступен'
                ], 500);
            }

            // Пытаемся валидировать полный initData
            $isValid = TelegramInitDataValidator::validate($initData, $botToken);
            
            // Извлекаем user_id (работает даже если hash невалидный)
            $userId = TelegramInitDataValidator::getUserId($initData);
            
            // Если нет user_id, данные точно невалидны
            if (!$userId) {
                // Попробуем извлечь из неполного initData (fallback для dev режима)
                parse_str($initData, $parsed);
                if (isset($parsed['user'])) {
                    $userData = json_decode($parsed['user'], true);
                    $userId = $userData['id'] ?? null;
                }
            }
            
            // Если user_id все еще нет, отклоняем запрос
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось определить пользователя'
                ], 400);
            }
            
            // В продакшене требуем валидацию, в dev режиме можем пропустить
            if (!$isValid && app()->environment('production')) {
                Log::warning('Invalid initData in production', [
                    'user_id' => $userId,
                    'initData_length' => strlen($initData)
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Невалидные данные от Telegram'
                ], 401);
            }

            // Получаем обязательные каналы
            $channels = Channel::getRequiredChannels();
            
            // Проверяем был ли запрос через WebApp API (force=true обычно означает что WebApp API не сработал)
            $requestSource = $request->get('source', 'unknown'); // 'webapp' или 'backend'
            $isWebAppCheck = $requestSource === 'webapp';
            
            Log::info('Subscription check request', [
                'user_id' => $userId,
                'channels_count' => $channels->count(),
                'is_valid' => $isValid,
                'force' => $force,
                'source' => $requestSource,
                'is_webapp_api_attempted' => $isWebAppCheck,
                'environment' => app()->environment(),
                'note' => $isWebAppCheck ? 'Request indicates WebApp API was attempted' : 'Direct backend API request (WebApp API likely not available or failed)'
            ]);
            
            if ($channels->isEmpty()) {
                // Если нет обязательных каналов, считаем что все подписаны
                Log::info('No required channels found, allowing access', [
                    'user_id' => $userId
                ]);
                return response()->json([
                    'success' => true,
                    'allSubscribed' => true,
                    'channels' => []
                ]);
            }

            // Проверяем подписки с кешированием на 5 минут
            $cacheKey = "subscriptions_check_{$userId}";
            $cachedResult = $force ? null : Cache::get($cacheKey);
            
            Log::info('Cache check', [
                'user_id' => $userId,
                'cache_key' => $cacheKey,
                'has_cache' => $cachedResult !== null,
                'force' => $force,
                'cached_all_subscribed' => $cachedResult['allSubscribed'] ?? null
            ]);
            
            if ($cachedResult !== null && !$force) {
                Log::info('Returning cached subscription result', [
                    'user_id' => $userId,
                    'allSubscribed' => $cachedResult['allSubscribed'],
                    'channels_count' => count($cachedResult['channels'] ?? []),
                    'source' => $requestSource,
                    'note' => 'Cache hit - returning previous result'
                ]);
                return response()->json([
                    'success' => true,
                    'allSubscribed' => $cachedResult['allSubscribed'],
                    'channels' => $cachedResult['channels'],
                    'cached' => true
                ]);
            }
            
            // Если force=true, очищаем кеш перед проверкой
            if ($force) {
                Cache::forget($cacheKey);
                Log::info('Cache cleared due to force check', ['user_id' => $userId]);
            }

            $subscriptions = [];
            $allSubscribed = true;

            Log::info('Starting subscription checks', [
                'user_id' => $userId,
                'channels_count' => $channels->count()
            ]);

            foreach ($channels as $channel) {
                $subscribed = $this->checkUserSubscription($userId, $channel, $botToken);
                $subscriptions[] = [
                    'id' => $channel->id,
                    'title' => $channel->title,
                    'username' => $channel->username,
                    'url' => $channel->url,
                    'subscribed' => $subscribed
                ];

                Log::info('Channel subscription checked', [
                    'user_id' => $userId,
                    'channel_id' => $channel->id,
                    'channel_title' => $channel->title,
                    'channel_username' => $channel->username,
                    'telegram_chat_id' => $channel->getChatIdForCheck(),
                    'subscribed' => $subscribed
                ]);

                if (!$subscribed) {
                    $allSubscribed = false;
                }
            }

            Log::info('Subscription check completed', [
                'user_id' => $userId,
                'allSubscribed' => $allSubscribed,
                'channels_checked' => count($subscriptions),
                'subscribed_count' => count(array_filter($subscriptions, fn($ch) => $ch['subscribed']))
            ]);

            // Кешируем результат на 5 минут
            Cache::put($cacheKey, [
                'allSubscribed' => $allSubscribed,
                'channels' => $subscriptions
            ], now()->addMinutes(5));

            return response()->json([
                'success' => true,
                'allSubscribed' => $allSubscribed,
                'channels' => $subscriptions
            ]);

        } catch (\Exception $e) {
            Log::error('Subscription check error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при проверке подписок: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить список обязательных каналов
     * 
     * @OA\Get(
     *     path="/subscriptions/channels",
     *     tags={"Subscriptions"},
     *     summary="Получить список каналов для подписки",
     *     @OA\Response(
     *         response=200,
     *         description="Список каналов",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="url", type="string"),
     *                 @OA\Property(property="avatar", type="string", nullable=true)
     *             ))
     *         )
     *     )
     * )
     */
    public function channels(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'subscriptions/channels');
        
        try {
            $channels = Channel::getRequiredChannels()->map(function ($channel) {
                return [
                    'id' => $channel->id,
                    'title' => $channel->title,
                    'description' => $channel->description,
                    'username' => $channel->username,
                    'url' => $channel->url,
                    'avatar' => $channel->avatar,
                    'isRequired' => $channel->is_required
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $channels
            ]);

        } catch (\Exception $e) {
            Log::error('Get channels error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении списка каналов'
            ], 500);
        }
    }

    /**
     * Проверяет подписку пользователя на конкретный канал
     * 
     * @param int $userId ID пользователя Telegram
     * @param Channel $channel Канал для проверки
     * @param string $botToken Токен бота
     * @return bool
     */
    protected function checkUserSubscription(int $userId, Channel $channel, string $botToken): bool
    {
        try {
            $chatId = $channel->getChatIdForCheck();
            
            // Проверяем что это не бот
            $botUsername = config('services.telegram.bot_username');
            if ($botUsername && ltrim(strtolower($channel->username), '@') === ltrim(strtolower($botUsername), '@')) {
                Log::error('Channel username matches bot username - this is incorrect!', [
                    'channel_id' => $channel->id,
                    'channel_username' => $channel->username,
                    'bot_username' => $botUsername,
                    'message' => 'Channel username cannot be the same as bot username. Use a different channel for subscriptions.'
                ]);
            }
            
            Log::info('Checking subscription', [
                'user_id' => $userId,
                'channel_id' => $channel->id,
                'channel_title' => $channel->title,
                'channel_username' => $channel->username,
                'telegram_chat_id_db' => $channel->telegram_chat_id,
                'chat_id_used' => $chatId,
                'bot_username' => $botUsername,
                'is_bot_username' => $botUsername && ltrim(strtolower($channel->username), '@') === ltrim(strtolower($botUsername), '@')
            ]);
            
            // Пробуем разные форматы chat_id
            $chatIdsToTry = [];
            
            // Если telegram_chat_id указан, используем его
            if ($channel->telegram_chat_id) {
                $chatIdsToTry[] = $channel->telegram_chat_id;
            }
            
            // Пробуем username без @
            $chatIdsToTry[] = $channel->username;
            
            // Пробуем username с @
            $chatIdsToTry[] = '@' . ltrim($channel->username, '@');
            
            // Убираем дубликаты
            $chatIdsToTry = array_unique($chatIdsToTry);
            
            // Используем Telegram Bot API для проверки статуса пользователя
            $lastError = null;
            
            foreach ($chatIdsToTry as $tryChatId) {
                Log::info('Trying chat_id format', [
                    'chat_id' => $tryChatId,
                    'user_id' => $userId,
                    'channel_title' => $channel->title
                ]);
                
                // Для локальной разработки отключаем проверку SSL
                $verify = app()->environment('local') ? false : true;
                
                $response = Http::timeout(10)
                    ->withoutVerifying($verify === false)
                    ->get("https://api.telegram.org/bot{$botToken}/getChatMember", [
                        'chat_id' => $tryChatId,
                        'user_id' => $userId
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    
                    if (isset($data['ok']) && $data['ok']) {
                        // Успешно получили ответ
                        $status = $data['result']['status'] ?? null;
                        $isSubscribed = in_array($status, ['member', 'administrator', 'creator']);
                        
                        Log::info('Subscription check successful', [
                            'user_id' => $userId,
                            'channel_title' => $channel->title,
                            'chat_id_used' => $tryChatId,
                            'status' => $status,
                            'subscribed' => $isSubscribed,
                            'full_response' => $data['result'] ?? null
                        ]);
                        
                        return $isSubscribed;
                    }
                }
                
                // Сохраняем последнюю ошибку для логирования
                $lastError = [
                    'chat_id' => $tryChatId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'data' => $response->json()
                ];
            }

            // Если все попытки неудачны, логируем ошибку
            Log::warning('Telegram Bot API error - all chat_id formats failed', [
                'chat_ids_tried' => $chatIdsToTry,
                'user_id' => $userId,
                'channel_title' => $channel->title,
                'channel_username' => $channel->username,
                'telegram_chat_id_db' => $channel->telegram_chat_id,
                'last_error' => $lastError
            ]);
            
            // Если канал не найден или бот не администратор
            if ($lastError && isset($lastError['data']['error_code'])) {
                $errorCode = $lastError['data']['error_code'];
                $errorDescription = $lastError['data']['description'] ?? 'Unknown error';
                
                if (in_array($errorCode, [400, 403])) {
                    $solution = 'Убедитесь что: 1) Бот добавлен как администратор в канал, 2) Канал существует, 3) Для приватных каналов укажите числовой chat_id в поле telegram_chat_id';
                    
                    // Специальная обработка ошибки "member list is inaccessible"
                    if (stripos($errorDescription, 'member list is inaccessible') !== false) {
                        $solution = 'Бот не имеет прав "Просмотр подписчиков". Добавьте бота как администратора в канал и включите права: "Просмотр подписчиков" или "View members" / "View statistics"';
                    }
                    
                    Log::error('Bot is not admin or channel not found', [
                        'channel_title' => $channel->title,
                        'channel_username' => $channel->username,
                        'telegram_chat_id_db' => $channel->telegram_chat_id,
                        'error_code' => $errorCode,
                        'error_description' => $errorDescription,
                        'solution' => $solution,
                        'chat_ids_tried' => $chatIdsToTry
                    ]);
                    
                    // Если ошибка "chat not found", очищаем кеш чтобы при следующей проверке попробовать снова
                    if (stripos($errorDescription, 'chat not found') !== false) {
                        Cache::forget("subscriptions_check_{$userId}");
                        Log::info('Cache cleared due to "chat not found" error', [
                            'user_id' => $userId,
                            'channel_title' => $channel->title
                        ]);
                    }
                }
            }
            
            return false;

        } catch (\Exception $e) {
            Log::error('Check subscription error', [
                'channel_id' => $channel->id,
                'channel_title' => $channel->title,
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            // При ошибке считаем что не подписан для безопасности
            return false;
        }
    }

    /**
     * Очистить кеш проверки подписок для пользователя
     * Вызывается после подписки на канал
     * 
     * @OA\Post(
     *     path="/subscriptions/clear-cache",
     *     tags={"Subscriptions"},
     *     summary="Очистить кеш подписок",
     *     @OA\Parameter(
     *         name="initData",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Кеш очищен")
     * )
     */
    public function clearCache(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'subscriptions/clear-cache');
        
        try {
            $initData = $request->get('initData');
            $botToken = config('services.telegram.bot_token');

            if ($initData && $botToken && TelegramInitDataValidator::validate($initData, $botToken)) {
                $userId = TelegramInitDataValidator::getUserId($initData);
                
                if ($userId) {
                    Cache::forget("subscriptions_check_{$userId}");
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Кеш очищен'
            ]);

        } catch (\Exception $e) {
            Log::error('Clear cache error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при очистке кеша'
            ], 500);
        }
    }

    /**
     * Получение username текущего бота
     * 
     * @OA\Get(
     *     path="/bot/username",
     *     tags={"Bot"},
     *     summary="Получить username бота",
     *     description="Возвращает username текущего бота из конфигурации",
     *     @OA\Response(
     *         response=200,
     *         description="Успешный ответ",
     *         @OA\JsonContent(
     *             @OA\Property(property="username", type="string", example="sitesaccessbot")
     *         )
     *     )
     * )
     */
    public function getBotUsername(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'bot/username');
        
        $botUsername = config('services.telegram.bot_username');
        
        return response()->json([
            'username' => $botUsername ?: null
        ]);
    }
}

