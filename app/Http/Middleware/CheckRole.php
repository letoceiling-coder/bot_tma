<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @param string ...$roles Список допустимых ролей (например: "admin", "moderator")
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return response()->json([
                'notify' => [
                    'title' => 'Ошибка доступа',
                    'text' => 'Необходима авторизация',
                    'status' => 'error',
                ]
            ], 401);
        }

        $userRole = UserRole::tryFrom($request->user()->role_id);

        if (!$userRole) {
            return response()->json([
                'notify' => [
                    'title' => 'Ошибка',
                    'text' => 'Роль пользователя не определена',
                    'status' => 'error',
                ]
            ], 403);
        }

        // Проверяем роли
        foreach ($roles as $role) {
            if ($this->checkRole($userRole, $role)) {
                return $next($request);
            }
        }

        return response()->json([
            'notify' => [
                'title' => 'Доступ запрещен',
                'text' => 'У вас недостаточно прав для выполнения этого действия',
                'status' => 'error',
            ]
        ], 403);
    }

    /**
     * Проверка соответствия роли
     */
    private function checkRole(UserRole $userRole, string $requiredRole): bool
    {
        return match(strtolower($requiredRole)) {
            'user' => true, // Любой авторизованный пользователь
            'moderator' => $userRole->isModerator(),
            'admin' => $userRole->isAdmin(),
            'developer' => $userRole->isDeveloper(),
            default => false,
        };
    }
}

