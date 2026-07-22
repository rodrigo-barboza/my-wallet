<?php

use App\Enums\PurchaseType;
use App\Models\Card;
use App\Models\Invoice;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

it('user can view purchases list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Purchase::factory()->count(3)->create(['user_id' => $user->id]);

    $response = get(route('purchases.index'));

    $response->assertSuccessful();
});

it('user can create purchase', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $data = [
        'name' => 'Netflix',
        'type' => PurchaseType::Bill->value,
        'payment_day' => 15,
        'is_recurring' => true,
        'amount' => 39.90,
        'start_date' => '2024-07-01',
    ];

    post(route('purchases.store'), $data)->assertRedirect();

    assertDatabaseHas(Purchase::class, [
        'user_id' => $user->id,
        'name' => 'Netflix',
        'type' => PurchaseType::Bill->value,
    ]);
});

it('user can create purchase with card', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $card = Card::factory()->create(['user_id' => $user->id]);

    $data = [
        'name' => 'Compra no cartão',
        'type' => PurchaseType::CreditCard->value,
        'card_id' => $card->id,
        'amount' => 150.00,
        'installments_total' => 3,
        'start_date' => '2024-07-01',
        'is_recurring' => false,
    ];

    post(route('purchases.store'), $data)->assertRedirect();

    assertDatabaseHas(Purchase::class, [
        'user_id' => $user->id,
        'card_id' => $card->id,
        'name' => 'Compra no cartão',
    ]);
});

it('user can update purchase', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchase = Purchase::factory()->create(['user_id' => $user->id]);

    put(route('purchases.update', $purchase), [
        'name' => 'Netflix Premium',
        'type' => $purchase->type->value,
        'payment_day' => $purchase->payment_day,
        'is_recurring' => $purchase->is_recurring,
        'amount' => 55.90,
        'start_date' => $purchase->start_date->format('Y-m-d'),
    ])->assertRedirect();

    assertDatabaseHas(Purchase::class, [
        'id' => $purchase->id,
        'name' => 'Netflix Premium',
    ]);
});

it('user can delete purchase', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchase = Purchase::factory()->create(['user_id' => $user->id]);

    delete(route('purchases.destroy', $purchase))->assertRedirect();

    assertDatabaseMissing(Purchase::class, ['id' => $purchase->id]);
});

it('user cannot view other users purchases', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $purchase = Purchase::factory()->create(['user_id' => $otherUser->id]);

    get(route('purchases.show', $purchase))->assertStatus(403);
});

it('user cannot update other users purchases', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $purchase = Purchase::factory()->create(['user_id' => $otherUser->id]);

    put(route('purchases.update', $purchase), [
        'name' => 'Hacked',
        'type' => $purchase->type->value,
        'is_recurring' => $purchase->is_recurring,
        'amount' => $purchase->amount,
        'start_date' => $purchase->start_date->format('Y-m-d'),
    ])->assertStatus(403);
});

it('user cannot delete other users purchases', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $purchase = Purchase::factory()->create(['user_id' => $otherUser->id]);

    delete(route('purchases.destroy', $purchase))->assertStatus(403);
});

it('requires name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    post(route('purchases.store'), [
        'type' => PurchaseType::Bill->value,
        'payment_day' => 10,
        'is_recurring' => true,
        'amount' => 100,
        'start_date' => '2024-07-01',
    ])->assertSessionHasErrors('name');
});

it('requires type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    post(route('purchases.store'), [
        'name' => 'Test',
        'payment_day' => 10,
        'is_recurring' => true,
        'amount' => 100,
        'start_date' => '2024-07-01',
    ])->assertSessionHasErrors('type');
});

it('requires amount', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    post(route('purchases.store'), [
        'name' => 'Test',
        'type' => PurchaseType::Bill->value,
        'payment_day' => 10,
        'is_recurring' => true,
        'start_date' => '2024-07-01',
    ])->assertSessionHasErrors('amount');
});

it('requires start_date', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    post(route('purchases.store'), [
        'name' => 'Test',
        'type' => PurchaseType::Bill->value,
        'payment_day' => 10,
        'is_recurring' => true,
        'amount' => 100,
    ])->assertSessionHasErrors('start_date');
});

it('correctly calculates active installment in month', function () {
    $purchase = Purchase::factory()->create([
        'start_date' => '2024-07-01',
        'installments_total' => 5,
        'is_recurring' => false,
    ]);

    expect($purchase->isActiveInMonth(2024, 7))->toBeTrue();
    expect($purchase->isActiveInMonth(2024, 8))->toBeTrue();
    expect($purchase->isActiveInMonth(2024, 11))->toBeTrue();
    expect($purchase->isActiveInMonth(2024, 12))->toBeFalse();
});

it('correctly calculates current installment number', function () {
    $purchase = Purchase::factory()->create([
        'start_date' => '2024-07-01',
        'installments_total' => 5,
        'is_recurring' => false,
    ]);

    expect($purchase->getCurrentInstallment(2024, 7))->toBe(1);
    expect($purchase->getCurrentInstallment(2024, 8))->toBe(2);
    expect($purchase->getCurrentInstallment(2024, 11))->toBe(5);
});

it('recurring purchase is always active', function () {
    $purchase = Purchase::factory()->recurring()->create([
        'start_date' => '2024-01-01',
    ]);

    expect($purchase->isActiveInMonth(2024, 1))->toBeTrue();
    expect($purchase->isActiveInMonth(2025, 12))->toBeTrue();
    expect($purchase->isActiveInMonth(2030, 6))->toBeTrue();
});

it('groups credit card purchases correctly', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $card = Card::factory()->create(['user_id' => $user->id]);

    Purchase::factory()->forCard($card)->create([
        'user_id' => $user->id,
        'amount' => 100,
        'start_date' => '2024-07-01',
        'installments_total' => 1,
    ]);

    Purchase::factory()->forCard($card)->create([
        'user_id' => $user->id,
        'amount' => 200,
        'start_date' => '2024-07-01',
        'installments_total' => 1,
    ]);

    $response = get(route('purchases.index', ['month' => 7, 'year' => 2024]));

    $response->assertSuccessful();
});

it('marks individual purchase as paid', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'status' => 'aberta',
    ]);

    patch(route('purchases.mark-as-paid', $purchase))->assertRedirect();

    assertDatabaseHas('purchase_payments', [
        'purchase_id' => $purchase->id,
        'month' => now()->month,
        'year' => now()->year,
    ]);
});

it('marks card purchase invoice as paid', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $card = Card::factory()->create(['user_id' => $user->id]);

    $purchase = Purchase::factory()->forCard($card)->create([
        'user_id' => $user->id,
        'start_date' => '2024-07-01',
        'amount' => 500,
    ]);

    Invoice::factory()->create([
        'user_id' => $user->id,
        'card_id' => $card->id,
        'month' => 7,
        'year' => 2024,
        'status' => 'fechada',
    ]);

    patch(route('purchases.mark-as-paid', $purchase), [
        'amount' => 500,
    ])->assertRedirect();

    assertDatabaseHas(Invoice::class, [
        'card_id' => $card->id,
        'month' => 7,
        'year' => 2024,
        'status' => 'paga',
    ]);
});

it('user cannot mark other users purchase as paid', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $this->actingAs($user);

    $purchase = Purchase::factory()->create(['user_id' => $otherUser->id]);

    patch(route('purchases.mark-as-paid', $purchase))->assertStatus(403);
});

it('marks individual purchase as paid in specific month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'status' => 'aberta',
    ]);

    patch(route('purchases.mark-as-paid', $purchase), [
        'month' => 6,
        'year' => 2024,
    ])->assertRedirect();

    assertDatabaseHas('purchase_payments', [
        'purchase_id' => $purchase->id,
        'month' => 6,
        'year' => 2024,
    ]);
});

it('marks individual purchase as paid in different months creating separate records', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'status' => 'aberta',
    ]);

    patch(route('purchases.mark-as-paid', $purchase), [
        'month' => 6,
        'year' => 2024,
    ])->assertRedirect();

    patch(route('purchases.mark-as-paid', $purchase), [
        'month' => 7,
        'year' => 2024,
    ])->assertRedirect();

    assertDatabaseHas('purchase_payments', [
        'purchase_id' => $purchase->id,
        'month' => 6,
        'year' => 2024,
    ]);

    assertDatabaseHas('purchase_payments', [
        'purchase_id' => $purchase->id,
        'month' => 7,
        'year' => 2024,
    ]);
});

it('unmarks individual purchase as paid', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'status' => 'aberta',
    ]);

    patch(route('purchases.mark-as-paid', $purchase), [
        'month' => 6,
        'year' => 2024,
    ]);

    patch(route('purchases.unmark-as-paid', $purchase), [
        'month' => 6,
        'year' => 2024,
    ])->assertRedirect();

    assertDatabaseMissing('purchase_payments', [
        'purchase_id' => $purchase->id,
        'month' => 6,
        'year' => 2024,
    ]);
});

it('unmark only removes payment for the specified month', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $purchase = Purchase::factory()->create([
        'user_id' => $user->id,
        'status' => 'aberta',
    ]);

    patch(route('purchases.mark-as-paid', $purchase), [
        'month' => 6,
        'year' => 2024,
    ]);

    patch(route('purchases.mark-as-paid', $purchase), [
        'month' => 7,
        'year' => 2024,
    ]);

    patch(route('purchases.unmark-as-paid', $purchase), [
        'month' => 6,
        'year' => 2024,
    ])->assertRedirect();

    assertDatabaseMissing('purchase_payments', [
        'purchase_id' => $purchase->id,
        'month' => 6,
        'year' => 2024,
    ]);

    assertDatabaseHas('purchase_payments', [
        'purchase_id' => $purchase->id,
        'month' => 7,
        'year' => 2024,
    ]);
});
