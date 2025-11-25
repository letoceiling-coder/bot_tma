<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

class LoginRequest extends FormRequest
{
    /**
     * Определяет, авторизован ли пользователь для выполнения запроса
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для запроса
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Кастомные сообщения об ошибках валидации
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Поле email обязательно для заполнения',
            'email.email' => 'Укажите корректный email адрес',
            'email.max' => 'Email не должен превышать 255 символов',
            'password.required' => 'Поле пароль обязательно для заполнения',
            'password.min' => 'Пароль должен содержать минимум 8 символов',
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
            'email' => 'Email',
            'password' => 'Пароль',
        ];
    }
}
