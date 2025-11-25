<?php

namespace Database\Factories;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Channel>
 */
class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $username = $this->faker->unique()->userName();

        return [
            'title' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'username' => $username,
            'url' => "https://t.me/{$username}",
            'avatar' => $this->faker->optional()->imageUrl(),
            'telegram_chat_id' => $this->faker->optional()->randomElement([
                null,
                "@{$username}",
                '-' . $this->faker->numerify('100#########')
            ]),
            'sort_order' => $this->faker->numberBetween(0, 100),
            'is_active' => true,
            'is_required' => true,
        ];
    }

    /**
     * Указать что канал не обязательный
     */
    public function notRequired(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => false,
        ]);
    }

    /**
     * Указать что канал не активен
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

