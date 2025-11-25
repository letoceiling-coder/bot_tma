<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь для выполнения запроса
     */
    public function authorize(): bool
    {
        // Можно добавить проверку прав на редактирование
        // Например, только сам пользователь или админ может редактировать
        return true;
    }

    /**
     * Правила валидации для запроса
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'min:2', 'max:255'],
            'email' => [
                'sometimes',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[+]?[0-9\s\-\(\)]+$/'],
            'password' => [
                'nullable',
                'string',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'role_id' => ['sometimes', 'integer', 'exists:user_roles,id'],
        ];
    }

    /**
     * Подготовка данных перед валидацией
     */
    protected function prepareForValidation(): void
    {
        // Подготовка данных перед валидацией (если нужно)
    }

    /**
     * Кастомные сообщения об ошибках валидации
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.min' => 'Имя должно содержать минимум 2 символа',
            'name.max' => 'Имя не должно превышать 255 символов',
            
            'email.email' => 'Укажите корректный email адрес',
            'email.unique' => 'Пользователь с таким email уже существует',
            'email.max' => 'Email не должен превышать 255 символов',
            
            'phone.regex' => 'Некорректный формат номера телефона',
            'phone.max' => 'Номер телефона слишком длинный',
            
            'password.min' => 'Пароль должен содержать минимум 8 символов',
            
            'role_id.exists' => 'Выбранная роль не существует',
        ];
    }

    /**
     * Кастомные названия атрибутов для сообщений об ошибках
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Имя',
            'email' => 'Email',
            'phone' => 'Телефон',
            'password' => 'Пароль',
            'role_id' => 'Роль',
        ];
    }
}
