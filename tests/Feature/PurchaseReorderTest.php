<?php

declare(strict_types=1);

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

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
