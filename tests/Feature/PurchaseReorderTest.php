<?php

declare(strict_types=1);

use App\Models\Card;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

test('user can save purchase order', function () {
    $user = User::factory()->create();

    actingAs($user);

    $order = ['card_1', 'purchase_5', 'card_3'];

    post(route('purchases.reorder'), ['order' => $order])
        ->assertSessionDoesntHaveErrors();

    expect($user->fresh()->purchase_order)->toBe($order);
});

test('order must be an array of strings', function () {
    $user = User::factory()->create();

    actingAs($user);

    post(route('purchases.reorder'), ['order' => [1, 2, 3]])
        ->assertSessionHasErrors('order.0');
});

test('guest cannot reorder', function () {
    post(route('purchases.reorder'), ['order' => []])
        ->assertRedirect(route('login'));
});

test('summary is sorted by saved purchase order', function () {
    $user = User::factory()->create();
    actingAs($user);

    $cardA = Card::factory()->create(['user_id' => $user->id, 'name' => 'Card A']);
    $cardB = Card::factory()->create(['user_id' => $user->id, 'name' => 'Card B']);

    $purchaseOnA = Purchase::factory()->forCard($cardA)->create([
        'user_id' => $user->id,
        'start_date' => now()->startOfMonth(),
    ]);
    $purchaseOnB = Purchase::factory()->forCard($cardB)->create([
        'user_id' => $user->id,
        'start_date' => now()->startOfMonth(),
    ]);
    $individual = Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => null,
        'start_date' => now()->startOfMonth(),
    ]);

    $order = [
        'purchase_'.$individual->id,
        'card_'.$cardB->id,
        'card_'.$cardA->id,
    ];

    $user->update(['purchase_order' => $order]);

    $response = get(route('purchases.index', [
        'month' => now()->month,
        'year' => now()->year,
    ]));

    $response->assertSuccessful();

    $content = $response->content();
    preg_match('/<script data-page="app" type="application\/json">(.+?)<\/script>/s', $content, $matches);
    $pageData = json_decode($matches[1], true);
    $summary = $pageData['props']['summary'];

    expect($summary)->toHaveCount(3);
    expect($summary[0]['items'][0]['id'])->toBe($individual->id);
    expect($summary[1]['items'][0]['id'])->toBe($purchaseOnB->id);
    expect($summary[2]['items'][0]['id'])->toBe($purchaseOnA->id);
});
