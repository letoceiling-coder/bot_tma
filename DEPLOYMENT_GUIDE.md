# 🚀 Руководство по развертыванию в продакшене

## 📋 Предварительные требования

### 1. Настройка Telegram бота

1. Создайте бота через [@BotFather](https://t.me/BotFather):
   ```
   /newbot
   ```

2. Получите токен бота (формат: `123456789:ABCdefGHIjklMNOpqrsTUVwxyz`)

3. Создайте каналы для подписки:
   - Создайте публичные каналы в Telegram
   - Добавьте бота как администратора в каждый канал
   - Скопируйте username каналов (без @)

### 2. Настройка переменных окружения

Добавьте в `.env`:

```env
# Telegram Bot
TELEGRAM_BOT_TOKEN=your_bot_token_here
TELEGRAM_BOT_USERNAME=your_bot_username

# App Settings
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Redis (опционально, для кеширования)
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## 📦 Установка и настройка

### Шаг 1: Установка зависимостей

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

### Шаг 2: Запуск миграций и сидеров

```bash
php artisan migrate
php artisan db:seed --class=ChannelSeeder
```

### Шаг 3: Настройка каналов

После создания таблицы `channels`, добавьте каналы через админку или напрямую в БД:

```sql
INSERT INTO channels (title, description, username, url, telegram_chat_id, sort_order, is_active, is_required, created_at, updated_at) VALUES
('Основной канал', 'Новости и обновления', 'your_channel', 'https://t.me/your_channel', '@your_channel', 1, 1, 1, NOW(), NOW()),
('Новости проекта', 'Актуальная информация', 'your_news', 'https://t.me/your_news', '@your_news', 2, 1, 1, NOW(), NOW());
```

Или используйте Tinker:

```bash
php artisan tinker
```

```php
use App\Models\Channel;

Channel::create([
    'title' => 'Основной канал',
    'description' => 'Новости и обновления',
    'username' => 'your_channel',
    'url' => 'https://t.me/your_channel',
    'telegram_chat_id' => '@your_channel',
    'sort_order' => 1,
    'is_active' => true,
    'is_required' => true,
]);
```

### Шаг 4: Настройка WebApp URL в Telegram

В [@BotFather](https://t.me/BotFather):

```
/setdomain
@your_bot_username
yourdomain.com
```

Затем настройте WebApp:

```
/setmenubutton
@your_bot_username
Колесо фортуны
https://yourdomain.com/start
```

### Шаг 5: Оптимизация приложения

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## 🔒 Безопасность

### Проверка initData

В продакшене валидация initData **обязательна**. Убедитесь что:

1. `APP_ENV=production` установлен в `.env`
2. `APP_DEBUG=false`
3. Токен бота хранится только в `.env`, не коммитьте его в Git

### HTTPS

Telegram WebApp работает **только** по HTTPS. Убедитесь что:

1. SSL сертификат установлен и валиден
2. Все запросы перенаправляются на HTTPS
3. В Laravel установлен `APP_URL` с `https://`

## 🧪 Тестирование

### 1. Тест проверки подписок

Откройте в Telegram:

```
https://t.me/your_bot_username?start=test
```

Убедитесь что:
- Если не подписан - показывается страница подписки
- После подписки на все каналы - доступен основной функционал

### 2. Проверка API

```bash
curl -X GET "https://yourdomain.com/api/v1/subscriptions/channels"
```

Должен вернуть список активных каналов.

## 📊 Мониторинг

### Логирование

Ошибки логируются в:
- `storage/logs/laravel.log`

Важные события:
- Проверка подписок (успешная/неуспешная)
- Ошибки валидации initData
- Ошибки Telegram Bot API

### Кеширование

Результаты проверки подписок кешируются на 5 минут в `subscriptions_check_{user_id}`.

Очистить кеш:

```bash
php artisan cache:clear
```

## ⚠️ Устранение проблем

### Проблема: "initData not available"

**Решение:**
- Убедитесь что приложение открыто через Telegram Mini App
- Проверьте что Telegram WebApp SDK загружен (см. `resources/views/layouts/app.blade.php`)

### Проблема: "Telegram bot token not configured"

**Решение:**
- Проверьте `.env` файл
- Убедитесь что `TELEGRAM_BOT_TOKEN` установлен
- Выполните `php artisan config:clear` и `php artisan config:cache`

### Проблема: "Невалидные данные от Telegram"

**Решение:**
- В продакшене это означает что initData не прошла валидацию
- Проверьте логи на наличие деталей ошибки
- Убедитесь что токен бота правильный

### Проблема: Подписки не проверяются

**Решение:**
1. Проверьте что бот добавлен как администратор в каналы
2. Убедитесь что `telegram_chat_id` правильно указан в БД
3. Проверьте логи Telegram Bot API запросов

## 🔄 Обновление

При обновлении приложения:

```bash
git pull
composer install --optimize-autoloader --no-dev
npm install
npm run build
php artisan migrate
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

## 📝 Чеклист перед запуском

- [ ] Токен бота установлен в `.env`
- [ ] Каналы созданы и добавлены в БД
- [ ] Бот добавлен как администратор в каналы
- [ ] WebApp URL настроен в BotFather
- [ ] SSL сертификат установлен (HTTPS обязателен)
- [ ] `APP_ENV=production` установлен
- [ ] Кеш конфигурации обновлен
- [ ] Миграции выполнены
- [ ] Тестирование пройдено

## 🎯 Дополнительные ресурсы

- [Telegram Bot API](https://core.telegram.org/bots/api)
- [Telegram Mini Apps](https://core.telegram.org/bots/webapps)
- [Laravel Documentation](https://laravel.com/docs)

