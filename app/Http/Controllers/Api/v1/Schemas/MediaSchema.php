<?php

namespace App\Http\Controllers\Api\v1\Schemas;

/**
 * @OA\Schema(
 *     schema="Media",
 *     title="Media",
 *     description="Модель медиа-файла",
 *     type="object",
 *     required={"id", "name", "original_name", "type", "size"}
 * )
 */
class MediaSchema
{
    /**
     * @OA\Property(
     *     property="id",
     *     type="integer",
     *     description="ID файла",
     *     example=1
     * )
     */
    public int $id;

    /**
     * @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Сгенерированное имя файла",
     *     example="abc123_1234567890.jpg"
     * )
     */
    public string $name;

    /**
     * @OA\Property(
     *     property="original_name",
     *     type="string",
     *     description="Оригинальное имя файла",
     *     example="photo.jpg"
     * )
     */
    public string $original_name;

    /**
     * @OA\Property(
     *     property="extension",
     *     type="string",
     *     description="Расширение файла",
     *     example="jpg"
     * )
     */
    public string $extension;

    /**
     * @OA\Property(
     *     property="disk",
     *     type="string",
     *     description="Путь к директории файла",
     *     example="upload"
     * )
     */
    public string $disk;

    /**
     * @OA\Property(
     *     property="width",
     *     type="integer",
     *     nullable=true,
     *     description="Ширина изображения (для фото)",
     *     example=1920
     * )
     */
    public ?int $width;

    /**
     * @OA\Property(
     *     property="height",
     *     type="integer",
     *     nullable=true,
     *     description="Высота изображения (для фото)",
     *     example=1080
     * )
     */
    public ?int $height;

    /**
     * @OA\Property(
     *     property="type",
     *     type="string",
     *     description="Тип файла (photo, video, document)",
     *     example="photo",
     *     enum={"photo", "video", "document"}
     * )
     */
    public string $type;

    /**
     * @OA\Property(
     *     property="size",
     *     type="integer",
     *     description="Размер файла в байтах",
     *     example=102400
     * )
     */
    public int $size;

    /**
     * @OA\Property(
     *     property="folder_id",
     *     type="integer",
     *     nullable=true,
     *     description="ID папки",
     *     example=1
     * )
     */
    public ?int $folder_id;

    /**
     * @OA\Property(
     *     property="user_id",
     *     type="integer",
     *     nullable=true,
     *     description="ID пользователя, загрузившего файл",
     *     example=1
     * )
     */
    public ?int $user_id;

    /**
     * @OA\Property(
     *     property="telegram_file_id",
     *     type="string",
     *     nullable=true,
     *     description="file_id из Telegram",
     *     example="AgACAgIAAxkBAAIBB..."
     * )
     */
    public ?string $telegram_file_id;

    /**
     * @OA\Property(
     *     property="temporary",
     *     type="boolean",
     *     description="Временный файл",
     *     example=false
     * )
     */
    public bool $temporary;

    /**
     * @OA\Property(
     *     property="url",
     *     type="string",
     *     description="URL файла",
     *     example="/upload/photo.jpg"
     * )
     */
    public string $url;

    /**
     * @OA\Property(
     *     property="thumbnail",
     *     type="string",
     *     nullable=true,
     *     description="URL миниатюры (для изображений)",
     *     example="/upload/photo.jpg"
     * )
     */
    public ?string $thumbnail;

    /**
     * @OA\Property(
     *     property="created_at",
     *     type="string",
     *     format="date-time",
     *     description="Дата создания",
     *     example="2025-11-08T12:00:00.000000Z"
     * )
     */
    public string $created_at;

    /**
     * @OA\Property(
     *     property="updated_at",
     *     type="string",
     *     format="date-time",
     *     description="Дата обновления",
     *     example="2025-11-08T12:00:00.000000Z"
     * )
     */
    public string $updated_at;

    /**
     * @OA\Property(
     *     property="folder",
     *     ref="#/components/schemas/Folder",
     *     description="Папка файла",
     *     nullable=true
     * )
     */
    public ?object $folder;

    /**
     * @OA\Property(
     *     property="user",
     *     type="object",
     *     description="Пользователь, загрузивший файл",
     *     nullable=true,
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="name", type="string", example="Иван Иванов")
     * )
     */
    public ?object $user;
}

