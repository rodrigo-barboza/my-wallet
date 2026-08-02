<?php

declare(strict_types=1);

use App\Models\Card;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

it('filters purchases by name when search is provided', function () {
    $user = User::factory()->create();
    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'name' => 'Netflix',
        'start_date' => now()->startOfMonth(),
    ]);

    $this->actingAs($user)
        ->getJson(route('purchases.index', ['search' => 'Netflix']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('summary', 1)
            ->where('summary.0.name', 'Netflix')
        );
});

it('returns all purchases when allMonths is true', function () {
    $user = User::factory()->create();
    $purchase1 = Purchase::factory()->create([
        'user_id' => $user->id,
        'name' => 'Netflix',
        'start_date' => now()->startOfMonth(),
    ]);
    $purchase2 = Purchase::factory()->create([
        'user_id' => $user->id,
        'name' => 'Spotify',
        'start_date' => now()->subMonths(3)->startOfMonth(),
    ]);

    $this->actingAs($user)
        ->getJson(route('purchases.index', ['allMonths' => true]))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('summary', 2)
        );
});

it('combines search and allMonths parameters', function () {
    $user = User::factory()->create();
    $purchase1 = Purchase::factory()->create([
        'user_id' => $user->id,
        'name' => 'Netflix',
        'start_date' => now()->startOfMonth(),
    ]);
    $purchase2 = Purchase::factory()->create([
        'user_id' => $user->id,
        'name' => 'Spotify',
        'start_date' => now()->startOfMonth(),
    ]);

    $this->actingAs($user)
        ->getJson(route('purchases.index', ['allMonths' => true, 'search' => 'Netflix']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('summary', 1)
            ->where('summary.0.name', 'Netflix')
        );
});

it('filters card purchases by name when search is provided', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create(['user_id' => $user->id]);
    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'name' => 'Ifood',
        'start_date' => now()->startOfMonth(),
    ]);

    $this->actingAs($user)
        ->getJson(route('cards.purchases', ['card' => $card->id, 'search' => 'Ifood']))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->has('purchases', 1)
            ->where('purchases.0.name', 'Ifood')
        );
});
