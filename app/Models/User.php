<?php

namespace App\Models;

use App\Enums\UserRole as UserRoleEnum;
use App\Models\Traits\Filterable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use Filterable, HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'role_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Отправка уведомления о верификации email
     */
    public function sendEmailVerificationNotification(): void
    {
        try {
            $this->notify(new VerifyEmail());
        } catch (\Exception $exception) {
            report($exception);
        }
    }

    /**
     * Связь с ролью пользователя
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(UserRole::class, 'role_id');
    }

    /**
     * Получить Enum роли пользователя
     */
    public function getRoleEnum(): ?UserRoleEnum
    {
        return UserRoleEnum::tryFrom($this->role_id);
    }

    /**
     * Проверка, является ли пользователь администратором
     */
    public function isAdmin(): bool
    {
        return $this->getRoleEnum()?->isAdmin() ?? false;
    }

    /**
     * Проверка, является ли пользователь модератором или выше
     */
    public function isModerator(): bool
    {
        return $this->getRoleEnum()?->isModerator() ?? false;
    }

    /**
     * Проверка, является ли пользователь разработчиком
     */
    public function isDeveloper(): bool
    {
        return $this->getRoleEnum()?->isDeveloper() ?? false;
    }

    /**
     * Проверка, подтверждена ли почта
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Scope для пользователей с определенной ролью
     */
    public function scopeWithRole($query, int|UserRoleEnum $role)
    {
        $roleId = $role instanceof UserRoleEnum ? $role->value : $role;
        return $query->where('role_id', $roleId);
    }

    /**
     * Scope для администраторов
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role_id', [UserRoleEnum::ADMIN->value, UserRoleEnum::DEVELOPER->value]);
    }

    /**
     * Scope для модераторов и выше
     */
    public function scopeModerators($query)
    {
        return $query->whereIn('role_id', [
            UserRoleEnum::MODERATOR->value,
            UserRoleEnum::ADMIN->value,
            UserRoleEnum::DEVELOPER->value,
        ]);
    }
}
