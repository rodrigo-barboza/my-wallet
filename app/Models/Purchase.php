<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PurchaseType;
use Database\Factories\PurchaseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'type', 'payment_day', 'is_recurring', 'card_id', 'amount', 'installments_total', 'start_date', 'notes'])]
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

        if (! $this->installments_total) {
            return $this->is_recurring;
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
}
