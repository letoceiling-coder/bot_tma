<?php

namespace Tests\Unit;

use App\Helpers\TelegramInitDataValidator;
use Tests\TestCase;

class TelegramInitDataValidatorTest extends TestCase
{
    private string $botToken = '123456789:ABCdefGHIjklMNOpqrsTUVwxyz';

    /**
     * Тест валидации корректного initData
     */
    public function test_validates_correct_init_data(): void
    {
        // Генерируем валидный initData
        $authDate = time();
        $user = json_encode([
            'id' => 123456789,
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser'
        ]);

        $userEncoded = urlencode($user);
        
        // Парсим initData для правильного порядка параметров
        parse_str("user={$userEncoded}&auth_date={$authDate}", $parsed);
        
        // Сортируем параметры по ключам (как в валидаторе)
        ksort($parsed);
        
        // Формируем data check string в правильном порядке
        $dataCheckArray = [];
        foreach ($parsed as $key => $value) {
            $dataCheckArray[] = $key . '=' . $value;
        }
        $dataCheckString = implode("\n", $dataCheckArray);
        
        // Создаем секретный ключ
        $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        
        // Вычисляем hash
        $hash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        $initData = "user={$userEncoded}&auth_date={$authDate}&hash={$hash}";

        $result = TelegramInitDataValidator::validate($initData, $this->botToken);

        $this->assertTrue($result, 'initData validation failed');
    }

    /**
     * Тест отклонения initData с неверным hash
     */
    public function test_rejects_init_data_with_invalid_hash(): void
    {
        $authDate = time();
        $user = json_encode(['id' => 123456789, 'first_name' => 'Test']);

        $initData = "user=" . urlencode($user) . "&auth_date={$authDate}&hash=invalid_hash";

        $result = TelegramInitDataValidator::validate($initData, $this->botToken);

        $this->assertFalse($result);
    }

    /**
     * Тест отклонения initData без hash
     */
    public function test_rejects_init_data_without_hash(): void
    {
        $authDate = time();
        $user = json_encode(['id' => 123456789, 'first_name' => 'Test']);

        $initData = "user=" . urlencode($user) . "&auth_date={$authDate}";

        $result = TelegramInitDataValidator::validate($initData, $this->botToken);

        $this->assertFalse($result);
    }

    /**
     * Тест отклонения устаревшего initData (старше 24 часов)
     */
    public function test_rejects_old_init_data(): void
    {
        $authDate = time() - (25 * 60 * 60); // 25 часов назад
        $user = json_encode(['id' => 123456789, 'first_name' => 'Test']);

        $dataCheckString = "auth_date={$authDate}\nuser=" . urlencode($user);
        $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        $hash = bin2hex(hash_hmac('sha256', $dataCheckString, $secretKey, true));

        $initData = "user=" . urlencode($user) . "&auth_date={$authDate}&hash={$hash}";

        $result = TelegramInitDataValidator::validate($initData, $this->botToken);

        $this->assertFalse($result);
    }

    /**
     * Тест извлечения user_id из initData
     */
    public function test_extracts_user_id_from_init_data(): void
    {
        $userId = 123456789;
        $user = json_encode([
            'id' => $userId,
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);

        $initData = "user=" . urlencode($user) . "&auth_date=" . time();

        $extractedUserId = TelegramInitDataValidator::getUserId($initData);

        $this->assertEquals($userId, $extractedUserId);
    }

    /**
     * Тест извлечения данных пользователя из initData
     */
    public function test_extracts_user_data_from_init_data(): void
    {
        $userData = [
            'id' => 123456789,
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'testuser'
        ];

        $initData = "user=" . urlencode(json_encode($userData)) . "&auth_date=" . time();

        $extractedData = TelegramInitDataValidator::getUserData($initData);

        $this->assertEquals($userData['id'], $extractedData['id']);
        $this->assertEquals($userData['first_name'], $extractedData['first_name']);
        $this->assertEquals($userData['last_name'], $extractedData['last_name']);
    }

    /**
     * Тест возврата null для некорректного JSON в user
     */
    public function test_returns_null_for_invalid_user_json(): void
    {
        $initData = "user=invalid_json&auth_date=" . time();

        $result = TelegramInitDataValidator::getUserData($initData);

        $this->assertNull($result);
    }
}

