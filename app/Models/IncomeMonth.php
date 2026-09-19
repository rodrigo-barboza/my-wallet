<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['income_id', 'month', 'year', 'amount', 'received_at'])]
class IncomeMonth extends Model
{
    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'amount' => 'decimal:2',
        'received_at' => 'datetime',
    ];

    public function income(): BelongsTo
    {
        return $this->belongsTo(Income::class);
    }

    public function isReceived(): bool
    {
        return $this->received_at !== null;
    }

    public function setReceived(): void
    {
        if ($this->isReceived()) {
            return;
        }

        $this->received_at = now();
        $this->income->user->increment('wallet_balance', (float) $this->amount);
        $this->save();
    }

    public function clearReceived(): void
    {
        if (! $this->isReceived()) {
            return;
        }

        $this->income->user->decrement('wallet_balance', (float) $this->amount);
        $this->received_at = null;
        $this->save();
    }
}
