<?php

declare(strict_types=1);

use App\Models\Card;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(RefreshDatabase::class);

it('sets correct closing and due dates for closing before due scenario', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create([
        'user_id' => $user->id,
        'closing_day' => 5,
        'due_day' => 10,
    ]);

    actingAs($user);

    post(route('purchases.store'), [
        'name' => 'Compra Teste',
        'type' => 'credit_card',
        'card_id' => $card->id,
        'amount' => 150.00,
        'is_recurring' => false,
        'start_date' => '2026-07-15',
    ]);

    $invoice = Invoice::where('card_id', $card->id)
        ->where('month', 7)
        ->where('year', 2026)
        ->first();

    expect($invoice)->not->toBeNull();
    expect($invoice->closing_date->format('Y-m-d'))->toBe('2026-08-05');
    expect($invoice->due_date->format('Y-m-d'))->toBe('2026-08-10');
});

it('sets correct closing and due dates for closing after due scenario', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create([
        'user_id' => $user->id,
        'closing_day' => 26,
        'due_day' => 5,
    ]);

    actingAs($user);

    post(route('purchases.store'), [
        'name' => 'Compra Teste',
        'type' => 'credit_card',
        'card_id' => $card->id,
        'amount' => 200.00,
        'is_recurring' => false,
        'start_date' => '2026-07-15',
    ]);

    $invoice = Invoice::where('card_id', $card->id)
        ->where('month', 7)
        ->where('year', 2026)
        ->first();

    expect($invoice)->not->toBeNull();
    expect($invoice->closing_date->format('Y-m-d'))->toBe('2026-07-26');
    expect($invoice->due_date->format('Y-m-d'))->toBe('2026-08-05');
});

it('does not mark invoice as overdue before due date passes', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create([
        'user_id' => $user->id,
        'closing_day' => 5,
        'due_day' => 10,
    ]);

    actingAs($user);

    post(route('purchases.store'), [
        'name' => 'Compra Teste',
        'type' => 'credit_card',
        'card_id' => $card->id,
        'amount' => 150.00,
        'is_recurring' => false,
        'start_date' => '2026-07-15',
    ]);

    $invoice = Invoice::where('card_id', $card->id)
        ->where('month', 7)
        ->where('year', 2026)
        ->first();

    Carbon::setTestNow(Carbon::parse('2026-07-11'));
    expect($invoice->status)->toBe('aberta');

    Carbon::setTestNow(Carbon::parse('2026-08-04'));
    expect($invoice->status)->toBe('aberta');

    Carbon::setTestNow(Carbon::parse('2026-08-10'));
    expect($invoice->status)->toBe('atrasada');

    Carbon::setTestNow(Carbon::parse('2026-08-11'));
    expect($invoice->status)->toBe('atrasada');

    Carbon::setTestNow();
});

it('marks invoice as overdue only after due date for closing > due scenario', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create([
        'user_id' => $user->id,
        'closing_day' => 26,
        'due_day' => 5,
    ]);

    actingAs($user);

    post(route('purchases.store'), [
        'name' => 'Compra Teste',
        'type' => 'credit_card',
        'card_id' => $card->id,
        'amount' => 200.00,
        'is_recurring' => false,
        'start_date' => '2026-07-15',
    ]);

    $invoice = Invoice::where('card_id', $card->id)
        ->where('month', 7)
        ->where('year', 2026)
        ->first();

    Carbon::setTestNow(Carbon::parse('2026-07-20'));
    expect($invoice->status)->toBe('aberta');

    Carbon::setTestNow(Carbon::parse('2026-07-26'));
    expect($invoice->status)->toBe('fechada');

    Carbon::setTestNow(Carbon::parse('2026-08-04'));
    expect($invoice->status)->toBe('fechada');

    Carbon::setTestNow(Carbon::parse('2026-08-05'));
    expect($invoice->status)->toBe('atrasada');

    Carbon::setTestNow();
});
