<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role_id' => 1, // По умолчанию USER
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Создать пользователя с ролью администратора
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 900, // ADMIN
        ]);
    }

    /**
     * Создать пользователя с ролью модератора
     */
    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 500, // MODERATOR
        ]);
    }

    /**
     * Создать пользователя с ролью разработчика
     */
    public function developer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => 999, // DEVELOPER
        ]);
    }
}
