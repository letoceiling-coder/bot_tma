# ✅ Удаление полей image_id, subscription, active - Завершено

## 🗑️ Что было удалено:

### Поля из базы данных:
- ❌ `image_id` - ID изображения профиля
- ❌ `subscription` - Наличие подписки
- ❌ `active` - Активность пользователя

---

## 📝 Изменения в файлах:

### 1️⃣ **database/migrations/2014_10_12_000000_create_users_table.php**

**Удалено:**
```php
$table->unsignedBigInteger('image_id')->nullable();
$table->boolean('subscription')->default(false);
$table->boolean('active')->default(true);
```

**Удалены индексы:**
```php
$table->index('active');
$table->index(['role_id', 'active']); // Составной индекс
```

**Осталось:**
- ✅ role_id
- ✅ name
- ✅ email
- ✅ phone
- ✅ password
- ✅ email_verified_at
- ✅ timestamps
- ✅ softDeletes

---

### 2️⃣ **app/Models/User.php**

**Удалено из `$fillable`:**
```php
'image_id',
'subscription',
'active',
```

**Удалено из `$casts`:**
```php
'image_id' => 'integer',
'subscription' => 'boolean',
'active' => 'boolean',
```

**Удалены методы:**
```php
public function isActive(): bool
public function hasSubscription(): bool
public function scopeActive($query)
```

**Остались методы:**
- ✅ `isAdmin()`
- ✅ `isModerator()`
- ✅ `isDeveloper()`
- ✅ `hasVerifiedEmail()`
- ✅ `getRoleEnum()`
- ✅ `scopeWithRole()`
- ✅ `scopeAdmins()`
- ✅ `scopeModerators()`

---

### 3️⃣ **app/Http/Requests/UpdateUserRequest.php**

**Удалены правила валидации:**
```php
'image_id' => ['nullable', 'integer', 'exists:images,id'],
'subscription' => ['sometimes', 'boolean'],
'active' => ['sometimes', 'boolean'],
```

**Удалена логика prepareForValidation:**
```php
// Обработка image_id как объекта
// Преобразование boolean subscription
// Преобразование boolean active
```

**Удалены сообщения:**
```php
'image_id.exists' => '...',
```

**Удалены атрибуты:**
```php
'image_id' => 'Изображение',
'subscription' => 'Подписка',
'active' => 'Активность',
```

---

### 4️⃣ **app/Http/Resources/UserResource.php**

**Удалены поля из ответа:**
```php
'image_id' => $this->when(isset($this->image_id), $this->image_id),
'subscription' => $this->when(isset($this->subscription), (bool) $this->subscription),
'active' => $this->when(isset($this->active), (bool) $this->active),
```

**Остались поля:**
- ✅ id
- ✅ name
- ✅ email
- ✅ phone
- ✅ email_verified_at
- ✅ role_id
- ✅ role (связь)
- ✅ created_at
- ✅ updated_at
- ✅ is_admin (computed)
- ✅ is_moderator (computed)

---

### 5️⃣ **app/Http/Controllers/Api/v1/PassportAuthController.php**

**Swagger аннотации - удалено из @OA\RequestBody для update:**
```php
@OA\Property(property="image_id", type="integer", example=5, description="ID изображения профиля"),
@OA\Property(property="subscription", type="boolean", example=true, description="Наличие подписки"),
@OA\Property(property="active", type="boolean", example=true, description="Активность пользователя")
```

---

### 6️⃣ **app/Http/Controllers/Api/v1/Schemas/UserSchema.php**

**Swagger схема User - удалено:**
```php
@OA\Property(property="image_id", type="integer", example=5, description="ID изображения профиля", nullable=true),
@OA\Property(property="subscription", type="boolean", example=false, description="Наличие подписки"),
@OA\Property(property="active", type="boolean", example=true, description="Активен ли пользователь"),
```

---

### 7️⃣ **app/Http/Middleware/EnsureUserIsActive.php**

**Файл полностью удален ❌**

Middleware больше не нужен, так как поле `active` удалено.

---

### 8️⃣ **app/Http/Kernel.php**

**Удалена регистрация middleware:**
```php
'active' => \App\Http\Middleware\EnsureUserIsActive::class,
```

---

### 9️⃣ **Swagger документация**

**Регенерирована:**
```bash
✅ php artisan l5-swagger:generate
```

Документация обновлена без удаленных полей.

---

## 🔄 Миграция базы данных

### Если БД уже создана, нужно:

```bash
# Вариант 1: Полный пересоздание (удалит данные!)
php artisan migrate:fresh --seed

# Вариант 2: Создать миграцию для удаления полей
php artisan make:migration remove_unused_fields_from_users_table
```

### Пример миграции для удаления полей:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['image_id', 'subscription', 'active']);
            $table->dropIndex(['users_active_index']); // если есть
            $table->dropIndex(['users_role_id_active_index']); // если есть
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('image_id')->nullable();
            $table->boolean('subscription')->default(false);
            $table->boolean('active')->default(true);
            $table->index('active');
            $table->index(['role_id', 'active']);
        });
    }
};
```

Затем:
```bash
php artisan migrate
```

---

## ✅ Проверочный чеклист:

- [x] Миграция обновлена
- [x] Модель User очищена от полей
- [x] Request валидация обновлена
- [x] Resource ответы обновлены
- [x] Swagger аннотации обновлены
- [x] Swagger схемы обновлены
- [x] Middleware удален
- [x] Kernel.php обновлен
- [x] Swagger регенерирован
- [x] Проверка на ошибки пройдена

---

## 📊 Текущая структура User:

### Поля в БД:
```
✅ id
✅ role_id
✅ name
✅ email
✅ phone (nullable)
✅ email_verified_at (nullable)
✅ password
✅ remember_token
✅ created_at
✅ updated_at
✅ deleted_at (SoftDeletes)
```

### Доступные методы:
```php
// Роли
$user->isAdmin()
$user->isModerator()
$user->isDeveloper()
$user->getRoleEnum()

// Проверки
$user->hasVerifiedEmail()

// Scopes
User::withRole($roleId)
User::admins()
User::moderators()
```

### API ответ (UserResource):
```json
{
    "id": 1,
    "name": "Иван Иванов",
    "email": "user@example.com",
    "phone": "+7 999 123-45-67",
    "email_verified_at": "2024-01-15 10:30:00",
    "role_id": 1,
    "role": {
        "id": 1,
        "name": "USER",
        "description": "Пользователь",
        "system": true,
        "is_admin": false,
        "is_moderator": false,
        "is_user": true,
        "is_developer": false,
        "level": 1
    },
    "created_at": "2024-01-01 12:00:00",
    "updated_at": "2024-01-15 12:00:00",
    "is_admin": false,
    "is_moderator": false
}
```

---

## 🚀 Команды для применения изменений:

```bash
# 1. Очистить кеш
php artisan config:clear
php artisan cache:clear

# 2. Пересоздать БД (если тестовая среда)
php artisan migrate:fresh --seed

# 3. Или применить новую миграцию для удаления полей
php artisan make:migration remove_unused_fields_from_users_table
# (отредактировать миграцию)
php artisan migrate

# 4. Обновить composer autoload
composer dump-autoload

# 5. Проверить документацию Swagger
php artisan l5-swagger:generate
```

---

## 🎯 Swagger документация:

**Доступ:**
```
http://localhost/api/documentation
```

**Обновлено:**
- ✅ User Schema без удаленных полей
- ✅ Update endpoint без удаленных полей
- ✅ Все примеры актуальны

---

## 📝 Примечания:

### Что осталось неизменным:
- ✅ Система ролей (UserRole Enum)
- ✅ Middleware для проверки ролей (CheckRole)
- ✅ Все эндпоинты аутентификации
- ✅ OAuth2 через Passport
- ✅ SoftDeletes для пользователей
- ✅ Валидация паролей

### Что было упрощено:
- ✅ Меньше полей в БД
- ✅ Проще валидация
- ✅ Чище API ответы
- ✅ Меньше middleware

---

## ✨ Готово!

Все поля `image_id`, `subscription`, `active` успешно удалены из проекта!

**Следующий шаг:** Применить миграции на вашей БД

```bash
php artisan migrate:fresh --seed
```

Или создать отдельную миграцию для удаления полей из существующей БД.

---

*Документация обновлена | Swagger регенерирован | 2024*

