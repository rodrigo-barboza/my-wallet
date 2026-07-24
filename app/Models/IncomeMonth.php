<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['income_id', 'month', 'year', 'amount'])]
class IncomeMonth extends Model
{
    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function income(): BelongsTo
    {
        return $this->belongsTo(Income::class);
    }
}
