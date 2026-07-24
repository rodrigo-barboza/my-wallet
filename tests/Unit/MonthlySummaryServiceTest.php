<?php

declare(strict_types=1);

use App\Enums\PurchaseType;
use App\Models\Card;
use App\Models\Income;
use App\Models\IncomeMonth;
use App\Models\Purchase;
use App\Models\User;
use App\Services\MonthlySummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class);
uses(RefreshDatabase::class);

test('buildForMonth returns card summary and individual purchases', function () {
    $user = User::factory()->create();

    $card = Card::factory()->create(['user_id' => $user->id]);
    $this->travelTo('2026-07-15');
    $activeMonth = 7;
    $activeYear = 2026;

    Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'type' => PurchaseType::CreditCard->value,
        'amount' => 300.00,
        'installments_total' => 3,
        'start_date' => '2026-07-01',
        'is_recurring' => false,
    ]);

    Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'type' => PurchaseType::CreditCard->value,
        'amount' => 200.00,
        'start_date' => '2026-07-01',
        'is_recurring' => false,
    ]);

    Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => null,
        'type' => PurchaseType::Bill->value,
        'amount' => 50.00,
        'start_date' => '2026-07-01',
        'payment_day' => 15,
        'is_recurring' => false,
    ]);

    $service = new MonthlySummaryService;
    $result = $service->buildForMonth($user, $activeYear, $activeMonth);

    expect($result)->toHaveCount(2);

    $cardItem = $result->first(fn ($i) => $i['name'] === $card->name);
    expect($cardItem)->not->toBeNull();
    expect($cardItem['total'])->toEqual(300.0); // 300/3 + 200 = 300

    $individualItem = $result->first(fn ($i) => $i['name'] !== $card->name);
    expect($individualItem)->not->toBeNull();
    expect($individualItem['total'])->toEqual(50.0);
});

test('buildForMonth respects purchase active window', function () {
    $user = User::factory()->create();
    $this->travelTo('2026-07-15');

    Purchase::factory()->create([
        'user_id' => $user->id,
        'type' => PurchaseType::Bill->value,
        'amount' => 100.00,
        'start_date' => '2026-08-01',
        'payment_day' => 10,
        'is_recurring' => false,
    ]);

    $service = new MonthlySummaryService;
    $result = $service->buildForMonth($user, 2026, 7);

    expect($result)->toHaveCount(0);
});

test('incomeForMonth sums income_months for given month', function () {
    $user = User::factory()->create();

    $income = Income::create(['user_id' => $user->id, 'name' => 'Salário']);
    IncomeMonth::create(['income_id' => $income->id, 'month' => 7, 'year' => 2026, 'amount' => 5000.00]);
    IncomeMonth::create(['income_id' => $income->id, 'month' => 8, 'year' => 2026, 'amount' => 5000.00]);

    $service = new MonthlySummaryService;

    expect($service->incomeForMonth($user, 2026, 7))->toEqual(5000.0);
    expect($service->incomeForMonth($user, 2026, 9))->toEqual(0.0);
});

test('expensesByType groups purchases by type in given month', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create(['user_id' => $user->id]);
    $this->travelTo('2026-07-15');

    Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'type' => PurchaseType::CreditCard->value,
        'amount' => 300.00,
        'start_date' => '2026-07-01',
        'is_recurring' => false,
    ]);

    Purchase::factory()->create([
        'user_id' => $user->id,
        'type' => PurchaseType::Financing->value,
        'amount' => 800.00,
        'start_date' => '2026-07-01',
        'payment_day' => 10,
        'is_recurring' => false,
    ]);

    $service = new MonthlySummaryService;
    $result = $service->expensesByType($user, 2026, 7);

    expect($result)->toHaveCount(2);

    $credit = $result->first(fn ($d) => $d['type'] === 'credit_card');
    expect($credit['total'])->toEqual(300.0);
    expect($credit['label'])->toEqual('Cartão de crédito');

    $financing = $result->first(fn ($d) => $d['type'] === 'financing');
    expect($financing['total'])->toEqual(800.0);
    expect($financing['label'])->toEqual('Financiamento');
});

test('upcomingPayments returns next unpaid payments sorted by due date', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create([
        'user_id' => $user->id,
        'due_day' => 15,
    ]);
    $this->travelTo('2026-07-10');

    Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'type' => PurchaseType::CreditCard->value,
        'amount' => 200.00,
        'start_date' => '2026-07-01',
        'is_recurring' => false,
    ]);

    Purchase::factory()->create([
        'user_id' => $user->id,
        'type' => PurchaseType::Bill->value,
        'amount' => 90.00,
        'start_date' => '2026-07-01',
        'payment_day' => 20,
        'is_recurring' => false,
    ]);

    $service = new MonthlySummaryService;
    $result = $service->upcomingPayments($user, 2026, 7, 7);

    expect($result)->toHaveCount(2);
    expect($result[0]['amount'])->toEqual(200.0); // due_day 15 is sooner than payment_day 20
    expect($result[1]['amount'])->toEqual(90.0);
    expect($result[0]['dueDate'])->toBeString();
});

test('upcomingPayments skips paid items in current month', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create([
        'user_id' => $user->id,
        'due_day' => 10,
    ]);
    $this->travelTo('2026-07-05');

    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'type' => PurchaseType::CreditCard->value,
        'amount' => 200.00,
        'start_date' => '2026-07-01',
        'is_recurring' => false,
    ]);

    $purchase->payments()->create([
        'month' => 7,
        'year' => 2026,
        'paid_at' => now(),
    ]);

    $service = new MonthlySummaryService;
    $result = $service->upcomingPayments($user, 2026, 7, 7);

    // Item was paid, so it should not appear
    $cardNames = collect($result)->pluck('name');
    expect($cardNames->contains($card->name))->toBeFalse();
});

test('upcomingPayments limits results', function () {
    $user = User::factory()->create();
    $this->travelTo('2026-07-15');

    for ($i = 0; $i < 10; $i++) {
        Purchase::factory()->create([
            'user_id' => $user->id,
            'type' => PurchaseType::Bill->value,
            'amount' => 50.00,
            'start_date' => '2026-07-01',
            'payment_day' => 16 + $i,
            'is_recurring' => false,
        ]);
    }

    $service = new MonthlySummaryService;
    $result = $service->upcomingPayments($user, 2026, 7, 3);

    expect($result)->toHaveCount(3);
});
