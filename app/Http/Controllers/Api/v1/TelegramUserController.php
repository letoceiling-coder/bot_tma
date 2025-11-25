<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LogsRequestParameters;
use App\Helpers\TelegramInitDataValidator;
use App\Models\TelegramUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramUserController extends Controller
{
    use LogsRequestParameters;
    /**
     * Обработка команды /start с реферальным параметром
     * 
     * Создает нового пользователя или обновляет существующего
     * Если пользователь новый и есть реферальный параметр, увеличивает счетчик рефералов у пригласившего
     * 
     * @OA\Post(
     *     path="/telegram-users/start",
     *     tags={"Telegram Users"},
     *     summary="Обработать команду /start",
     *     description="Создает или обновляет пользователя Telegram, обрабатывает реферальные ссылки",
     *     @OA\Parameter(
     *         name="initData",
     *         in="query",
     *         required=true,
     *         description="InitData от Telegram WebApp",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="start",
     *         in="query",
     *         required=false,
     *         description="Telegram ID пользователя, который пригласил (реферальный параметр)",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Пользователь создан или обновлен",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/TelegramUser"),
     *             @OA\Property(property="is_new", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ошибка валидации"
     *     )
     * )
     */
    public function start(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'telegram-users/start');
        
        try {
            $initData = $request->get('initData');
            // Поддерживаем оба варианта: ref (кастомный) и start (стандартный)
            $startParam = $request->get('ref') ?: $request->get('start'); // Telegram ID пригласившего пользователя
            
            if (!$initData) {
                Log::warning('Telegram user start: initData is missing', [
                    'all_query_params' => $request->query->all(),
                    'request_url' => $request->fullUrl()
                ]);
                
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

            // Валидируем initData
            $isValid = TelegramInitDataValidator::validate($initData, $botToken);
            
            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Невалидный initData'
                ], 400);
            }

            // Извлекаем данные пользователя из initData
            $userData = $this->extractUserDataFromInitData($initData);

            // Дополнительно пытаемся достать start_param из самого initData,
            // т.к. Telegram Mini App передает его как параметр start_param внутри initData
            $parsedInitData = [];
            parse_str($initData, $parsedInitData);
            $startParamFromInitData = $parsedInitData['start_param'] ?? null;

            // Логируем источники referral параметра (поддерживаем ref и start)
            Log::info('Telegram user start: referral param sources', [
                'ref_param_query' => $request->get('ref'),
                'start_param_query' => $request->get('start'),
                'final_param_from_query' => $startParam,
                'start_param_from_initData' => $startParamFromInitData,
                'start_param_from_initData_type' => $startParamFromInitData !== null ? gettype($startParamFromInitData) : null,
            ]);

            // Если referral параметр не пришел явно, но есть в initData, используем его
            if (!$startParam && $startParamFromInitData) {
                $startParam = $startParamFromInitData;
            }
            
            if (!$userData || !isset($userData['id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось извлечь данные пользователя'
                ], 400);
            }

            // Проверяем, существовал ли пользователь до этого
            $existingUser = TelegramUser::findByTelegramId($userData['id']);
            $isNewUser = !$existingUser;
            
            // Логируем информацию о пользователе
            Log::info('Telegram user check', [
                'telegram_id' => $userData['id'],
                'is_new_user' => $isNewUser,
                'existing_user_id' => $existingUser?->id,
                'referral_param_present' => !empty($startParam),
                'referral_param_value' => $startParam,
                'referral_param_source' => $request->get('ref') ? 'ref' : ($request->get('start') ? 'start' : 'none'),
                'username' => $userData['username'] ?? null,
                'first_name' => $userData['first_name'] ?? null
            ]);

            // Обрабатываем реферальный параметр (только для новых пользователей)
            $invitedByTelegramId = null;
            if ($startParam && $isNewUser) {
                Log::info('Processing referral link for new user', [
                    'new_user_telegram_id' => $userData['id'],
                    'start_param' => $startParam,
                    'start_param_is_numeric' => is_numeric($startParam)
                ]);
                // start параметр может быть telegram_id пригласившего
                $invitedByTelegramId = is_numeric($startParam) ? (int)$startParam : null;
                
                // Валидируем, что пригласивший существует и не является самим пользователем
                if ($invitedByTelegramId) {
                    // Пользователь не может пригласить сам себя
                    if ($invitedByTelegramId === $userData['id']) {
                        Log::warning('User tried to invite themselves', [
                            'telegram_id' => $userData['id'],
                            'start_param' => $startParam
                        ]);
                        $invitedByTelegramId = null;
                    } else {
                        // Проверяем, что пригласивший существует в базе
                        // Сначала ищем по telegram_id (основной способ)
                        $inviter = TelegramUser::findByTelegramId($invitedByTelegramId);
                        
                        // Если не нашли по telegram_id, пробуем найти по внутреннему id
                        // (на случай, если в ref передается id из таблицы telegram_users, а не telegram_id)
                        if (!$inviter && is_numeric($startParam)) {
                            $internalId = (int)$startParam;
                            $inviter = TelegramUser::find($internalId);
                            if ($inviter) {
                                // Если нашли по id, используем его telegram_id
                                $invitedByTelegramId = $inviter->telegram_id;
                            }
                        }
                        
                        if (!$inviter) {
                            Log::warning('Inviter not found in database', [
                                'inviter_telegram_id_searched' => $invitedByTelegramId,
                                'internal_id_searched' => is_numeric($startParam) ? (int)$startParam : null,
                                'new_user_telegram_id' => $userData['id'],
                                'start_param' => $startParam
                            ]);
                            $invitedByTelegramId = null;
                        } else {
                            Log::info('Inviter found for referral', [
                                'inviter_id' => $inviter->id,
                                'inviter_telegram_id' => $inviter->telegram_id,
                                'inviter_username' => $inviter->username,
                                'inviter_referrals_count_before' => $inviter->referrals_count,
                                'new_user_telegram_id' => $userData['id']
                            ]);
                        }
                    }
                }
            }

            // Создаем или обновляем пользователя
            $user = TelegramUser::createOrUpdateFromTelegram($userData, $invitedByTelegramId);

            // Обновляем данные после возможного добавления билетов
            $user->refresh();
            
            // Детальное логирование результата
            Log::info('Telegram user start processed', [
                'telegram_id' => $user->telegram_id,
                'user_id' => $user->id,
                'is_new' => $isNewUser,
                'invited_by_telegram_id' => $invitedByTelegramId,
                'invited_by_user_id' => $user->invited_by_telegram_user_id,
                'referrals_count' => $user->referrals_count,
                'tickets' => $user->tickets,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'start_param_received' => $startParam,
                'start_param_used' => !empty($invitedByTelegramId),
                'created_at' => $user->created_at?->toIso8601String(),
                'last_ticket_added_at' => $user->last_ticket_added_at?->toIso8601String()
            ]);
            
            // Если это новый пользователь - логируем отдельно
            if ($isNewUser) {
                Log::info('New Telegram user created', [
                    'telegram_id' => $user->telegram_id,
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'tickets_initial' => $user->tickets,
                    'invited_by_telegram_id' => $user->invited_by_telegram_user_id,
                    'has_referral' => !empty($user->invited_by_telegram_user_id),
                    'start_param_received' => $startParam,
                    'start_param_used' => !empty($invitedByTelegramId),
                    'initData_has_start_param' => $startParamFromInitData !== null,
                ]);
            }

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'telegram_id' => $user->telegram_id,
                    'username' => $user->username,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'display_name' => $user->display_name,
                    'language_code' => $user->language_code,
                    'photo_url' => $user->photo_url,
                    'tickets' => $user->tickets,
                    'referrals_count' => $user->referrals_count,
                    'invited_by_telegram_user_id' => $user->invited_by_telegram_user_id,
                    'last_active_at' => $user->last_active_at?->toIso8601String(),
                    'seconds_until_next_ticket' => $user->getSecondsUntilNextTicket(),
                    'created_at' => $user->created_at->toIso8601String(),
                    'updated_at' => $user->updated_at->toIso8601String(),
                ],
                'is_new' => $isNewUser,
                'message' => $isNewUser ? 'Пользователь создан' : 'Пользователь обновлен'
            ]);

        } catch (\Exception $e) {
            Log::error('Telegram user start error', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обработке запроса'
            ], 500);
        }
    }

    /**
     * Получение текущего пользователя по initData
     * 
     * @OA\Get(
     *     path="/telegram-users/me",
     *     tags={"Telegram Users"},
     *     summary="Получить текущего пользователя",
     *     @OA\Parameter(
     *         name="initData",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Данные пользователя")
     * )
     */
    public function me(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'telegram-users/me');
        
        try {
            $initData = $request->get('initData');
            
            if (!$initData) {
                return response()->json([
                    'success' => false,
                    'message' => 'initData обязателен'
                ], 400);
            }

            $botToken = config('services.telegram.bot_token');
            
            if (!$botToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сервис временно недоступен'
                ], 500);
            }

            $isValid = TelegramInitDataValidator::validate($initData, $botToken);
            
            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Невалидный initData'
                ], 400);
            }

            $userId = TelegramInitDataValidator::getUserId($initData);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось определить пользователя'
                ], 400);
            }

            $user = TelegramUser::findByTelegramId($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ], 404);
            }

            // Обновляем последнюю активность и проверяем билеты
            $user->updateLastActive();
            $user->checkAndAddTicketsIfNeeded();
            $user->refresh(); // Обновляем данные после возможного добавления билетов

            return response()->json([
                'success' => true,
                'user' => [
                    'id' => $user->id,
                    'telegram_id' => $user->telegram_id,
                    'username' => $user->username,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'display_name' => $user->display_name,
                    'language_code' => $user->language_code,
                    'photo_url' => $user->photo_url,
                    'tickets' => $user->tickets,
                    'referrals_count' => $user->referrals_count,
                    'invited_by_telegram_user_id' => $user->invited_by_telegram_user_id,
                    'last_active_at' => $user->last_active_at?->toIso8601String(),
                    'seconds_until_next_ticket' => $user->getSecondsUntilNextTicket(),
                    'created_at' => $user->created_at->toIso8601String(),
                    'updated_at' => $user->updated_at->toIso8601String(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Telegram user me error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении данных пользователя'
            ], 500);
        }
    }

    /**
     * Извлечение данных пользователя из initData
     */
    protected function extractUserDataFromInitData(string $initData): ?array
    {
        try {
            parse_str($initData, $params);
            $userParam = $params['user'] ?? null;
            
            if (!$userParam) {
                return null;
            }

            // Если user это JSON строка, декодируем
            if (is_string($userParam)) {
                $decoded = json_decode($userParam, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
            }

            // Если user это массив (уже декодирован)
            if (is_array($userParam)) {
                return $userParam;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to extract user data from initData', [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Получение информации о билетах пользователя
     * 
     * @OA\Get(
     *     path="/telegram-users/tickets",
     *     tags={"Telegram Users"},
     *     summary="Получить информацию о билетах",
     *     @OA\Parameter(
     *         name="initData",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Информация о билетах")
     * )
     */
    public function getTickets(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'telegram-users/tickets');
        
        try {
            $initData = $request->get('initData');
            
            if (!$initData) {
                return response()->json([
                    'success' => false,
                    'message' => 'initData обязателен'
                ], 400);
            }

            $botToken = config('services.telegram.bot_token');
            
            if (!$botToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сервис временно недоступен'
                ], 500);
            }

            $isValid = TelegramInitDataValidator::validate($initData, $botToken);
            
            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Невалидный initData'
                ], 400);
            }

            $userId = TelegramInitDataValidator::getUserId($initData);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось определить пользователя'
                ], 400);
            }

            $user = TelegramUser::findByTelegramId($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ], 404);
            }

            // Проверяем и добавляем билеты, если нужно
            $ticketsAdded = $user->checkAndAddTicketsIfNeeded();
            $user->refresh();

            return response()->json([
                'success' => true,
                'tickets' => $user->tickets,
                'seconds_until_next_ticket' => $user->getSecondsUntilNextTicket(),
                'tickets_added' => $ticketsAdded > 0,
                'tickets_added_count' => $ticketsAdded
            ]);

        } catch (\Exception $e) {
            Log::error('Telegram user get tickets error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении информации о билетах'
            ], 500);
        }
    }

    /**
     * Списание билета при вращении колеса
     * 
     * @OA\Post(
     *     path="/telegram-users/spend-ticket",
     *     tags={"Telegram Users"},
     *     summary="Списать билет",
     *     @OA\Parameter(
     *         name="initData",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Билет списан")
     * )
     */
    public function spendTicket(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'telegram-users/spend-ticket');
        
        try {
            $initData = $request->get('initData');
            
            if (!$initData) {
                return response()->json([
                    'success' => false,
                    'message' => 'initData обязателен'
                ], 400);
            }

            $botToken = config('services.telegram.bot_token');
            
            if (!$botToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Сервис временно недоступен'
                ], 500);
            }

            $isValid = TelegramInitDataValidator::validate($initData, $botToken);
            
            if (!$isValid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Невалидный initData'
                ], 400);
            }

            $userId = TelegramInitDataValidator::getUserId($initData);
            
            if (!$userId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось определить пользователя'
                ], 400);
            }

            $user = TelegramUser::findByTelegramId($userId);
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Пользователь не найден'
                ], 404);
            }

            // Проверяем наличие билетов перед списанием
            if (!$user->hasEnoughTickets(1)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Недостаточно билетов',
                    'tickets' => $user->tickets
                ], 400);
            }

            // Списываем билет
            $spent = $user->spendTickets(1);
            
            if (!$spent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка при списании билета'
                ], 500);
            }

            $user->refresh();

            return response()->json([
                'success' => true,
                'tickets' => $user->tickets,
                'seconds_until_next_ticket' => $user->getSecondsUntilNextTicket(),
                'message' => 'Билет успешно списан'
            ]);

        } catch (\Exception $e) {
            Log::error('Telegram user spend ticket error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при списании билета'
            ], 500);
        }
    }
}
