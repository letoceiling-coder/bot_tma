<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="CRM для Telegram ботов API",
 *     version="1.0.0",
 *     description="API документация для CRM системы управления Telegram ботами",
 *     @OA\Contact(
 *         email="support@example.com"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="/api/v1",
 *     description="API V1"
 * )
 * 
 * @OA\SecurityScheme(
 *     type="http",
 *     securityScheme="bearerAuth",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Введите токен в формате: Bearer {token}"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="Операции с аутентификацией"
 * )
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="Операции с пользователями"
 * )
 */
class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
