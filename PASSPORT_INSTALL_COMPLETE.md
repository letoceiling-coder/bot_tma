# ✅ Laravel Passport - Установка завершена!

## 🔑 Что было сделано:

### 1. Созданы ключи шифрования

```bash
✅ php artisan passport:install
```

**Ключи созданы:**
- ✅ `storage/oauth-private.key` - Приватный ключ
- ✅ `storage/oauth-public.key` - Публичный ключ

Эти ключи используются для подписи и проверки JWT токенов.

---

## ⚠️ Следующие шаги

### 1. Настроить базу данных

Passport требует базу данных для хранения токенов и клиентов.

#### Вариант A: Создать базу данных через OSPanel

1. Откройте **PhpMyAdmin**: http://localhost/openserver/?action=tools.phpmyadmin
2. Создайте новую БД (например: `tg_crm`)
3. Кодировка: `utf8mb4_unicode_ci`

#### Вариант B: Через MySQL консоль

```sql
CREATE DATABASE tg_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. Обновить .env файл

Откройте `.env` и обновите настройки БД:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tg_crm
DB_USERNAME=root
DB_PASSWORD=
```

**Для OSPanel обычно:**
- Username: `root`
- Password: пустой или смотрите в настройках OSPanel

### 3. Запустить миграции

После настройки БД:

```bash
# Очистить кеш конфигурации
php artisan config:clear

# Запустить миграции
php artisan migrate

# Заполнить роли
php artisan db:seed --class=UserRoleSeeder

# Passport клиенты (если нужны)
php artisan passport:client --personal
php artisan passport:client --password
```

---

## 📋 Passport миграции

Passport автоматически создаст следующие таблицы:

1. **oauth_auth_codes** - Authorization codes
2. **oauth_access_tokens** - Access tokens
3. **oauth_refresh_tokens** - Refresh tokens
4. **oauth_clients** - OAuth клиенты
5. **oauth_personal_access_clients** - Personal access клиенты

---

## 🔧 Конфигурация Passport

### Обновить AuthServiceProvider (если нужно)

`app/Providers/AuthServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [];

    public function boot(): void
    {
        // Необязательно - можно оставить по умолчанию
        // Passport::tokensExpireIn(now()->addDays(15));
        // Passport::refreshTokensExpireIn(now()->addDays(30));
        // Passport::personalAccessTokensExpireIn(now()->addMonths(6));
    }
}
```

### config/auth.php уже настроен

Проверьте что guard использует `passport`:

```php
'guards' => [
    'api' => [
        'driver' => 'passport',
        'provider' => 'users',
    ],
],
```

---

## 🎯 Текущий статус

| Компонент | Статус |
|-----------|--------|
| Passport пакет | ✅ Установлен |
| Ключи шифрования | ✅ Созданы |
| Миграции | ⏳ Ожидают запуска |
| База данных | ⏳ Требует настройки |
| OAuth клиенты | ⏳ Будут созданы после миграций |

---

## 🚀 Полная последовательность запуска

```bash
# 1. Создать БД в PhpMyAdmin или MySQL

# 2. Обновить .env с настройками БД

# 3. Очистить кеш
php artisan config:clear
php artisan cache:clear

# 4. Запустить все миграции
php artisan migrate

# 5. Заполнить роли
php artisan db:seed --class=UserRoleSeeder

# 6. (Опционально) Создать OAuth клиентов
php artisan passport:client --personal
php artisan passport:client --password

# 7. Проверить что Passport работает
php artisan tinker
>>> $user = App\Models\User::first();
>>> $token = $user->createToken('Test Token');
>>> $token->accessToken; // Должен вернуть токен
```

---

## 🧪 Тестирование Passport

### Через Swagger UI:

1. Откройте: http://crm.loc/api/documentation
2. Попробуйте `POST /auth/register`:
   ```json
   {
     "name": "Test User",
     "email": "test@example.com",
     "password": "Password123!",
     "password_confirmation": "Password123!"
   }
   ```
3. Получите `accessToken`
4. Нажмите "Authorize" 🔓
5. Введите: `Bearer ваш_токен`
6. Тестируйте защищенные эндпоинты!

### Через Postman/Insomnia:

```http
POST http://crm.loc/api/v1/auth/register
Content-Type: application/json

{
  "name": "Test User",
  "email": "test@example.com",
  "password": "Password123!",
  "password_confirmation": "Password123!"
}
```

Получите токен и используйте в заголовке:
```
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJh...
```

---

## 📁 Файлы Passport

```
storage/
├── oauth-private.key     ✅ Приватный ключ (НЕ коммитить!)
├── oauth-public.key      ✅ Публичный ключ
└── ...

.gitignore должен содержать:
/storage/*.key
```

**ВАЖНО:** Ключи в `.gitignore` - не коммитьте их в Git!

---

## 🔒 Безопасность

### Права на ключи:

```bash
# Linux/Mac:
chmod 600 storage/oauth-*.key

# Windows:
# Убедитесь что только ваш пользователь имеет доступ
```

### .env переменные (опционально):

```env
PASSPORT_PRIVATE_KEY="путь/к/приватному/ключу"
PASSPORT_PUBLIC_KEY="путь/к/публичному/ключу"
```

---

## ❓ Частые проблемы

### 1. "Key path does not exist"

**Решение:**
```bash
php artisan passport:keys --force
```

### 2. "Database not found"

**Решение:**
- Создайте БД в PhpMyAdmin
- Обновите `.env`
- Запустите `php artisan config:clear`

### 3. "Class 'finfo' not found" (уже решено)

Ключи созданы, это нормально. Миграции можно запустить отдельно.

### 4. Токены не работают

**Проверьте:**
- Passport ключи существуют
- Миграции запущены
- `config/auth.php` использует `driver => 'passport'`
- User модель использует `HasApiTokens` trait

---

## ✅ Проверочный чеклист

- [x] Passport установлен
- [x] Ключи шифрования созданы
- [ ] База данных создана
- [ ] .env настроен
- [ ] Миграции запущены
- [ ] Seeders запущены
- [ ] API тестирование пройдено

---

## 📚 Документация

**Laravel Passport:**
- https://laravel.com/docs/10.x/passport

**OAuth2:**
- https://oauth.net/2/

---

## 🎉 Статус

Passport ключи созданы! ✅

**Следующий шаг:**
1. Создайте базу данных
2. Настройте `.env`
3. Запустите `php artisan migrate`

После этого система аутентификации будет полностью готова! 🚀

---

*Passport установлен | Ключи созданы | 2024*

