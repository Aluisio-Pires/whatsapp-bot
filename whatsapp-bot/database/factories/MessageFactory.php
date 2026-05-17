<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'external_id' => fake()->unique()->uuid(),
            'role' => 'user',
            'content' => fake()->sentence(),
            'metadata' => [],
        ];
    }

    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }

    public function assistant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'assistant',
            'external_id' => null,
        ]);
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'system',
            'external_id' => null,
        ]);
    }
}
