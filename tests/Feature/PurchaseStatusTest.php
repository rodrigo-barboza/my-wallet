<?php

declare(strict_types=1);

use App\Enums\PurchaseStatus;
use App\Models\Purchase;

beforeEach(function () {
    $this->travelTo('2026-01-15');
});

it('returns atrasada status when payment_day has passed', function () {
    $purchase = Purchase::factory()->create([
        'status' => PurchaseStatus::Aberta,
        'payment_day' => 10,
        'start_date' => now()->subMonth(),
    ]);

    expect($purchase->status)->toBe(PurchaseStatus::Atrasada->value);
});

it('returns aberta status when payment_day has not passed', function () {
    $purchase = Purchase::factory()->create([
        'status' => PurchaseStatus::Aberta,
        'payment_day' => 20,
        'start_date' => now()->subMonth(),
    ]);

    expect($purchase->status)->toBe(PurchaseStatus::Aberta->value);
});

it('falls back to start_date->day when payment_day is null', function () {
    $startDate = now()->subMonth()->startOfMonth()->addDays(2);
    $purchase = Purchase::factory()->create([
        'status' => PurchaseStatus::Aberta,
        'payment_day' => null,
        'start_date' => $startDate,
    ]);

    expect($purchase->status)->toBe(PurchaseStatus::Atrasada->value);
});

it('returns aberta status as default when status attribute is missing', function () {
    $purchase = Purchase::factory()->create([
        'payment_day' => 20,
        'start_date' => now()->subMonth(),
    ]);

    expect($purchase->status)->toBe(PurchaseStatus::Aberta->value);
});
