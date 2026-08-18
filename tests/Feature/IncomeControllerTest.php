<?php

use App\Models\Income;
use App\Models\IncomeMonth;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('creates an income month when it does not exist yet', function () {
    $user = User::factory()->create();
    $income = Income::create(['user_id' => $user->id, 'name' => 'Salário']);

    actingAs($user)
        ->post(route('incomes.store-month', $income), [
            'month' => 3,
            'year' => 2026,
            'amount' => 0,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('income_months', [
        'income_id' => $income->id,
        'month' => 3,
        'year' => 2026,
        'amount' => 0.00,
    ]);
});

it('updates an existing income month instead of duplicating it', function () {
    $user = User::factory()->create();
    $income = Income::create(['user_id' => $user->id, 'name' => 'Salário']);
    IncomeMonth::create([
        'income_id' => $income->id,
        'month' => 3,
        'year' => 2026,
        'amount' => 100,
    ]);

    actingAs($user)
        ->post(route('incomes.store-month', $income), [
            'month' => 3,
            'year' => 2026,
            'amount' => 50,
        ])
        ->assertRedirect();

    $this->assertDatabaseCount('income_months', 1);
    $this->assertDatabaseHas('income_months', [
        'income_id' => $income->id,
        'month' => 3,
        'year' => 2026,
        'amount' => 50.00,
    ]);
});

it('does not allow creating an income month for another user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $income = Income::create(['user_id' => $owner->id, 'name' => 'Salário']);

    actingAs($other)
        ->post(route('incomes.store-month', $income), [
            'month' => 3,
            'year' => 2026,
            'amount' => 0,
        ])
        ->assertForbidden();
});

it('requires amount to be numeric and not negative', function () {
    $user = User::factory()->create();
    $income = Income::create(['user_id' => $user->id, 'name' => 'Salário']);

    actingAs($user)
        ->post(route('incomes.store-month', $income), [
            'month' => 3,
            'year' => 2026,
            'amount' => -5,
        ])
        ->assertSessionHasErrors('amount');

    $this->assertDatabaseCount('income_months', 0);
});
