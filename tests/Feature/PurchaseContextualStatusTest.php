<?php

declare(strict_types=1);

use App\Models\Purchase;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\get;

uses(RefreshDatabase::class);

it('shows aberta status for future month even if payment day passed in current month', function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 16));

    $user = User::factory()->create();
    $this->actingAs($user);

    Purchase::factory()->recurring()->create([
        'user_id' => $user->id,
        'payment_day' => 10,
        'start_date' => '2026-01-01',
    ]);

    $response = get(route('purchases.index', ['month' => 8, 'year' => 2026]));

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props['summary'][0]['status'])->toBe('aberta');

    Carbon::setTestNow();
});

it('shows atrasada status for past month when payment day has passed', function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 16));

    $user = User::factory()->create();
    $this->actingAs($user);

    Purchase::factory()->recurring()->create([
        'user_id' => $user->id,
        'payment_day' => 10,
        'start_date' => '2026-01-01',
    ]);

    $response = get(route('purchases.index', ['month' => 6, 'year' => 2026]));

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props['summary'][0]['status'])->toBe('atrasada');

    Carbon::setTestNow();
});

it('shows atrasada status for current month when payment day has passed', function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 16));

    $user = User::factory()->create();
    $this->actingAs($user);

    Purchase::factory()->recurring()->create([
        'user_id' => $user->id,
        'payment_day' => 10,
        'start_date' => '2026-01-01',
    ]);

    $response = get(route('purchases.index', ['month' => 7, 'year' => 2026]));

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props['summary'][0]['status'])->toBe('atrasada');

    Carbon::setTestNow();
});

it('shows aberta status for current month when payment day has not passed', function () {
    Carbon::setTestNow(Carbon::create(2026, 7, 16));

    $user = User::factory()->create();
    $this->actingAs($user);

    Purchase::factory()->recurring()->create([
        'user_id' => $user->id,
        'payment_day' => 20,
        'start_date' => '2026-01-01',
    ]);

    $response = get(route('purchases.index', ['month' => 7, 'year' => 2026]));

    $response->assertSuccessful();
    $props = $response->viewData('page')['props'];

    expect($props['summary'][0]['status'])->toBe('aberta');

    Carbon::setTestNow();
});
