<?php

namespace App\Http\Controllers\Api\v1\Schemas;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="Модель пользователя",
 *     @OA\Property(property="id", type="integer", example=1, description="ID пользователя"),
 *     @OA\Property(property="name", type="string", example="Иван Иванов", description="Имя пользователя"),
 *     @OA\Property(property="email", type="string", format="email", example="user@example.com", description="Email пользователя"),
 *     @OA\Property(property="phone", type="string", example="+7 999 123-45-67", description="Телефон", nullable=true),
 *     @OA\Property(property="email_verified_at", type="string", format="date-time", example="2024-01-15 10:30:00", description="Дата подтверждения email", nullable=true),
 *     @OA\Property(property="role_id", type="integer", example=1, description="ID роли"),
 *     @OA\Property(property="role", ref="#/components/schemas/UserRole", description="Роль пользователя"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01 12:00:00", description="Дата создания"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15 12:00:00", description="Дата обновления"),
 *     @OA\Property(property="is_admin", type="boolean", example=false, description="Является ли администратором"),
 *     @OA\Property(property="is_moderator", type="boolean", example=false, description="Является ли модератором или выше")
 * )
 */
class UserSchema {}

/**
 * @OA\Schema(
 *     schema="UserRole",
 *     type="object",
 *     title="UserRole",
 *     description="Роль пользователя",
 *     @OA\Property(property="id", type="integer", example=1, description="ID роли"),
 *     @OA\Property(property="name", type="string", example="USER", description="Название роли"),
 *     @OA\Property(property="description", type="string", example="Пользователь, ограничение в доступе", description="Описание роли"),
 *     @OA\Property(property="system", type="boolean", example=true, description="Системная роль"),
 *     @OA\Property(property="is_admin", type="boolean", example=false, description="Роль администратора"),
 *     @OA\Property(property="is_moderator", type="boolean", example=false, description="Роль модератора"),
 *     @OA\Property(property="is_user", type="boolean", example=true, description="Обычный пользователь"),
 *     @OA\Property(property="is_developer", type="boolean", example=false, description="Роль разработчика"),
 *     @OA\Property(property="level", type="integer", example=1, description="Уровень доступа")
 * )
 */
class UserRoleSchema {}

