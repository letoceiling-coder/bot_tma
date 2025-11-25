<?php

namespace App\Http\Controllers\Api\v1\Schemas;

/**
 * @OA\Schema(
 *     schema="Folder",
 *     title="Folder",
 *     description="Модель папки медиа-менеджера",
 *     type="object",
 *     required={"id", "name", "slug"}
 * )
 */
class FolderSchema
{
    /**
     * @OA\Property(
     *     property="id",
     *     type="integer",
     *     description="ID папки",
     *     example=1
     * )
     */
    public int $id;

    /**
     * @OA\Property(
     *     property="name",
     *     type="string",
     *     description="Название папки",
     *     example="Общая"
     * )
     */
    public string $name;

    /**
     * @OA\Property(
     *     property="slug",
     *     type="string",
     *     description="URL-slug папки",
     *     example="common"
     * )
     */
    public string $slug;

    /**
     * @OA\Property(
     *     property="src",
     *     type="string",
     *     description="Иконка папки",
     *     example="folder"
     * )
     */
    public string $src;

    /**
     * @OA\Property(
     *     property="parent_id",
     *     type="integer",
     *     nullable=true,
     *     description="ID родительской папки",
     *     example=null
     * )
     */
    public ?int $parent_id;

    /**
     * @OA\Property(
     *     property="position",
     *     type="integer",
     *     description="Позиция для сортировки",
     *     example=0
     * )
     */
    public int $position;

    /**
     * @OA\Property(
     *     property="count",
     *     type="integer",
     *     description="Количество файлов в папке",
     *     example=5
     * )
     */
    public int $count;

    /**
     * @OA\Property(
     *     property="countFolder",
     *     type="integer",
     *     description="Количество вложенных папок",
     *     example=2
     * )
     */
    public int $countFolder;

    /**
     * @OA\Property(
     *     property="parent",
     *     ref="#/components/schemas/Folder",
     *     description="Родительская папка",
     *     nullable=true
     * )
     */
    public ?object $parent;

    /**
     * @OA\Property(
     *     property="children",
     *     type="array",
     *     description="Вложенные папки",
     *     @OA\Items(ref="#/components/schemas/Folder")
     * )
     */
    public array $children;
}

