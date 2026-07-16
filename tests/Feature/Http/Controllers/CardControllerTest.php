<?php

use App\Models\Card;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

it('authenticated users can view cards page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = get(route('cards.index'));

    $response->assertSuccessful();
});

it('guests cannot view cards page', function () {
    $response = get(route('cards.index'));

    $response->assertRedirect(route('login'));
});

it('user can create a card with valid data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = post(route('cards.store'), [
        'name' => 'Nubank',
        'color' => '#8B5CF6',
        'closing_day' => 15,
        'due_day' => 25,
        'notify_closing' => true,
        'notify_due' => true,
    ]);

    $response->assertRedirect(route('cards.index'));
    assertDatabaseHas(Card::class, [
        'user_id' => $user->id,
        'name' => 'Nubank',
        'color' => '#8B5CF6',
        'closing_day' => 15,
        'due_day' => 25,
    ]);
});

it('name is required', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = post(route('cards.store'), [
        'name' => '',
        'color' => '#8B5CF6',
        'closing_day' => 15,
        'due_day' => 25,
    ]);

    $response->assertSessionHasErrors('name');
});

it('color is required', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = post(route('cards.store'), [
        'name' => 'Nubank',
        'color' => '',
        'closing_day' => 15,
        'due_day' => 25,
    ]);

    $response->assertSessionHasErrors('color');
});

it('color must be valid hex', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = post(route('cards.store'), [
        'name' => 'Nubank',
        'color' => 'invalid-color',
        'closing_day' => 15,
        'due_day' => 25,
    ]);

    $response->assertSessionHasErrors('color');
});

it('closing_day must be between 1 and 31', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = post(route('cards.store'), [
        'name' => 'Nubank',
        'color' => '#8B5CF6',
        'closing_day' => 32,
        'due_day' => 25,
    ]);

    $response->assertSessionHasErrors('closing_day');
});

it('due_day must be between 1 and 31', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = post(route('cards.store'), [
        'name' => 'Nubank',
        'color' => '#8B5CF6',
        'closing_day' => 15,
        'due_day' => 0,
    ]);

    $response->assertSessionHasErrors('due_day');
});

it('notifications are enabled by default', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    post(route('cards.store'), [
        'name' => 'Nubank',
        'color' => '#8B5CF6',
        'closing_day' => 15,
        'due_day' => 25,
    ]);

    assertDatabaseHas(Card::class, [
        'user_id' => $user->id,
        'notify_closing' => true,
        'notify_due' => true,
    ]);
});

it('user can toggle notification settings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $card = Card::factory()->create([
        'user_id' => $user->id,
        'notify_closing' => true,
        'notify_due' => true,
    ]);

    put(route('cards.update', $card), [
        'name' => $card->name,
        'color' => $card->color,
        'closing_day' => $card->closing_day,
        'due_day' => $card->due_day,
        'notify_closing' => false,
        'notify_due' => false,
    ]);

    assertDatabaseHas(Card::class, [
        'id' => $card->id,
        'notify_closing' => false,
        'notify_due' => false,
    ]);
});

it('user can update their card', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $card = Card::factory()->create(['user_id' => $user->id]);

    $response = put(route('cards.update', $card), [
        'name' => 'Nubank Atualizado',
        'color' => '#10B981',
        'closing_day' => 20,
        'due_day' => 30,
        'notify_closing' => false,
        'notify_due' => false,
    ]);

    $response->assertRedirect(route('cards.index'));
    assertDatabaseHas(Card::class, [
        'id' => $card->id,
        'name' => 'Nubank Atualizado',
        'color' => '#10B981',
    ]);
});

it('user cannot update another users card', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $otherUser = User::factory()->create();
    $card = Card::factory()->create(['user_id' => $otherUser->id]);

    $response = put(route('cards.update', $card), [
        'name' => 'Hacked',
        'color' => '#FF0000',
        'closing_day' => 1,
        'due_day' => 1,
    ]);

    $response->assertStatus(403);
});

it('user can delete their card', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $card = Card::factory()->create(['user_id' => $user->id]);

    $response = delete(route('cards.destroy', $card));

    $response->assertRedirect(route('cards.index'));
    assertDatabaseMissing(Card::class, ['id' => $card->id]);
});

it('user cannot delete another users card', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $otherUser = User::factory()->create();
    $card = Card::factory()->create(['user_id' => $otherUser->id]);

    $response = delete(route('cards.destroy', $card));

    $response->assertStatus(403);
});

it('user can bulk delete cards', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cards = Card::factory()->count(3)->create(['user_id' => $user->id]);

    $response = post(route('cards.bulk-destroy'), [
        'ids' => $cards->pluck('id'),
    ]);

    $response->assertRedirect(route('cards.index'));
    foreach ($cards as $card) {
        assertDatabaseMissing(Card::class, ['id' => $card->id]);
    }
});

it('user cannot bulk delete another users cards', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $otherUser = User::factory()->create();
    $cards = Card::factory()->count(2)->create(['user_id' => $otherUser->id]);

    $response = post(route('cards.bulk-destroy'), [
        'ids' => $cards->pluck('id'),
    ]);

    $response->assertStatus(403);
});
