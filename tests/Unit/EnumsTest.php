<?php

use App\Enums\InvoiceStatus;
use App\Enums\PurchaseStatus;

it('has correct InvoiceStatus cases', function () {
    expect(InvoiceStatus::cases())->toHaveCount(5)
        ->and(InvoiceStatus::Aberta->value)->toBe('aberta')
        ->and(InvoiceStatus::Fechada->value)->toBe('fechada')
        ->and(InvoiceStatus::Paga->value)->toBe('paga')
        ->and(InvoiceStatus::ParcialmentePaga->value)->toBe('parcialmente_paga')
        ->and(InvoiceStatus::Atrasada->value)->toBe('atrasada');
});

it('can create InvoiceStatus from string', function () {
    expect(InvoiceStatus::from('aberta'))->toBe(InvoiceStatus::Aberta)
        ->and(InvoiceStatus::from('fechada'))->toBe(InvoiceStatus::Fechada)
        ->and(InvoiceStatus::from('paga'))->toBe(InvoiceStatus::Paga)
        ->and(InvoiceStatus::from('parcialmente_paga'))->toBe(InvoiceStatus::ParcialmentePaga)
        ->and(InvoiceStatus::from('atrasada'))->toBe(InvoiceStatus::Atrasada);
});

it('has correct PurchaseStatus cases', function () {
    expect(PurchaseStatus::cases())->toHaveCount(3)
        ->and(PurchaseStatus::Aberta->value)->toBe('aberta')
        ->and(PurchaseStatus::Paga->value)->toBe('paga')
        ->and(PurchaseStatus::Atrasada->value)->toBe('atrasada');
});

it('can create PurchaseStatus from string', function () {
    expect(PurchaseStatus::from('aberta'))->toBe(PurchaseStatus::Aberta)
        ->and(PurchaseStatus::from('paga'))->toBe(PurchaseStatus::Paga)
        ->and(PurchaseStatus::from('atrasada'))->toBe(PurchaseStatus::Atrasada);
});

it('throws on invalid InvoiceStatus value', function () {
    InvoiceStatus::from('invalid');
})->throws(ValueError::class, '"invalid" is not a valid backing value');

it('throws on invalid PurchaseStatus value', function () {
    PurchaseStatus::from('invalid');
})->throws(ValueError::class, '"invalid" is not a valid backing value');
