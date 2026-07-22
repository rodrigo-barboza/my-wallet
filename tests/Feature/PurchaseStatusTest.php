<?php

declare(strict_types=1);

use App\Enums\PurchaseStatus;
use App\Models\Purchase;

it('returns atrasada status when payment_day has passed', function () {
    $purchase = Purchase::factory()->create([
        'status' => PurchaseStatus::Aberta,
        'payment_day' => now()->subDay()->day,
        'start_date' => now()->subMonth(),
    ]);

    expect($purchase->status)->toBe(PurchaseStatus::Atrasada->value);
});

it('returns aberta status when payment_day has not passed', function () {
    $purchase = Purchase::factory()->create([
        'status' => PurchaseStatus::Aberta,
        'payment_day' => now()->addDays(5)->day,
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

    if (now()->day > $startDate->day) {
        expect($purchase->status)->toBe(PurchaseStatus::Atrasada->value);
    } else {
        expect($purchase->status)->toBe(PurchaseStatus::Aberta->value);
    }
});

it('returns aberta status as default when status attribute is missing', function () {
    $purchase = Purchase::factory()->create([
        'payment_day' => 25,
        'start_date' => now()->subMonth(),
    ]);

    expect($purchase->status)->toBe(PurchaseStatus::Aberta->value);
});
