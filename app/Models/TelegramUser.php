<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Модель для хранения пользователей Telegram Mini App
 * 
 * Хранит все данные о пользователе из Telegram,
 * количество билетов для крутки колеса,
 * и информацию о рефералах
 */
class TelegramUser extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Таблица, используемая моделью
     */
    protected $table = 'telegram_users';

    /**
     * Поля, которые можно массово присваивать
     */
    protected $fillable = [
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'language_code',
        'photo_url',
        'tickets',
        'referrals_count',
        'invited_by_telegram_user_id',
        'last_active_at',
        'last_ticket_added_at',
        'metadata',
    ];

    /**
     * Поля, которые должны быть скрыты при сериализации
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * Преобразование типов полей
     */
    protected $casts = [
        'telegram_id' => 'integer',
        'tickets' => 'integer',
        'referrals_count' => 'integer',
        'invited_by_telegram_user_id' => 'integer',
        'last_active_at' => 'datetime',
        'last_ticket_added_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Связь с пользователем, который пригласил этого пользователя
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(TelegramUser::class, 'invited_by_telegram_user_id', 'telegram_id');
    }

    /**
     * Связь с пользователями, приглашенными этим пользователем
     */
    public function referrals(): HasMany
    {
        return $this->hasMany(TelegramUser::class, 'invited_by_telegram_user_id', 'telegram_id');
    }

    /**
     * Получить полное имя пользователя
     */
    public function getFullNameAttribute(): string
    {
        $parts = array_filter([$this->first_name, $this->last_name]);
        if (!empty($parts)) {
            return implode(' ', $parts);
        }
        
        return $this->username ?? "User #{$this->telegram_id}";
    }

    /**
     * Получить отображаемое имя пользователя
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->username) {
            return "@{$this->username}";
        }
        
        return $this->full_name;
    }

    /**
     * Проверка, достаточно ли билетов для крутки колеса
     */
    public function hasEnoughTickets(int $requiredTickets = 1): bool
    {
        return $this->tickets >= $requiredTickets;
    }

    /**
     * Списание билетов
     */
    public function spendTickets(int $amount = 1): bool
    {
        if (!$this->hasEnoughTickets($amount)) {
            return false;
        }
        
        $this->tickets -= $amount;
        return $this->save();
    }

    /**
     * Добавление билетов
     */
    public function addTickets(int $amount = 1): bool
    {
        $this->tickets += $amount;
        return $this->save();
    }

    /**
     * Обновление последней активности
     */
    public function updateLastActive(): bool
    {
        $this->last_active_at = now();
        return $this->save();
    }

    /**
     * Увеличение счетчика рефералов
     */
    public function incrementReferralsCount(): bool
    {
        $this->increment('referrals_count');
        return $this->save();
    }

    /**
     * Проверка и автоматическое добавление билетов каждые 24 часа с момента регистрации
     * 
     * Логика:
     * - При регистрации: 1 билет, last_ticket_added_at = created_at
     * - Каждые 24 часа с момента last_ticket_added_at (или created_at, если last_ticket_added_at = null) добавляется 1 билет
     * 
     * @return int Количество добавленных билетов (0 или 1)
     */
    public function checkAndAddTicketsIfNeeded(): int
    {
        $now = now();
        $hoursPerTicket = 24;
        
        // Определяем время отсчета (приоритет: last_ticket_added_at, если null - created_at)
        $referenceTime = $this->last_ticket_added_at ?? $this->created_at;
        
        if (!$referenceTime) {
            // Если нет времени отсчета, устанавливаем текущее время
            $this->last_ticket_added_at = $now;
            $this->save();
            return 0;
        }
        
        // Вычисляем количество прошедших часов
        $hoursSinceLastTicket = $referenceTime->diffInHours($now);
        
        // Если прошло 24 часа или больше, добавляем билет
        if ($hoursSinceLastTicket >= $hoursPerTicket) {
            // Вычисляем, сколько билетов нужно добавить (на случай, если прошло больше 24 часов)
            $ticketsToAdd = (int) floor($hoursSinceLastTicket / $hoursPerTicket);
            
            $this->tickets += $ticketsToAdd;
            $this->last_ticket_added_at = $now;
            $this->save();
            
            return $ticketsToAdd;
        }
        
        return 0;
    }

    /**
     * Получить количество секунд до следующего билета
     * 
     * @return int Количество секунд (0, если билет уже доступен)
     */
    public function getSecondsUntilNextTicket(): int
    {
        $now = now();
        $hoursPerTicket = 24;
        
        // Определяем время отсчета
        $referenceTime = $this->last_ticket_added_at ?? $this->created_at;
        
        if (!$referenceTime) {
            return 0;
        }
        
        // Время следующего билета
        $nextTicketTime = $referenceTime->copy()->addHours($hoursPerTicket);
        
        if ($nextTicketTime->isPast()) {
            return 0;
        }
        
        return max(0, $now->diffInSeconds($nextTicketTime, false));
    }

    /**
     * Поиск пользователя по telegram_id
     */
    public static function findByTelegramId(int $telegramId): ?self
    {
        return static::where('telegram_id', $telegramId)->first();
    }

    /**
     * Создание или обновление пользователя из данных Telegram
     */
    public static function createOrUpdateFromTelegram(array $telegramData, ?int $invitedByTelegramId = null): self
    {
        $telegramId = $telegramData['id'] ?? null;
        
        if (!$telegramId) {
            throw new \InvalidArgumentException('Telegram ID is required');
        }

        $user = static::findByTelegramId($telegramId);
        $isNewUser = !$user;

        if (!$user) {
            $user = new static();
            $user->telegram_id = $telegramId;
        }

        // Обновляем данные из Telegram
        $user->username = $telegramData['username'] ?? null;
        $user->first_name = $telegramData['first_name'] ?? null;
        $user->last_name = $telegramData['last_name'] ?? null;
        $user->language_code = $telegramData['language_code'] ?? 'ru';
        $user->photo_url = $telegramData['photo_url'] ?? null;
        $user->last_active_at = now();

        // Если это новый пользователь - даем 1 билет по умолчанию
        if ($isNewUser) {
            $user->tickets = 1;
            $user->last_ticket_added_at = now(); // Устанавливаем время для отсчета следующих билетов
        }

        // Если это новый пользователь и есть пригласивший - увеличиваем счетчик рефералов
        if ($isNewUser && $invitedByTelegramId) {
            \Log::info('Processing referral for new user', [
                'new_user_telegram_id' => $user->telegram_id,
                'invited_by_telegram_id' => $invitedByTelegramId,
                'step' => 'checking_inviter'
            ]);
            
            $inviter = static::findByTelegramId($invitedByTelegramId);
            
            if ($inviter) {
                // Устанавливаем связь с пригласившим
                $user->invited_by_telegram_user_id = $invitedByTelegramId;
                
                // Сохраняем счетчик рефералов до увеличения
                $referralsCountBefore = $inviter->referrals_count;
                
                // Увеличиваем счетчик рефералов у пригласившего
                $inviter->incrementReferralsCount();
                $inviter->refresh();
                
                \Log::info('Referral successfully registered', [
                    'new_user_telegram_id' => $user->telegram_id,
                    'new_user_id' => $user->id,
                    'inviter_telegram_id' => $invitedByTelegramId,
                    'inviter_user_id' => $inviter->id,
                    'inviter_referrals_count_before' => $referralsCountBefore,
                    'inviter_referrals_count_after' => $inviter->referrals_count,
                    'inviter_username' => $inviter->username,
                    'inviter_full_name' => $inviter->full_name
                ]);
            } else {
                \Log::warning('Inviter not found when processing referral', [
                    'invited_by_telegram_id' => $invitedByTelegramId,
                    'new_user_telegram_id' => $user->telegram_id,
                    'new_user_id' => $user->id,
                    'step' => 'inviter_not_found_in_database'
                ]);
            }
        } elseif ($isNewUser && !$invitedByTelegramId) {
            \Log::info('New user created without referral', [
                'telegram_id' => $user->telegram_id,
                'user_id' => $user->id,
                'username' => $user->username,
                'full_name' => $user->full_name
            ]);
        }

        $user->save();

        // Для существующих пользователей проверяем, нужно ли добавить билеты
        if (!$isNewUser) {
            $user->checkAndAddTicketsIfNeeded();
        }

        return $user;
    }

    /**
     * Scope для поиска по username
     */
    public function scopeByUsername($query, string $username)
    {
        return $query->where('username', $username);
    }

    /**
     * Scope для пользователей с билетами
     */
    public function scopeWithTickets($query, int $minTickets = 1)
    {
        return $query->where('tickets', '>=', $minTickets);
    }

    /**
     * Scope для пользователей с рефералами
     */
    public function scopeWithReferrals($query, int $minReferrals = 1)
    {
        return $query->where('referrals_count', '>=', $minReferrals);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Database\Factories\TelegramUserFactory::new();
    }
}
