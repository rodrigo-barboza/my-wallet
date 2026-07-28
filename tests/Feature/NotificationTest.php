<?php

use App\Models\Card;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\User;
use App\Notifications\InvoiceClosingNotification;
use App\Notifications\InvoiceDueNotification;
use App\Notifications\PurchasePaymentDueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

it('sends invoice closing notification when card notify_closing is true', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create(['user_id' => $user->id, 'notify_closing' => true]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'closing_date' => now(),
        'due_date' => now()->addDays(10),
    ]);

    $this->artisan('notifications:send');

    Notification::assertSentTo($user, InvoiceClosingNotification::class);
});

it('does not send invoice closing notification when card notify_closing is false', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create(['user_id' => $user->id, 'notify_closing' => false]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'closing_date' => now(),
        'due_date' => now()->addDays(10),
    ]);

    $this->artisan('notifications:send');

    Notification::assertNothingSent();
});

it('sends invoice due notification when card notify_due is true', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create(['user_id' => $user->id, 'notify_due' => true]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'closing_date' => now()->subDays(5),
        'due_date' => now(),
    ]);

    $this->artisan('notifications:send');

    Notification::assertSentTo($user, InvoiceDueNotification::class);
});

it('does not send invoice due notification when card notify_due is false', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create(['user_id' => $user->id, 'notify_due' => false]);

    $invoice = Invoice::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'closing_date' => now()->subDays(5),
        'due_date' => now(),
    ]);

    $this->artisan('notifications:send');

    Notification::assertNothingSent();
});

it('sends purchase payment due notification for active recurring purchase with notify_due', function () {
    $user = User::factory()->create();

    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => null,
        'payment_day' => now()->day,
        'notify_due' => true,
        'is_recurring' => true,
        'start_date' => now()->subMonth(),
    ]);

    $this->artisan('notifications:send');

    Notification::assertSentTo($user, PurchasePaymentDueNotification::class);
});

it('does not send purchase notification when already paid this month', function () {
    $user = User::factory()->create();

    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => null,
        'payment_day' => now()->day,
        'notify_due' => true,
        'is_recurring' => true,
        'start_date' => now()->subMonth(),
    ]);

    $purchase->payments()->create([
        'month' => now()->month,
        'year' => now()->year,
        'paid_at' => now(),
    ]);

    $this->artisan('notifications:send');

    Notification::assertNothingSent();
});

it('does not send purchase notification when notify_due is false', function () {
    $user = User::factory()->create();

    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => null,
        'payment_day' => now()->day,
        'notify_due' => false,
        'is_recurring' => true,
        'start_date' => now()->subMonth(),
    ]);

    $this->artisan('notifications:send');

    Notification::assertNothingSent();
});

it('does not send purchase notification for card-linked purchases', function () {
    $user = User::factory()->create();
    $card = Card::factory()->create(['user_id' => $user->id]);

    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'payment_day' => now()->day,
        'notify_due' => true,
        'is_recurring' => true,
        'start_date' => now()->subMonth(),
    ]);

    $this->artisan('notifications:send');

    Notification::assertNothingSent();
});
