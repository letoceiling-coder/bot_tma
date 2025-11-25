<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

/**
 * Валидатор initData от Telegram WebApp
 * 
 * Документация: https://core.telegram.org/bots/webapps#validating-data-received-via-the-mini-app
 */
class TelegramInitDataValidator
{
    /**
     * Валидирует initData от Telegram WebApp
     * 
     * @param string $initData Raw initData строка от Telegram
     * @param string $botToken Токен бота из .env
     * @return bool
     */
    public static function validate(string $initData, string $botToken): bool
    {
        try {
            // Парсим initData (формат: key1=value1&key2=value2&hash=...)
            parse_str($initData, $parsed);
            
            if (!isset($parsed['hash'])) {
                Log::warning('Telegram initData: hash not found');
                return false;
            }

            $hash = $parsed['hash'];
            unset($parsed['hash']);

            // Сортируем параметры по ключам
            ksort($parsed);

            // Формируем строку для проверки: key1=value1\nkey2=value2
            $dataCheckString = [];
            foreach ($parsed as $key => $value) {
                $dataCheckString[] = $key . '=' . $value;
            }
            $dataCheckString = implode("\n", $dataCheckString);

            // Создаем секретный ключ: HMAC-SHA-256 от bot token
            $secretKey = hash_hmac('sha256', $botToken, 'WebAppData', true);

            // Вычисляем хеш
            $calculatedHash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

            // Сравниваем хеши
            if (!hash_equals($calculatedHash, $hash)) {
                Log::warning('Telegram initData: hash mismatch', [
                    'calculated' => $calculatedHash,
                    'received' => $hash
                ]);
                return false;
            }

            // Проверяем timestamp (не старше 24 часов)
            if (isset($parsed['auth_date'])) {
                $authDate = (int)$parsed['auth_date'];
                $currentTime = time();
                $diff = $currentTime - $authDate;

                // Разрешаем разницу до 24 часов (86400 секунд)
                if ($diff > 86400) {
                    Log::warning('Telegram initData: auth_date too old', [
                        'auth_date' => $authDate,
                        'current' => $currentTime,
                        'diff' => $diff
                    ]);
                    return false;
                }
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Telegram initData validation error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Извлекает данные пользователя из валидированного initData
     * 
     * @param string $initData Raw initData строка
     * @return array|null Данные пользователя или null
     */
    public static function getUserData(string $initData): ?array
    {
        parse_str($initData, $parsed);
        
        if (!isset($parsed['user'])) {
            return null;
        }

        $userData = json_decode($parsed['user'], true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Telegram initData: invalid user JSON', [
                'error' => json_last_error_msg(),
                'user_string' => $parsed['user']
            ]);
            return null;
        }

        return $userData;
    }

    /**
     * Извлекает user_id из initData
     * 
     * @param string $initData Raw initData строка
     * @return int|null
     */
    public static function getUserId(string $initData): ?int
    {
        $userData = self::getUserData($initData);
        return $userData['id'] ?? null;
    }
}

