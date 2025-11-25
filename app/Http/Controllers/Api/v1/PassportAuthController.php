<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\LogsRequestParameters;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PassportAuthController extends Controller
{
    use LogsRequestParameters;
    private const TOKEN_NAME = 'authToken';
    private const COOKIE_NAME = 'accessToken';
    private const COOKIE_LIFETIME = 60 * 24 * 30; // 30 дней

    /**
     * Регистрация нового пользователя
     * 
     * @OA\Post(
     *     path="/auth/register",
     *     tags={"Authentication"},
     *     summary="Регистрация нового пользователя",
     *     description="Создает нового пользователя и возвращает токен доступа",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "password", "password_confirmation"},
     *             @OA\Property(property="name", type="string", example="Иван Иванов", description="Имя пользователя"),
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="Email пользователя"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!", description="Пароль (минимум 8 символов, буквы разного регистра, цифры, символы)"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Password123!", description="Подтверждение пароля"),
     *             @OA\Property(property="role_id", type="integer", example=1, description="ID роли (опционально, по умолчанию 1 - User)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Успешная регистрация",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="accessToken", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *             @OA\Property(property="notify", type="object",
     *                 @OA\Property(property="title", type="string", example="Успешно"),
     *                 @OA\Property(property="text", type="string", example="Вы успешно зарегистрировались"),
     *                 @OA\Property(property="status", type="string", example="success")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Ошибка сервера",
     *         @OA\JsonContent(
     *             @OA\Property(property="notify", type="object",
     *                 @OA\Property(property="title", type="string", example="Ошибка регистрации"),
     *                 @OA\Property(property="text", type="string", example="Произошла ошибка на сервере. Попробуйте позже."),
     *                 @OA\Property(property="status", type="string", example="error")
     *             )
     *         )
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'user/register');
        
        DB::beginTransaction();
        
        try {
            // Создаем пользователя
            $user = User::create([
                ...$request->validated(),
                'password' => Hash::make($request->password),
            ]);

            // Создаем токен
            $token = $user->createToken(self::TOKEN_NAME);
            $accessToken = $token->accessToken;

            // Устанавливаем cookie
            $this->setAccessTokenCookie($accessToken);

            DB::commit();

            return response()->json([
                'user' => new UserResource($user->load('role')),
                'accessToken' => $accessToken,
                'notify' => [
                    'title' => 'Успешно',
                    'text' => 'Вы успешно зарегистрировались',
                    'status' => 'success',
                ]
            ], 201);

        } catch (\Exception $exception) {
            DB::rollBack();
            
            Log::error('Registration failed', [
                'email' => $request->email,
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'notify' => [
                    'title' => 'Ошибка регистрации',
                    'text' => 'Произошла ошибка на сервере. Попробуйте позже.',
                    'status' => 'error',
                ]
            ], 500);
        }
    }

    /**
     * Авторизация пользователя
     * 
     * @OA\Post(
     *     path="/auth/login",
     *     tags={"Authentication"},
     *     summary="Вход в систему",
     *     description="Авторизует пользователя и возвращает токен доступа",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="Email пользователя"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!", description="Пароль")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Успешная авторизация",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="accessToken", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGc..."),
     *             @OA\Property(property="notify", type="object",
     *                 @OA\Property(property="title", type="string", example="Успешно"),
     *                 @OA\Property(property="text", type="string", example="Добро пожаловать!"),
     *                 @OA\Property(property="status", type="string", example="success")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Неверные учетные данные",
     *         @OA\JsonContent(
     *             @OA\Property(property="notify", type="object",
     *                 @OA\Property(property="title", type="string", example="Доступ запрещен"),
     *                 @OA\Property(property="text", type="string", example="Неверный email или пароль"),
     *                 @OA\Property(property="status", type="string", example="error")
     *             )
     *         )
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        if (!Auth::attempt($request->validated())) {
            return response()->json([
                'notify' => [
                    'title' => 'Доступ запрещен',
                    'text' => 'Неверный email или пароль',
                    'status' => 'error',
                ]
            ], 401);
        }

        try {
            /** @var User $user */
            $user = Auth::user();

            // Отзываем все предыдущие токены пользователя (опционально)
            // $user->tokens()->delete();

            // Создаем новый токен
            $token = $user->createToken(self::TOKEN_NAME);
            $accessToken = $token->accessToken;

            // Устанавливаем cookie
            $this->setAccessTokenCookie($accessToken);

            return response()->json([
                'user' => new UserResource($user->load('role')),
                'accessToken' => $accessToken,
                'notify' => [
                    'title' => 'Успешно',
                    'text' => 'Добро пожаловать!',
                    'status' => 'success',
                ]
            ], 200);

        } catch (\Exception $exception) {
            Log::error('Login token creation failed', [
                'user_id' => $user->id ?? null,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'notify' => [
                    'title' => 'Ошибка',
                    'text' => 'Не удалось создать токен доступа',
                    'status' => 'error',
                ]
            ], 500);
        }
    }

    /**
     * Обновление данных пользователя
     * 
     * @OA\Put(
     *     path="/auth/update",
     *     tags={"Authentication"},
     *     summary="Обновить данные пользователя",
     *     description="Обновляет профиль текущего пользователя",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Петр Петров", description="Имя пользователя"),
     *             @OA\Property(property="email", type="string", format="email", example="newmail@example.com", description="Email"),
     *             @OA\Property(property="phone", type="string", example="+7 999 123-45-67", description="Номер телефона"),
     *             @OA\Property(property="password", type="string", format="password", example="NewPassword123!", description="Новый пароль (опционально)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Данные обновлены",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="notify", type="object",
     *                 @OA\Property(property="title", type="string", example="Успешно"),
     *                 @OA\Property(property="text", type="string", example="Данные обновлены"),
     *                 @OA\Property(property="status", type="string", example="success")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Не авторизован"),
     *     @OA\Response(response=422, description="Ошибка валидации")
     * )
     */
    public function update(UpdateUserRequest $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = $request->user();
            $data = $request->validated();

            // Хешируем пароль, если он передан
            if (isset($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            }

            $user->update($data);

            return response()->json([
                'user' => new UserResource($user->fresh('role')),
                'notify' => [
                    'title' => 'Успешно',
                    'text' => 'Данные обновлены',
                    'status' => 'success',
                ]
            ], 200);

        } catch (\Exception $exception) {
            Log::error('User update failed', [
                'user_id' => $request->user()->id,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'notify' => [
                    'title' => 'Ошибка',
                    'text' => 'Не удалось обновить данные',
                    'status' => 'error',
                ]
            ], 500);
        }
    }

    /**
     * Получение данных текущего пользователя
     * 
     * @OA\Get(
     *     path="/auth/user",
     *     tags={"Authentication"},
     *     summary="Получить данные текущего пользователя",
     *     description="Возвращает информацию об авторизованном пользователе",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Данные пользователя",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function user(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'user');
        
        return response()->json([
            'user' => new UserResource($request->user()->load('role'))
        ], 200);
    }

    /**
     * Выход из системы
     * 
     * @OA\Post(
     *     path="/auth/logout",
     *     tags={"Authentication"},
     *     summary="Выход из системы",
     *     description="Отзывает токен доступа и выходит из системы",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Успешный выход",
     *         @OA\JsonContent(
     *             @OA\Property(property="notify", type="object",
     *                 @OA\Property(property="title", type="string", example="Успешно"),
     *                 @OA\Property(property="text", type="string", example="Вы успешно вышли из системы"),
     *                 @OA\Property(property="status", type="string", example="success")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Не авторизован")
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'user/logout');
        
        try {
            /** @var User $user */
            $user = $request->user();
            
            // Отзываем текущий токен
            $token = $user->token();
            if ($token) {
                $token->revoke();
            }

            // Удаляем cookie
            Cookie::queue(Cookie::forget(self::COOKIE_NAME));

            return response()->json([
                'notify' => [
                    'title' => 'Успешно',
                    'text' => 'Вы успешно вышли из системы',
                    'status' => 'success',
                ]
            ], 200);

        } catch (\Exception $exception) {
            Log::error('Logout failed', [
                'user_id' => $request->user()->id ?? null,
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'notify' => [
                    'title' => 'Ошибка',
                    'text' => 'Произошла ошибка при выходе',
                    'status' => 'error',
                ]
            ], 500);
        }
    }

    /**
     * Восстановление пароля
     * 
     * @OA\Post(
     *     path="/auth/forgot-password",
     *     tags={"Authentication"},
     *     summary="Восстановление пароля",
     *     description="Отправляет ссылку для восстановления пароля на email",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="user@example.com", description="Email пользователя")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ссылка отправлена",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ссылка для восстановления пароля отправлена на ваш email")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Ошибка валидации",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        // Логируем все параметры запроса
        $this->logRequestParameters($request, 'user/forgot-password');
        
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Email обязателен для заполнения',
            'email.email' => 'Неверный формат email',
            'email.exists' => 'Пользователь с таким email не найден',
        ]);

        try {
            // Здесь должна быть логика отправки письма с ссылкой восстановления
            // Для примера просто возвращаем успешный ответ
            // В продакшене используйте Password::sendResetLink($request->only('email'))
            
            return response()->json([
                'notify' => [
                    'title' => 'Успешно',
                    'text' => 'Ссылка для восстановления пароля отправлена на ' . $request->email,
                    'status' => 'success',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Password reset failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'notify' => [
                    'title' => 'Ошибка',
                    'text' => 'Не удалось отправить ссылку восстановления. Попробуйте позже.',
                    'status' => 'error',
                ],
            ], 500);
        }
    }

    /**
     * Установка cookie с токеном доступа
     */
    private function setAccessTokenCookie(string $accessToken): void
    {
        Cookie::queue(
            self::COOKIE_NAME,
            $accessToken,
            self::COOKIE_LIFETIME,
            null,
            null,
            config('session.secure'), // secure только на HTTPS
            true, // httpOnly
            false,
            'lax' // SameSite
        );
    }
}
