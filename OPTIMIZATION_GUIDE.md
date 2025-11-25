# 📚 Руководство по оптимизации CRM для Telegram ботов

## 🎉 Что было оптимизировано

### 1. **PassportAuthController** - Контроллер аутентификации

#### ✅ Улучшения:
- Убрана самозапрос к API (вместо HTTP запроса - прямая работа с БД)
- Добавлены транзакции БД для регистрации
- Унифицированы имена токенов через константы
- Улучшена обработка ошибок с логированием
- Cookie устанавливаются через Laravel фасад с правильными флагами безопасности
- Все ответы теперь через JsonResponse с консистентной структурой
- Добавлен метод `forgotPassword` с валидацией

#### 📝 Использование:

```php
// Пример маршрутов в routes/api.php
Route::prefix('auth')->group(function () {
    Route::post('/register', [PassportAuthController::class, 'register']);
    Route::post('/login', [PassportAuthController::class, 'login']);
    Route::post('/forgot-password', [PassportAuthController::class, 'forgotPassword']);
    
    Route::middleware('auth:api')->group(function () {
        Route::get('/user', [PassportAuthController::class, 'user']);
        Route::put('/update', [PassportAuthController::class, 'update']);
        Route::post('/logout', [PassportAuthController::class, 'logout']);
    });
});
```

---

### 2. **Request классы** - Валидация данных

#### ✅ LoginRequest:
- Добавлена валидация DNS для email
- Минимум 8 символов для пароля
- Русские сообщения об ошибках
- Названия атрибутов для красивых сообщений

#### ✅ RegisterRequest:
- Строгая валидация пароля (буквы разного регистра, цифры, символы)
- Проверка на компрометацию пароля через Have I Been Pwned
- Валидация существования роли
- Минимум 2 символа для имени

#### ✅ UpdateUserRequest:
- Валидация телефона через regex
- Игнорирование текущего пользователя при проверке уникальности email
- Обработка image_id как объекта или числа
- Преобразование boolean значений

---

### 3. **Resources** - Сериализация данных

#### ✅ UserResource:
```php
// Условная загрузка связей
'role' => new UserRoleResource($this->whenLoaded('role')),

// Опциональные поля
'phone' => $this->when(isset($this->phone), $this->phone),

// Computed поля
'is_admin' => $this->when(
    $this->relationLoaded('role'),
    fn() => in_array($this->role_id, [900, 999])
),

// Форматирование дат
'created_at' => $this->created_at->format('Y-m-d H:i:s'),
```

#### ✅ UserRoleResource:
- Computed поля для проверки типа роли
- Уровень доступа для сортировки
- Приведение типов для frontend

---

### 4. **Enum для ролей** - UserRole

#### 📝 Использование:

```php
use App\Enums\UserRole;

// Получить роль
$role = UserRole::ADMIN;

// Проверки
if ($role->isAdmin()) {
    // Администратор или разработчик
}

if ($role->isModerator()) {
    // Модератор, админ или разработчик
}

// Получить данные
$label = $role->label(); // "Администратор"
$description = $role->description();
$level = $role->level(); // 900

// Сравнение ролей
$isHigher = UserRole::ADMIN->hasHigherAccessThan(UserRole::MODERATOR); // true

// Получить все роли
$allRoles = UserRole::toArray();

// В модели User
$user->getRoleEnum()->isAdmin();
```

---

### 5. **Миграции** - Структура БД

#### ✅ user_roles:
- Уникальный индекс на `name`
- Индекс на `system`
- Ограничение длины полей
- Данные перенесены в seeder

#### ✅ users:
- Добавлены поля: `phone`, `image_id`, `subscription`, `active`
- SoftDeletes для безопасного удаления
- Индексы на часто используемые поля:
  - `role_id`
  - `active`
  - `email_verified_at`
  - `created_at`
  - Составной индекс `[role_id, active]`
- Foreign key с `restrict` на удаление (защита от удаления роли с пользователями)

---

### 6. **Seeder для ролей** - UserRoleSeeder

#### 📝 Запуск:

```bash
# Запустить все сидеры
php artisan db:seed

# Только роли
php artisan db:seed --class=UserRoleSeeder

# Миграция + сидеры
php artisan migrate:fresh --seed
```

---

### 7. **Middleware для проверки ролей**

#### ✅ CheckRole - Проверка доступа по ролям

```php
// В routes/api.php

// Доступ только для модераторов и выше
Route::middleware(['auth:api', 'role:moderator'])->group(function () {
    Route::get('/moderate/users', [ModerateController::class, 'index']);
});

// Доступ только для администраторов
Route::middleware(['auth:api', 'role:admin'])->group(function () {
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser']);
});

// Доступ только для разработчиков
Route::middleware(['auth:api', 'role:developer'])->group(function () {
    Route::get('/system/logs', [SystemController::class, 'logs']);
});

// Множественные роли (или)
Route::middleware(['auth:api', 'role:moderator,admin'])->group(function () {
    Route::post('/content/approve', [ContentController::class, 'approve']);
});
```

#### ✅ EnsureUserIsActive - Проверка активности пользователя

```php
// Добавить в группу middleware в Kernel.php или использовать в маршрутах
Route::middleware(['auth:api', 'active'])->group(function () {
    // Только для активных пользователей
});
```

---

### 8. **Модель User** - Расширенный функционал

#### 📝 Использование:

```php
use App\Models\User;
use App\Enums\UserRole;

// Проверки ролей
if ($user->isAdmin()) { }
if ($user->isModerator()) { }
if ($user->isDeveloper()) { }

// Проверки статуса
if ($user->isActive()) { }
if ($user->hasSubscription()) { }
if ($user->hasVerifiedEmail()) { }

// Query Scopes
$activeUsers = User::active()->get();
$admins = User::admins()->get();
$moderators = User::moderators()->get();
$usersWithRole = User::withRole(UserRole::MODERATOR)->get();

// Связи
$user->role; // BelongsTo UserRole
$userWithRole = User::with('role')->find(1);

// Enum роли
$roleEnum = $user->getRoleEnum();
if ($roleEnum->isAdmin()) { }
```

---

## 🚀 Как использовать все вместе

### Пример: Защита маршрута

```php
// routes/api.php

Route::prefix('api/v1')->group(function () {
    
    // Публичные маршруты
    Route::post('auth/register', [PassportAuthController::class, 'register']);
    Route::post('auth/login', [PassportAuthController::class, 'login']);
    Route::post('auth/forgot-password', [PassportAuthController::class, 'forgotPassword']);
    
    // Защищенные маршруты (только авторизованные)
    Route::middleware(['auth:api', 'active'])->group(function () {
        
        Route::get('auth/user', [PassportAuthController::class, 'user']);
        Route::put('auth/update', [PassportAuthController::class, 'update']);
        Route::post('auth/logout', [PassportAuthController::class, 'logout']);
        
        // Пользовательские маршруты
        Route::prefix('user')->group(function () {
            Route::get('profile', [UserController::class, 'profile']);
        });
        
        // Маршруты для модераторов
        Route::middleware('role:moderator')->prefix('moderate')->group(function () {
            Route::get('users', [ModerateController::class, 'users']);
            Route::put('users/{id}/verify', [ModerateController::class, 'verifyUser']);
        });
        
        // Маршруты для администраторов
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            Route::resource('users', AdminUserController::class);
            Route::resource('roles', AdminRoleController::class);
        });
        
        // Маршруты для разработчиков
        Route::middleware('role:developer')->prefix('developer')->group(function () {
            Route::get('logs', [DeveloperController::class, 'logs']);
            Route::post('cache/clear', [DeveloperController::class, 'clearCache']);
        });
    });
});
```

### Пример: Контроллер с проверкой ролей

```php
<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Middleware 'role:admin' уже проверил права
        
        $users = User::with('role')
            ->when($request->active, fn($q) => $q->active())
            ->when($request->role_id, fn($q) => $q->withRole($request->role_id))
            ->paginate(20);
        
        return response()->json([
            'users' => UserResource::collection($users),
        ]);
    }
    
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // Разработчик может удалить кого угодно
        // Админ не может удалить разработчика
        if (!$request->user()->isDeveloper() && $user->isDeveloper()) {
            return response()->json([
                'notify' => [
                    'title' => 'Ошибка',
                    'text' => 'Нельзя удалить разработчика',
                    'status' => 'error',
                ]
            ], 403);
        }
        
        $user->delete();
        
        return response()->json([
            'notify' => [
                'title' => 'Успешно',
                'text' => 'Пользователь удален',
                'status' => 'success',
            ]
        ]);
    }
}
```

---

## 📋 Миграция с учетом новых изменений

### Шаг 1: Бэкап БД (если нужно)
```bash
php artisan db:backup # Если есть пакет
# или вручную через mysqldump
```

### Шаг 2: Откат текущих миграций
```bash
php artisan migrate:rollback --step=2
```

### Шаг 3: Запуск новых миграций
```bash
php artisan migrate
```

### Шаг 4: Заполнение ролей
```bash
php artisan db:seed --class=UserRoleSeeder
```

### Или все сразу (осторожно! удалит данные):
```bash
php artisan migrate:fresh --seed
```

---

## 🔧 Настройка SettingSite.php

Обновленный `SettingSite.php` теперь синхронизирован с контроллером:
- Использует те же константы для токенов и cookie
- Работает напрямую с БД вместо HTTP запросов
- Правильно устанавливает cookie через Laravel
- Валидирует токены через JWT

---

## 📊 Производительность

### До оптимизации:
- ❌ HTTP запрос к себе: ~50-100ms
- ❌ Создание токена при каждом запросе
- ❌ Нет индексов в БД
- ❌ N+1 проблемы с загрузкой ролей

### После оптимизации:
- ✅ Прямая работа с БД: ~5-10ms
- ✅ Токен создается только при login/register
- ✅ Индексы ускоряют запросы в 10-100 раз
- ✅ Eager loading связей

---

## 🛡️ Безопасность

### Улучшения:
1. **Cookie флаги**: `httpOnly`, `secure`, `SameSite=lax`
2. **Проверка компрометации паролей** через Have I Been Pwned
3. **SoftDeletes** - пользователи не удаляются физически
4. **Активные/неактивные пользователи** - блокировка без удаления
5. **Foreign key constraints** - целостность данных
6. **Валидация токенов** - проверка срока действия и отзыва

---

## 📝 Чеклист после деплоя

- [ ] Запустить миграции: `php artisan migrate`
- [ ] Заполнить роли: `php artisan db:seed --class=UserRoleSeeder`
- [ ] Проверить Passport ключи: `php artisan passport:keys`
- [ ] Очистить кеш: `php artisan cache:clear`
- [ ] Очистить конфиг: `php artisan config:clear`
- [ ] Перегенерировать autoload: `composer dump-autoload`
- [ ] Протестировать авторизацию
- [ ] Протестировать роли
- [ ] Проверить логи на ошибки

---

## 🎯 Дальнейшие улучшения

1. **Rate limiting** для API
2. **Кеширование** ролей пользователей
3. **Events & Listeners** для логирования действий
4. **Notifications** для важных событий
5. **API версионирование** (v1, v2)
6. **Тесты** для всех компонентов

---

Создано с ❤️ для оптимизации вашей CRM системы!

