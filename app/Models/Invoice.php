<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvoiceStatus;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'card_id', 'month', 'year', 'status', 'closing_date', 'due_date', 'paid_at'])]
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use HasFactory;

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'status' => InvoiceStatus::class,
        'closing_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(Card::class);
    }

    public function getStatusAttribute(): string
    {
        $status = $this->attributes['status'];

        if ($status === InvoiceStatus::Paga->value) {
            return InvoiceStatus::Paga->value;
        }

        if (now()->gte($this->due_date)) {
            return InvoiceStatus::Atrasada->value;
        }

        if (now()->gte($this->closing_date)) {
            return InvoiceStatus::Fechada->value;
        }

        return InvoiceStatus::Aberta->value;
    }
}
