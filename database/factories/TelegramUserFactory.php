<?php

namespace Database\Factories;

use App\Models\TelegramUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TelegramUser>
 */
class TelegramUserFactory extends Factory
{
    protected $model = TelegramUser::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'telegram_id' => $this->faker->unique()->numerify('##########'),
            'username' => $this->faker->optional(0.7)->userName(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->optional(0.5)->lastName(),
            'language_code' => $this->faker->randomElement(['ru', 'en', 'uk', 'kz']),
            'photo_url' => $this->faker->optional(0.6)->imageUrl(200, 200, 'people'),
            'tickets' => $this->faker->numberBetween(0, 100),
            'referrals_count' => $this->faker->numberBetween(0, 50),
            'invited_by_telegram_user_id' => null,
            'last_active_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 week', 'now'),
            'metadata' => null,
        ];
    }

    /**
     * Пользователь с билетами
     */
    public function withTickets(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'tickets' => $count,
        ]);
    }

    /**
     * Пользователь с рефералами
     */
    public function withReferrals(int $count): static
    {
        return $this->state(fn (array $attributes) => [
            'referrals_count' => $count,
        ]);
    }

    /**
     * Пользователь с пригласившим
     */
    public function invitedBy(TelegramUser $inviter): static
    {
        return $this->state(fn (array $attributes) => [
            'invited_by_telegram_user_id' => $inviter->telegram_id,
        ]);
    }
}
