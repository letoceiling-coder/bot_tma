<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель для хранения блоков Telegram бота
 * 
 * Блоки представляют собой элементы карты диалога бота:
 * - command: команда (например /start)
 * - text: текстовое сообщение
 * - menu: меню с кнопками
 * - input: запрос ввода от пользователя
 * - confirmation: блок подтверждения
 */
class Block extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Таблица, используемая моделью
     */
    protected $table = 'blocks';

    /**
     * Поля, которые можно массово присваивать
     */
    protected $fillable = [
        'block_id',
        'type',
        'text',
        'value',
        'buttons',
        'target',
        'next_block',
        'confirmation_block',
        'command',
        'input_type',
        'confirm_button',
        'cancel_button',
        'confirm_action',
        'cancel_action',
        'metadata',
        'sort_order',
        'is_active',
    ];

    /**
     * Преобразование типов полей
     */
    protected $casts = [
        'buttons' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Получить блок по block_id
     */
    public static function findByBlockId(string $blockId): ?self
    {
        return static::where('block_id', $blockId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Получить все активные блоки
     */
    public static function getAllActive(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Преобразовать блок в массив для Handler
     */
    public function toHandlerArray(): array
    {
        $data = [
            'id' => $this->block_id,
            'type' => $this->type,
        ];

        // Добавляем текст или value в зависимости от типа
        if ($this->text !== null) {
            $data['text'] = $this->text;
        }
        if ($this->value !== null) {
            $data['value'] = $this->value;
        }

        // Добавляем кнопки для menu типа
        if ($this->type === 'menu' && $this->buttons) {
            $data['buttons'] = $this->buttons;
        }

        // Добавляем связи с другими блоками
        if ($this->target) {
            $data['target'] = $this->target;
        }
        if ($this->next_block) {
            $data['next_block'] = $this->next_block;
        }
        if ($this->confirmation_block) {
            $data['confirmation_block'] = $this->confirmation_block;
        }

        // Добавляем специфичные поля для разных типов
        if ($this->type === 'command' && $this->command) {
            $data['command'] = $this->command;
        }
        if ($this->type === 'input' && $this->input_type) {
            $data['input_type'] = $this->input_type;
        }
        if ($this->type === 'confirmation') {
            if ($this->confirm_button) {
                $data['confirm_button'] = $this->confirm_button;
            }
            if ($this->cancel_button) {
                $data['cancel_button'] = $this->cancel_button;
            }
            if ($this->confirm_action) {
                $data['confirm_action'] = $this->confirm_action;
            }
            if ($this->cancel_action) {
                $data['cancel_action'] = $this->cancel_action;
            }
        }

        // Добавляем дополнительные метаданные
        if ($this->metadata) {
            $data = array_merge($data, $this->metadata);
        }

        return $data;
    }
}
