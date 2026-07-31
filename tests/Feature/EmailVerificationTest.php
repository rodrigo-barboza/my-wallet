<?php

use App\Models\User;
use Illuminate\Support\Facades\URL;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

it('verifies email when clicking the signed verification link', function () {
    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $response = actingAs($user)->get($url);

    $response->assertRedirect(route('home'));

    assertDatabaseHas('users', [
        'id' => $user->id,
        'email_verified_at' => $user->fresh()->email_verified_at,
    ]);

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});
