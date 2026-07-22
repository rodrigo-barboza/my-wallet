<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PurchaseType;
use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'card_id' => null,
            'name' => fake()->words(2, true),
            'type' => fake()->randomElement(PurchaseType::cases()),
            'payment_day' => fake()->numberBetween(1, 28),
            'is_recurring' => fake()->boolean(),
            'amount' => fake()->randomFloat(2, 10, 1000),
            'installments_total' => null,
            'start_date' => fake()->dateTimeThisYear(),
            'notes' => null,
            'notify_due' => false,
            'status' => 'aberta',
        ];
    }

    public function forCard(Card $card): static
    {
        return $this->state(fn () => [
            'card_id' => $card->id,
            'payment_day' => null,
        ]);
    }

    public function installments(int $total): static
    {
        return $this->state(fn () => [
            'installments_total' => $total,
            'is_recurring' => false,
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn () => [
            'installments_total' => null,
            'is_recurring' => true,
        ]);
    }
}
