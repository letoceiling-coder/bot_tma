# 🧪 Руководство по тестированию

## Запуск тестов

### Все тесты
```bash
php artisan test
```

### Только Unit тесты
```bash
php artisan test --testsuite=Unit
```

### Только Feature тесты
```bash
php artisan test --testsuite=Feature
```

### Конкретный тест класс
```bash
php artisan test --filter TelegramInitDataValidatorTest
```

### Конкретный тест метод
```bash
php artisan test --filter test_validates_correct_init_data
```

## Структура тестов

### Unit тесты (`tests/Unit/`)

#### `TelegramInitDataValidatorTest.php`
Тесты валидации initData от Telegram WebApp:
- ✅ `test_validates_correct_init_data` - валидация корректного initData
- ✅ `test_rejects_init_data_with_invalid_hash` - отклонение initData с неверным hash
- ✅ `test_rejects_init_data_without_hash` - отклонение initData без hash
- ✅ `test_rejects_old_init_data` - отклонение устаревшего initData (старше 24 часов)
- ✅ `test_extracts_user_id_from_init_data` - извлечение user_id
- ✅ `test_extracts_user_data_from_init_data` - извлечение данных пользователя
- ✅ `test_returns_null_for_invalid_user_json` - обработка некорректного JSON

#### `ChannelTest.php`
Тесты модели Channel:
- ✅ `test_get_required_channels` - получение обязательных каналов
- ✅ `test_get_chat_id_for_check_with_telegram_chat_id` - получение chat_id с telegram_chat_id
- ✅ `test_get_chat_id_for_check_without_telegram_chat_id` - получение chat_id без telegram_chat_id
- ✅ `test_get_chat_id_for_check_with_at_symbol_in_username` - обработка @ в username
- ✅ `test_casts_boolean_fields` - проверка cast boolean полей
- ✅ `test_casts_sort_order` - проверка cast sort_order

### Feature тесты (`tests/Feature/`)

#### `SubscriptionControllerTest.php`
Тесты API проверки подписок:
- ✅ `test_can_get_channels_list` - получение списка каналов
- ✅ `test_check_subscriptions_all_subscribed` - проверка когда все подписаны
- ✅ `test_check_subscriptions_not_all_subscribed` - проверка когда не все подписаны
- ✅ `test_check_subscriptions_no_required_channels` - проверка без обязательных каналов
- ✅ `test_check_subscriptions_requires_init_data` - проверка обязательности initData
- ✅ `test_check_subscriptions_uses_cache` - проверка кеширования
- ✅ `test_can_clear_subscription_cache` - очистка кеша

#### `ChannelControllerTest.php`
Тесты API управления каналами:
- ✅ `test_can_get_channels_list` - получение списка каналов
- ✅ `test_can_create_channel` - создание канала
- ✅ `test_creation_requires_title_and_username` - валидация при создании
- ✅ `test_can_update_channel` - обновление канала
- ✅ `test_can_delete_channel` - удаление канала
- ✅ `test_can_sync_channels` - синхронизация каналов
- ✅ `test_sync_deletes_channels_not_in_list` - удаление каналов не в списке
- ✅ `test_requires_authentication` - проверка авторизации

## Настройка для тестирования

### База данных для тестов

По умолчанию тесты используют SQLite в памяти. Для настройки:

1. Создайте `.env.testing` файл:
```env
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

2. Или используйте тестовую БД в `phpunit.xml`:
```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

### Фабрики (Factories)

Используются следующие фабрики:
- `ChannelFactory` - создание тестовых каналов
- `UserFactory` - создание тестовых пользователей

### Моки (Mocks)

В тестах используются моки для:
- HTTP запросов к Telegram Bot API (`Http::fake()`)
- Кеширования (`Cache::put()`, `Cache::get()`)

## Примеры использования

### Запуск с покрытием кода
```bash
php artisan test --coverage
```

### Запуск с детальным выводом
```bash
php artisan test --verbose
```

### Запуск с остановкой на первой ошибке
```bash
php artisan test --stop-on-failure
```

### Параллельный запуск (если установлен)
```bash
php artisan test --parallel
```

## Результаты

### ✅ Все тесты проходят:
- **Unit тесты**: 14 passed (24 assertions)
  - `TelegramInitDataValidatorTest`: 7 тестов
  - `ChannelTest`: 6 тестов
  - `ExampleTest`: 1 тест

- **Feature тесты**: 16 passed (89 assertions)
  - `SubscriptionControllerTest`: 7 тестов
  - `ChannelControllerTest`: 8 тестов
  - `ExampleTest`: 1 тест

**Итого: 30 тестов, 113 утверждений - все проходят! ✅**

## Дополнительные тесты для будущего

Рекомендуется добавить:
- [ ] E2E тесты для полного флоу подписки
- [ ] Тесты производительности для проверки подписок
- [ ] Интеграционные тесты с реальным Telegram Bot API (в dev режиме)
- [ ] Тесты безопасности (попытки подделки initData)

