<?php

use App\Models\Income;
use App\Models\IncomeMonth;
use App\Models\User;

use function Pest\Laravel\actingAs;

it('updates wallet balance manually', function () {
    $user = User::factory()->create(['wallet_balance' => 0]);

    actingAs($user)->patch(route('wallet.update'), ['balance' => 500])->assertRedirect();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'wallet_balance' => 500.00]);
});

it('allows a negative wallet balance', function () {
    $user = User::factory()->create(['wallet_balance' => 0]);

    actingAs($user)->patch(route('wallet.update'), ['balance' => -50])->assertRedirect();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'wallet_balance' => -50.00]);
});

it('marks an income month as received adding its amount to the wallet', function () {
    $user = User::factory()->create(['wallet_balance' => 0]);
    $income = Income::create(['user_id' => $user->id, 'name' => 'Salário']);
    $month = IncomeMonth::create(['income_id' => $income->id, 'month' => 3, 'year' => 2026, 'amount' => 1000]);

    actingAs($user)->post(route('incomes.receive', $month))->assertRedirect();

    $month->refresh();
    $this->assertTrue($month->isReceived());
    $this->assertDatabaseHas('users', ['id' => $user->id, 'wallet_balance' => 1000.00]);
});

it('unmarks an income month subtracting its amount from the wallet', function () {
    $user = User::factory()->create(['wallet_balance' => 1000]);
    $income = Income::create(['user_id' => $user->id, 'name' => 'Salário']);
    $month = IncomeMonth::create(['income_id' => $income->id, 'month' => 3, 'year' => 2026, 'amount' => 1000, 'received_at' => now()]);

    actingAs($user)->delete(route('incomes.unreceive', $month))->assertRedirect();

    $month->refresh();
    $this->assertFalse($month->isReceived());
    $this->assertDatabaseHas('users', ['id' => $user->id, 'wallet_balance' => 0.00]);
});

it('adjusts the wallet when editing the value of a received month', function () {
    $user = User::factory()->create(['wallet_balance' => 500]);
    $income = Income::create(['user_id' => $user->id, 'name' => 'Salário']);
    $month = IncomeMonth::create(['income_id' => $income->id, 'month' => 3, 'year' => 2026, 'amount' => 500, 'received_at' => now()]);

    actingAs($user)->patch(route('incomes.update-month', $month), ['amount' => 800])->assertRedirect();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'wallet_balance' => 800.00]);
});

it('does not allow receiving an income month of another user', function () {
    $owner = User::factory()->create(['wallet_balance' => 0]);
    $other = User::factory()->create();
    $income = Income::create(['user_id' => $owner->id, 'name' => 'Salário']);
    $month = IncomeMonth::create(['income_id' => $income->id, 'month' => 3, 'year' => 2026, 'amount' => 1000]);

    actingAs($other)->post(route('incomes.receive', $month))->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $owner->id, 'wallet_balance' => 0.00]);
});

it('creating a new value on an existing received month adjusts the wallet by the difference', function () {
    $user = User::factory()->create(['wallet_balance' => 300]);
    $income = Income::create(['user_id' => $user->id, 'name' => 'Salário']);
    $month = IncomeMonth::create(['income_id' => $income->id, 'month' => 3, 'year' => 2026, 'amount' => 300, 'received_at' => now()]);

    actingAs($user)->post(route('incomes.store-month', $income), [
        'month' => 3,
        'year' => 2026,
        'amount' => 450,
    ])->assertRedirect();

    $this->assertDatabaseHas('users', ['id' => $user->id, 'wallet_balance' => 450.00]);
});
