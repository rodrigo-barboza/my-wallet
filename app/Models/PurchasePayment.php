<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    protected $fillable = ['purchase_id', 'month', 'year', 'paid_at'];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'paid_at' => 'datetime',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }
}
