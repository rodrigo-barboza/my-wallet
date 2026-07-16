<?php

declare(strict_types=1);

use App\Enums\InvoiceStatus;
use App\Models\Card;
use App\Models\Invoice;
use App\Models\User;

it('can be created with valid attributes', function () {
    $invoice = Invoice::factory()->create();

    expect($invoice)->toBeInstanceOf(Invoice::class)
        ->and($invoice->user_id)->toBeInt()
        ->and($invoice->card_id)->toBeInt()
        ->and($invoice->month)->toBeInt()
        ->and($invoice->year)->toBeInt()
        ->and($invoice->closing_date)->not->toBeNull()
        ->and($invoice->due_date)->not->toBeNull();
});

it('casts month and year to integer', function () {
    $invoice = Invoice::factory()->create(['month' => 7, 'year' => 2026]);

    expect($invoice->month)->toBeInt()->toBe(7)
        ->and($invoice->year)->toBeInt()->toBe(2026);
});

it('casts closing_date and due_date to date', function () {
    $invoice = Invoice::factory()->create();

    expect($invoice->closing_date)->toBeInstanceOf(Carbon\Carbon::class)
        ->and($invoice->due_date)->toBeInstanceOf(Carbon\Carbon::class);
});

it('belongs to a user', function () {
    $user = User::factory()->create();
    $invoice = Invoice::factory()->for($user)->create();

    expect($invoice->user)->toBeInstanceOf(User::class)
        ->and($invoice->user->id)->toBe($user->id);
});

it('belongs to a card', function () {
    $card = Card::factory()->create();
    $invoice = Invoice::factory()->for($card)->create();

    expect($invoice->card)->toBeInstanceOf(Card::class)
        ->and($invoice->card->id)->toBe($card->id);
});

it('returns paga status when invoice is paid', function () {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Paga]);

    expect($invoice->status)->toBe(InvoiceStatus::Paga->value);
});

it('returns atrasada status when due_date is past', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::Aberta,
        'due_date' => now()->subDay(),
        'closing_date' => now()->subDays(10),
    ]);

    expect($invoice->status)->toBe(InvoiceStatus::Atrasada->value);
});

it('returns fechada status when closing_date is past but due_date is future', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::Aberta,
        'closing_date' => now()->subDay(),
        'due_date' => now()->addDays(5),
    ]);

    expect($invoice->status)->toBe(InvoiceStatus::Fechada->value);
});

it('returns aberta status when both dates are future', function () {
    $invoice = Invoice::factory()->create([
        'status' => InvoiceStatus::Aberta,
        'closing_date' => now()->addDays(10),
        'due_date' => now()->addDays(20),
    ]);

    expect($invoice->status)->toBe(InvoiceStatus::Aberta->value);
});
