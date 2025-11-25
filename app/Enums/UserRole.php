<?php

namespace App\Enums;

enum UserRole: int
{
    case USER = 1;
    case MODERATOR = 500;
    case ADMIN = 900;
    case DEVELOPER = 999;

    /**
     * Получить название роли
     */
    public function label(): string
    {
        return match($this) {
            self::USER => 'Пользователь',
            self::MODERATOR => 'Модератор',
            self::ADMIN => 'Администратор',
            self::DEVELOPER => 'Разработчик',
        };
    }

    /**
     * Получить описание роли
     */
    public function description(): string
    {
        return match($this) {
            self::USER => 'Пользователь, ограничение в доступе',
            self::MODERATOR => 'Модератор, ограниченный доступ',
            self::ADMIN => 'Администратор, полный доступ',
            self::DEVELOPER => 'Разработчик, максимальный доступ',
        };
    }

    /**
     * Является ли роль системной
     */
    public function isSystem(): bool
    {
        return match($this) {
            self::USER, self::ADMIN, self::DEVELOPER => true,
            self::MODERATOR => false,
        };
    }

    /**
     * Проверка, является ли роль администратором или выше
     */
    public function isAdmin(): bool
    {
        return in_array($this, [self::ADMIN, self::DEVELOPER]);
    }

    /**
     * Проверка, является ли роль модератором или выше
     */
    public function isModerator(): bool
    {
        return in_array($this, [self::MODERATOR, self::ADMIN, self::DEVELOPER]);
    }

    /**
     * Проверка, является ли роль разработчиком
     */
    public function isDeveloper(): bool
    {
        return $this === self::DEVELOPER;
    }

    /**
     * Получить уровень доступа роли
     */
    public function level(): int
    {
        return $this->value;
    }

    /**
     * Проверка, имеет ли текущая роль больше прав чем указанная
     */
    public function hasHigherAccessThan(self $role): bool
    {
        return $this->value > $role->value;
    }

    /**
     * Проверка, имеет ли текущая роль такие же или больше прав чем указанная
     */
    public function hasAccessLevelOf(self $role): bool
    {
        return $this->value >= $role->value;
    }

    /**
     * Получить все роли
     */
    public static function all(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Получить все роли с названиями
     */
    public static function toArray(): array
    {
        return array_map(
            fn(self $role) => [
                'id' => $role->value,
                'name' => $role->name,
                'label' => $role->label(),
                'description' => $role->description(),
                'is_system' => $role->isSystem(),
            ],
            self::cases()
        );
    }

    /**
     * Попытка получить роль по значению
     */
    public static function tryFromValue(int $value): ?self
    {
        return self::tryFrom($value);
    }

    /**
     * Получить роль по умолчанию
     */
    public static function default(): self
    {
        return self::USER;
    }
}

