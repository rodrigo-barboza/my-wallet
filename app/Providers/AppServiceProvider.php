<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Inertia::share('onboarding_completed', function (): bool {
            $user = auth()->user();

            if (! $user) {
                return true;
            }

            $preferences = $user->preferences ?? [];

            return $preferences['onboarding_completed'] ?? false;
        });

        Inertia::share('onboarding_version', function (): int {
            $user = auth()->user();

            if (! $user) {
                return 0;
            }

            $preferences = $user->preferences ?? [];

            return (int) ($preferences['onboarding_version'] ?? 0);
        });

        Inertia::share('wallet_balance', fn (): float => (float) (auth()->user()?->wallet_balance ?? 0));

        Inertia::share('wallet_hidden', fn (): bool => (bool) ((auth()->user()?->preferences ?? [])['wallet_hidden'] ?? false));
    }
}
