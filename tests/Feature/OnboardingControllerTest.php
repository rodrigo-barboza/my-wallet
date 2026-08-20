<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

it('marks onboarding complete and stores the version', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('onboarding.complete'), ['version' => 2])
        ->assertNoContent();

    $user->refresh();
    $this->assertTrue($user->preferences['onboarding_completed']);
    $this->assertSame(2, (int) $user->preferences['onboarding_version']);
});

it('stores the seen novelty version without completing onboarding', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->post(route('onboarding.groups-complete'), ['version' => 2])
        ->assertNoContent();

    $user->refresh();
    $this->assertSame(2, (int) $user->preferences['onboarding_version']);
    $this->assertArrayNotHasKey('onboarding_completed', $user->preferences ?? []);
});

it('resets onboarding and clears the stored version', function () {
    $user = User::factory()->create();
    $user->update([
        'preferences' => [
            'onboarding_completed' => true,
            'onboarding_version' => 2,
        ],
    ]);

    actingAs($user)->post(route('onboarding.reset'))->assertNoContent();

    $user->refresh();
    $this->assertFalse($user->preferences['onboarding_completed']);
    $this->assertArrayNotHasKey('onboarding_version', $user->preferences);
});
