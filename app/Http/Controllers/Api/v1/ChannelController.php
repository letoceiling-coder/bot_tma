<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LogsRequestParameters;
use App\Models\Channel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChannelController extends Controller
{
    use LogsRequestParameters;
    /**
     * Получить список всех каналов
     */
    public function index(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'channels/index');
        
        try {
            $channels = Channel::orderBy('sort_order')->get();

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
     * Создать новый канал
     */
    public function store(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'channels/store');
        
        try {
            $data = $this->validateChannel($request);
            $data['sort_order'] = $data['sort_order'] ?? (Channel::max('sort_order') ?? -1) + 1;

            $channel = Channel::create($data);

            return response()->json([
                'success' => true,
                'message' => 'Канал создан',
                'data' => $channel
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Валидация выбрасывает ValidationException, возвращаем 422
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Create channel error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при создании канала'
            ], 500);
        }
    }

    /**
     * Получить канал по ID
     */
    public function show(Channel $channel): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => $channel
            ]);
        } catch (\Exception $e) {
            Log::error('Get channel error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении канала'
            ], 500);
        }
    }

    /**
     * Обновить канал
     */
    public function update(Request $request, Channel $channel): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'channels/update');
        
        try {
            $data = $this->validateChannel($request, true);
            $channel->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Канал обновлён',
                'data' => $channel
            ]);
        } catch (\Exception $e) {
            Log::error('Update channel error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении канала'
            ], 500);
        }
    }

    /**
     * Удалить канал
     */
    public function destroy(Request $request, Channel $channel): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'channels/destroy');
        
        try {
            $channel->delete();

            return response()->json([
                'success' => true,
                'message' => 'Канал удалён'
            ]);
        } catch (\Exception $e) {
            Log::error('Delete channel error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении канала'
            ], 500);
        }
    }

    /**
     * Синхронизация каналов (массовое обновление/создание)
     */
    public function sync(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'channels/sync');
        
        try {
            // Сначала проверяем базовую валидацию
            $request->validate([
                'channels' => ['required', 'array'],
            ]);

            $channels = $request->input('channels', []);

            // Если массив пустой или не содержит элементов, удаляем все каналы
            if (empty($channels) || count($channels) === 0) {
                Channel::query()->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Все каналы удалены',
                    'data' => []
                ]);
            }

            // Валидация элементов массива только если массив не пустой
            $payload = $request->validate([
                'channels' => ['required', 'array'],
                'channels.*.id' => ['nullable', 'integer', 'exists:channels,id'],
                'channels.*.title' => ['required', 'string', 'max:255'],
                'channels.*.description' => ['nullable', 'string'],
                'channels.*.username' => ['required', 'string', 'max:255'],
                'channels.*.url' => ['required', 'url', 'max:500'],
                'channels.*.avatar' => ['nullable', 'string', 'max:500'],
                'channels.*.telegram_chat_id' => ['nullable', 'string', 'max:100'],
                'channels.*.sort_order' => ['nullable', 'integer', 'min:0'],
                'channels.*.is_active' => ['nullable', 'boolean'],
                'channels.*.is_required' => ['nullable', 'boolean'],
            ]);

            $ids = [];
            foreach ($payload['channels'] as $index => $channelData) {
                $data = [
                    'title' => $channelData['title'],
                    'description' => $channelData['description'] ?? null,
                    'username' => ltrim($channelData['username'], '@'), // Убираем @ если есть
                    'url' => $channelData['url'],
                    'avatar' => $channelData['avatar'] ?? null,
                    'telegram_chat_id' => $channelData['telegram_chat_id'] ?? null,
                    'sort_order' => $channelData['sort_order'] ?? $index,
                    'is_active' => $channelData['is_active'] ?? true,
                    'is_required' => $channelData['is_required'] ?? true,
                ];

                if (!empty($channelData['id'])) {
                    $channel = Channel::find($channelData['id']);
                    if ($channel) {
                        $channel->update($data);
                        $ids[] = $channel->id;
                        continue;
                    }
                }

                $channel = Channel::create($data);
                $ids[] = $channel->id;
            }

            // Удаляем каналы, которых нет в новом списке
            if (!empty($ids)) {
                Channel::whereNotIn('id', $ids)->delete();
            } else {
                Channel::query()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Каналы синхронизированы',
                'data' => Channel::orderBy('sort_order')->get()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Валидация выбрасывает ValidationException, возвращаем 422
            return response()->json([
                'success' => false,
                'message' => 'Ошибка валидации',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Sync channels error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при синхронизации каналов: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удалить все каналы
     */
    public function destroyAll(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'channels/destroy-all');
        
        try {
            // Считаем количество ДО удаления
            $count = Channel::count();
            
            Log::info('Deleting all channels', [
                'count_before_delete' => $count
            ]);
            
            // Удаляем все каналы
            $deleted = Channel::query()->delete();
            
            Log::info('Channels deleted', [
                'count_before' => $count,
                'deleted_rows' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => $count > 0 ? "Удалено каналов: {$count}" : "Каналов для удаления не найдено",
                'deleted_count' => $count,
                'deleted_rows' => $deleted
            ]);
        } catch (\Exception $e) {
            Log::error('Delete all channels error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при удалении всех каналов: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Валидация данных канала
     */
    protected function validateChannel(Request $request, bool $isUpdate = false): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'username' => ['required', 'string', 'max:255'],
            'url' => ['required', 'url', 'max:500'],
            'avatar' => ['nullable', 'string', 'max:500'],
            'telegram_chat_id' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_required' => ['nullable', 'boolean'],
        ]);
    }
}

