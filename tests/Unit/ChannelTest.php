<?php

namespace Tests\Unit;

use App\Models\Channel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChannelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Тест получения обязательных каналов
     */
    public function test_get_required_channels(): void
    {
        // Создаем обязательные и необязательные каналы
        $required1 = Channel::factory()->create([
            'is_active' => true,
            'is_required' => true,
            'sort_order' => 2
        ]);

        $required2 = Channel::factory()->create([
            'is_active' => true,
            'is_required' => true,
            'sort_order' => 1
        ]);

        $notRequired = Channel::factory()->create([
            'is_active' => true,
            'is_required' => false,
        ]);

        $inactive = Channel::factory()->create([
            'is_active' => false,
            'is_required' => true,
        ]);

        $requiredChannels = Channel::getRequiredChannels();

        // Должны быть только активные обязательные каналы
        $this->assertCount(2, $requiredChannels);
        
        // Должны быть отсортированы по sort_order
        $this->assertEquals($required2->id, $requiredChannels[0]->id);
        $this->assertEquals($required1->id, $requiredChannels[1]->id);
        
        // Не должны содержать необязательные или неактивные
        $this->assertFalse($requiredChannels->contains($notRequired));
        $this->assertFalse($requiredChannels->contains($inactive));
    }

    /**
     * Тест получения chat_id для проверки
     */
    public function test_get_chat_id_for_check_with_telegram_chat_id(): void
    {
        $channel = Channel::factory()->create([
            'username' => 'test_channel',
            'telegram_chat_id' => '-1001234567890'
        ]);

        $chatId = $channel->getChatIdForCheck();

        $this->assertEquals('-1001234567890', $chatId);
    }

    /**
     * Тест получения chat_id для проверки без telegram_chat_id
     */
    public function test_get_chat_id_for_check_without_telegram_chat_id(): void
    {
        $channel = Channel::factory()->create([
            'username' => 'test_channel',
            'telegram_chat_id' => null
        ]);

        $chatId = $channel->getChatIdForCheck();

        $this->assertEquals('@test_channel', $chatId);
    }

    /**
     * Тест получения chat_id для проверки с username с @
     */
    public function test_get_chat_id_for_check_with_at_symbol_in_username(): void
    {
        $channel = Channel::factory()->create([
            'username' => '@test_channel',
            'telegram_chat_id' => null
        ]);

        $chatId = $channel->getChatIdForCheck();

        $this->assertEquals('@test_channel', $chatId);
    }

    /**
     * Тест cast полей модели
     */
    public function test_casts_boolean_fields(): void
    {
        $channel = Channel::factory()->create([
            'is_active' => 1,
            'is_required' => 0
        ]);

        $this->assertIsBool($channel->is_active);
        $this->assertIsBool($channel->is_required);
        $this->assertTrue($channel->is_active);
        $this->assertFalse($channel->is_required);
    }

    /**
     * Тест cast sort_order
     */
    public function test_casts_sort_order(): void
    {
        $channel = Channel::factory()->create([
            'sort_order' => '10'
        ]);

        $this->assertIsInt($channel->sort_order);
        $this->assertEquals(10, $channel->sort_order);
    }
}

