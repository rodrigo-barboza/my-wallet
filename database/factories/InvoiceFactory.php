<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $month = (int) now()->format('m');
        $year = (int) now()->format('Y');

        return [
            'user_id' => User::factory(),
            'card_id' => Card::factory(),
            'month' => $month,
            'year' => $year,
            'status' => InvoiceStatus::Aberta,
            'closing_date' => now()->addDays(5),
            'due_date' => now()->addDays(15),
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn () => [
            'user_id' => $user->id,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Paga,
        ]);
    }

    public function closed(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Aberta,
            'closing_date' => now()->subDay(),
            'due_date' => now()->addDays(10),
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Aberta,
            'closing_date' => now()->subDays(10),
            'due_date' => now()->subDay(),
        ]);
    }
}
