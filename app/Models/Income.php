<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['user_id', 'name', 'group_id'])]
class Income extends Model
{
    public function name(): Attribute
    {
        return Attribute::make(
            get: fn (string $name) => Str::title($name),
        );
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(IncomeGroup::class, 'group_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incomeMonths(): HasMany
    {
        return $this->hasMany(IncomeMonth::class);
    }
}
