<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Card;
use App\Models\Purchase;
use App\Policies\CardPolicy;
use App\Policies\PurchasePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Card::class => CardPolicy::class,
        Purchase::class => PurchasePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
