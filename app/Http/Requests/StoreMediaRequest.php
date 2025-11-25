<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Авторизация проверяется через middleware auth:api
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'file' => 'required|file|mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,mp4,avi,mov|max:10240', // 10MB
            'folder_id' => 'nullable|exists:folders,id'
        ];
    }
    
    /**
     * Настройка сообщений об ошибках
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Файл обязателен для загрузки',
            'file.file' => 'Загружаемый объект должен быть файлом',
            'file.mimes' => 'Недопустимый тип файла. Разрешены: jpg, jpeg, png, gif, webp, svg, pdf, doc, docx, mp4, avi, mov',
            'file.max' => 'Размер файла не должен превышать 10 МБ',
            'folder_id.exists' => 'Указанная папка не существует'
        ];
    }
}
