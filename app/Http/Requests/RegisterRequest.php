<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'role_id' => ['nullable', 'integer', 'exists:user_roles,id'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->when(app()->isProduction(), fn ($rule) => $rule->uncompromised()),
            ],
        ];
    }

    /**
     * Подготовка данных перед валидацией
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'role_id' => $this->role_id ?? 1, // По умолчанию роль User
        ]);
    }

    /**
     * Кастомные сообщения об ошибках валидации
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Поле имя обязательно для заполнения',
            'name.min' => 'Имя должно содержать минимум 2 символа',
            'name.max' => 'Имя не должно превышать 255 символов',
            
            'email.required' => 'Поле email обязательно для заполнения',
            'email.email' => 'Укажите корректный email адрес',
            'email.unique' => 'Пользователь с таким email уже существует',
            'email.max' => 'Email не должен превышать 255 символов',
            
            'role_id.exists' => 'Выбранная роль не существует',
            
            'password.required' => 'Поле пароль обязательно для заполнения',
            'password.confirmed' => 'Пароли не совпадают',
            'password.min' => 'Пароль должен содержать минимум :min символов',
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
            'password' => 'Пароль',
            'role_id' => 'Роль',
        ];
    }
}
