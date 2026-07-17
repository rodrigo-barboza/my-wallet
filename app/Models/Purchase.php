<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseStatus;
use App\Enums\PurchaseType;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'name', 'type', 'payment_day', 'is_recurring', 'card_id', 'amount', 'installments_total', 'start_date', 'notes', 'status', 'paid_at'])]
class Purchase extends Model
{
    /** @use HasFactory<PurchaseFactory> */
    use HasFactory;

    protected $casts = [
        'type' => PurchaseType::class,
        'is_recurring' => 'boolean',
        'amount' => 'decimal:2',
        'start_date' => 'date',
    ];

    public function name(): Attribute
    {
        return Attribute::make(
            get: fn (string $name) => Str::title($name),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function isActiveInMonth(int $year, int $month): bool
    {
        $monthsDiff = ($year - $this->start_date->year) * 12
                    + ($month - $this->start_date->month);

        if ($monthsDiff < 0) {
            return false;
        }

        if ($this->is_recurring) {
            return true;
        }

        if (! $this->installments_total) {
            return $monthsDiff === 0;
        }

        return $monthsDiff < $this->installments_total;
    }

    public function getCurrentInstallment(int $year, int $month): ?int
    {
        if (! $this->installments_total) {
            return null;
        }

        $monthsDiff = ($year - $this->start_date->year) * 12
                    + ($month - $this->start_date->month);

        return $monthsDiff + 1;
    }

    public function getStatusAttribute(): string
    {
        $status = $this->attributes['status'] ?? null;

        if ($status === PurchaseStatus::Paga->value) {
            return PurchaseStatus::Paga->value;
        }

        $paymentDay = $this->payment_day ?? $this->start_date->day;

        if (now()->day > $paymentDay) {
            return PurchaseStatus::Atrasada->value;
        }

        return PurchaseStatus::Aberta->value;
    }
}
