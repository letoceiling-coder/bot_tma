<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasFactory;

    /**
     * Создать новый экземпляр фабрики модели.
     */
    protected static function newFactory()
    {
        return \Database\Factories\ChannelFactory::new();
    }

    protected $fillable = [
        'title',
        'description',
        'username',
        'url',
        'avatar',
        'telegram_chat_id',
        'sort_order',
        'is_active',
        'is_required'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_required' => 'boolean',
        'sort_order' => 'integer'
    ];

    /**
     * Получить список активных обязательных каналов, отсортированных по sort_order
     */
    public static function getRequiredChannels()
    {
        return static::where('is_active', true)
            ->where('is_required', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * Получить chat_id для проверки подписки
     * Если telegram_chat_id не указан, используем username с @
     */
    public function getChatIdForCheck(): string
    {
        return $this->telegram_chat_id ?? '@' . ltrim($this->username, '@');
    }
}

