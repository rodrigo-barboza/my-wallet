<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CardFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['name', 'color', 'closing_day', 'due_day', 'notify_closing', 'notify_due'])]
class Card extends Model
{
    /** @use HasFactory<CardFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'notify_closing' => 'boolean',
            'notify_due' => 'boolean',
        ];
    }
}
