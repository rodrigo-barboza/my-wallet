<?php

use App\Models\Card;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('auto-creates invoice when purchase is created with card', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $card = Card::factory()->create([
        'user_id' => $user->id,
        'closing_day' => 5,
        'due_day' => 10,
    ]);

    post(route('purchases.store'), [
        'name' => 'Compra teste',
        'type' => 'credit_card',
        'card_id' => $card->id,
        'amount' => 100,
        'is_recurring' => false,
        'start_date' => '2024-07-15',
    ])->assertRedirect();

    assertDatabaseHas(Invoice::class, [
        'user_id' => $user->id,
        'card_id' => $card->id,
        'month' => 7,
        'year' => 2024,
    ]);
});

it('sets invoice status to fechada when created after closing date', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $card = Card::factory()->create([
        'user_id' => $user->id,
        'closing_day' => 5,
        'due_day' => 10,
    ]);

    Carbon::setTestNow(Carbon::create(2024, 7, 7));

    post(route('purchases.store'), [
        'name' => 'Compra teste',
        'type' => 'credit_card',
        'card_id' => $card->id,
        'amount' => 100,
        'is_recurring' => false,
        'start_date' => '2024-07-01',
    ])->assertRedirect();

    $invoice = Invoice::where('card_id', $card->id)->first();
    expect($invoice->status)->toBe('fechada');

    Carbon::setTestNow();
});

it('can mark invoice as paid', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $card = Card::factory()->create(['user_id' => $user->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'status' => 'fechada',
    ]);

    patch(route('invoices.mark-as-paid', $invoice))->assertRedirect();

    $invoice->refresh();
    expect($invoice->status)->toBe('paga');
});

it('user cannot mark other users invoice as paid', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $card = Card::factory()->create(['user_id' => $otherUser->id]);

    $invoice = Invoice::factory()->create([
        'user_id' => $otherUser->id,
        'card_id' => $card->id,
    ]);

    patch(route('invoices.mark-as-paid', $invoice))->assertForbidden();
});
