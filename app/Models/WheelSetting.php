<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WheelSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'required',
    ];

    protected $casts = [
        'required' => 'boolean',
    ];

    /**
     * Получить единственную запись настроек (singleton)
     */
    public static function getSettings(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            ['required' => false]
        );
    }

    /**
     * Обновить настройки
     */
    public static function updateSettings(array $data): self
    {
        $settings = static::getSettings();
        $settings->update($data);
        return $settings;
    }
}
