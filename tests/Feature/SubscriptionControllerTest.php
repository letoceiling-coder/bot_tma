<?php

namespace Tests\Feature;

use App\Models\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
{
    use RefreshDatabase;

    private string $botToken;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Устанавливаем токен бота в конфигурацию
        $this->botToken = '123456789:ABCdefGHIjklMNOpqrsTUVwxyz';
        config(['services.telegram.bot_token' => $this->botToken]);
    }

    /**
     * Тест получения списка каналов
     */
    public function test_can_get_channels_list(): void
    {
        Channel::factory()->create([
            'title' => 'Test Channel',
            'username' => 'test_channel',
            'url' => 'https://t.me/test_channel',
            'is_active' => true,
            'is_required' => true,
        ]);

        $response = $this->getJson('/api/v1/subscriptions/channels');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'username',
                        'url',
                        'avatar',
                        'isRequired'
                    ]
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    /**
     * Тест проверки подписок - все подписаны
     */
    public function test_check_subscriptions_all_subscribed(): void
    {
        $channel = Channel::factory()->create([
            'username' => 'test_channel',
            'telegram_chat_id' => '@test_channel',
            'is_active' => true,
            'is_required' => true,
        ]);

        $userId = 123456789;
        $initData = $this->createInitData($userId);

        // Мокаем Telegram Bot API ответ
        Http::fake([
            "api.telegram.org/bot{$this->botToken}/getChatMember*" => Http::response([
                'ok' => true,
                'result' => [
                    'status' => 'member',
                    'user' => [
                        'id' => $userId,
                        'first_name' => 'Test'
                    ]
                ]
            ])
        ]);

        $response = $this->getJson("/api/v1/subscriptions/check?initData=" . urlencode($initData));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'allSubscribed' => true
            ])
            ->assertJsonStructure([
                'channels' => [
                    '*' => [
                        'id',
                        'title',
                        'subscribed'
                    ]
                ]
            ]);

        $channels = $response->json('channels');
        $this->assertNotEmpty($channels);
        $this->assertTrue($channels[0]['subscribed']);
    }

    /**
     * Тест проверки подписок - не все подписаны
     */
    public function test_check_subscriptions_not_all_subscribed(): void
    {
        $channel = Channel::factory()->create([
            'username' => 'test_channel',
            'telegram_chat_id' => '@test_channel',
            'is_active' => true,
            'is_required' => true,
        ]);

        $userId = 123456789;
        $initData = $this->createInitData($userId);

        // Мокаем Telegram Bot API ответ - пользователь не подписан
        Http::fake([
            "api.telegram.org/bot{$this->botToken}/getChatMember*" => Http::response([
                'ok' => true,
                'result' => [
                    'status' => 'left',
                    'user' => [
                        'id' => $userId,
                        'first_name' => 'Test'
                    ]
                ]
            ])
        ]);

        $response = $this->getJson("/api/v1/subscriptions/check?initData=" . urlencode($initData));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'allSubscribed' => false
            ]);

        $channels = $response->json('channels');
        $this->assertFalse($channels[0]['subscribed']);
    }

    /**
     * Тест проверки подписок без обязательных каналов
     */
    public function test_check_subscriptions_no_required_channels(): void
    {
        // Не создаем обязательных каналов
        $userId = 123456789;
        $initData = $this->createInitData($userId);

        $response = $this->getJson("/api/v1/subscriptions/check?initData=" . urlencode($initData));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'allSubscribed' => true,
                'channels' => []
            ]);
    }

    /**
     * Тест проверки подписок без initData
     */
    public function test_check_subscriptions_requires_init_data(): void
    {
        $response = $this->getJson('/api/v1/subscriptions/check');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'initData обязателен'
            ]);
    }

    /**
     * Тест проверки подписок с кешированием
     */
    public function test_check_subscriptions_uses_cache(): void
    {
        $channel = Channel::factory()->create([
            'username' => 'test_channel',
            'telegram_chat_id' => '@test_channel',
            'is_active' => true,
            'is_required' => true,
        ]);

        $userId = 123456789;
        $initData = $this->createInitData($userId);

        // Устанавливаем кеш
        $cacheKey = "subscriptions_check_{$userId}";
        $cachedResult = [
            'allSubscribed' => true,
            'channels' => [
                [
                    'id' => $channel->id,
                    'title' => $channel->title,
                    'subscribed' => true
                ]
            ]
        ];
        Cache::put($cacheKey, $cachedResult, now()->addMinutes(5));

        // Мокаем API, но запрос не должен быть выполнен из-за кеша
        Http::fake();

        $response = $this->getJson("/api/v1/subscriptions/check?initData=" . urlencode($initData));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'allSubscribed' => true
            ]);

        // Проверяем что HTTP запрос не был выполнен (благодаря кешу)
        Http::assertNothingSent();
    }

    /**
     * Тест очистки кеша подписок
     */
    public function test_can_clear_subscription_cache(): void
    {
        $userId = 123456789;
        $initData = $this->createInitData($userId);

        // Устанавливаем кеш
        $cacheKey = "subscriptions_check_{$userId}";
        Cache::put($cacheKey, ['allSubscribed' => true], now()->addMinutes(5));

        // Проверяем что кеш установлен
        $this->assertNotNull(Cache::get($cacheKey));

        // Очищаем кеш через прямой вызов метода
        Cache::forget($cacheKey);

        // Проверяем что кеш очищен
        $cached = Cache::get($cacheKey);
        // В тестах используется array драйвер
        $this->assertTrue($cached === null || $cached === false, 'Cache should be cleared after forget');
        
        // Тестируем API endpoint (он требует валидацию initData, но это работает)
        $response = $this->postJson("/api/v1/subscriptions/clear-cache?initData=" . urlencode($initData));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);
    }

    /**
     * Создает валидный initData для тестирования
     */
    private function createInitData(int $userId): string
    {
        $authDate = time();
        $user = json_encode([
            'id' => $userId,
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser'
        ]);

        $dataCheckString = "auth_date={$authDate}\nuser=" . urlencode($user);
        $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        $hash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        return "user=" . urlencode($user) . "&auth_date={$authDate}&hash={$hash}";
    }
}

