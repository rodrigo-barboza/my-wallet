<?php

use App\Enums\PurchaseType;
use App\Models\Card;
use App\Models\Income;
use App\Models\IncomeMonth;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
    $this->travelTo('2026-07-15');
});

it('renders dashboard with window of 6 months from current month', function () {
    $response = get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('window', 6)
        ->where('window.0.month', 7)
        ->where('window.0.isCurrent', true)
        ->where('window.0.isHighlighted', true)
        ->where('window.5.month', 12)
    );
});

it('renders dashboard with custom start month', function () {
    $response = get(route('dashboard', ['month' => 9, 'year' => 2026]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('window', 6)
        ->where('window.0.month', 9)
        ->where('window.1.month', 10)
    );
});

it('allows navigating to past months', function () {
    $response = get(route('dashboard', ['month' => 5, 'year' => 2026]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('window.0.month', 5)
    );
});

it('includes matrix with card rows', function () {
    $card = Card::factory()->create(['user_id' => $this->user->id]);

    Purchase::factory()->create([
        'user_id' => $this->user->id,
        'card_id' => $card->id,
        'type' => PurchaseType::CreditCard->value,
        'amount' => 400.00,
        'installments_total' => 4,
        'start_date' => '2026-07-01',
        'is_recurring' => false,
    ]);

    $response = get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('matrix', 1)
        ->where('matrix.0.id', "card_{$card->id}")
        ->where('matrix.0.type', 'credit_card')
        ->where('matrix.0.totals.0', 100)
    );
});

it('includes matrix with individual purchase rows', function () {
    Purchase::factory()->create([
        'user_id' => $this->user->id,
        'card_id' => null,
        'type' => PurchaseType::Bill->value,
        'amount' => 50.00,
        'start_date' => '2026-07-01',
        'payment_day' => 15,
        'is_recurring' => false,
    ]);

    $response = get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('matrix', 1)
        ->where('matrix.0.type', 'bill')
        ->where('matrix.0.totals.0', 50)
    );
});

it('includes monthly summary for each month in window', function () {
    Income::create(['user_id' => $this->user->id, 'name' => 'Salário']);
    IncomeMonth::create(['income_id' => 1, 'month' => 7, 'year' => 2026, 'amount' => 5000.00]);

    Purchase::factory()->create([
        'user_id' => $this->user->id,
        'type' => PurchaseType::Bill->value,
        'amount' => 100.00,
        'start_date' => '2026-07-01',
        'payment_day' => 15,
        'is_recurring' => false,
    ]);

    $response = get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('monthlySummary', 6)
        ->where('monthlySummary.0.income', 5000)
        ->where('monthlySummary.0.expenses', 100)
        ->where('monthlySummary.0.balance', 4900)
    );
});

it('includes category distribution for highlighted month', function () {
    Purchase::factory()->create([
        'user_id' => $this->user->id,
        'type' => PurchaseType::Financing->value,
        'amount' => 800.00,
        'start_date' => '2026-07-01',
        'payment_day' => 10,
        'is_recurring' => false,
    ]);

    $response = get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('categoryDistribution', 1)
        ->where('categoryDistribution.0.type', 'financing')
        ->where('categoryDistribution.0.total', 800)
    );
});

it('includes upcoming payments sorted by due date', function () {
    Purchase::factory()->create([
        'user_id' => $this->user->id,
        'type' => PurchaseType::Bill->value,
        'amount' => 50.00,
        'start_date' => '2026-07-01',
        'payment_day' => 20,
        'is_recurring' => false,
    ]);

    Purchase::factory()->create([
        'user_id' => $this->user->id,
        'type' => PurchaseType::Bill->value,
        'amount' => 60.00,
        'start_date' => '2026-07-01',
        'payment_day' => 16,
        'is_recurring' => false,
    ]);

    $response = get(route('dashboard'));

    $response->assertInertia(fn ($page) => $page
        ->has('upcomingPayments', 2)
        ->where('upcomingPayments.0.amount', 60)
        ->where('upcomingPayments.1.amount', 50)
    );
});
