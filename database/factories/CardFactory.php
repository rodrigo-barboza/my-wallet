<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Card>
 */
class CardFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement(['Nubank', 'Itaú Platinum', 'Visa Gold', 'Mastercard Black', 'Inter']),
            'color' => fake()->randomElement(['#8B5CF6', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#EC4899']),
            'closing_day' => fake()->numberBetween(1, 28),
            'due_day' => fake()->numberBetween(1, 28),
            'notify_closing' => true,
            'notify_due' => true,
        ];
    }
}
