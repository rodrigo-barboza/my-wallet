<?php

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('guests can view the registration page', function () {
    $response = get(route('register'));

    $response->assertSuccessful();
});

it('authenticated users cannot view the registration page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = get(route('register'));

    $response->assertRedirect(route('dashboard'));
});

it('guests can register with valid data', function () {
    Notification::fake();

    $response = post(route('register.store'), [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('verification.notice'));

    assertAuthenticated();

    assertDatabaseHas('users', [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'email_verified_at' => null,
    ]);

    $user = User::where('email', 'joao@example.com')->first();

    expect(Hash::check('password123', $user->password))->toBeTrue();

    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('name is required', function () {
    $response = post(route('register.store'), [
        'name' => '',
        'email' => 'joao@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('name');

    assertGuest();
    assertDatabaseMissing('users', ['email' => 'joao@example.com']);
});

it('email is required', function () {
    $response = post(route('register.store'), [
        'name' => 'João Silva',
        'email' => '',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');

    assertGuest();
    assertDatabaseMissing('users', ['email' => '']);
});

it('email must be valid', function () {
    $response = post(route('register.store'), [
        'name' => 'João Silva',
        'email' => 'not-an-email',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');

    assertGuest();
});

it('email must be unique', function () {
    User::factory()->create(['email' => 'joao@example.com']);

    $response = post(route('register.store'), [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');

    assertGuest();
});

it('password is required', function () {
    $response = post(route('register.store'), [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'password' => '',
        'password_confirmation' => '',
    ]);

    $response->assertSessionHasErrors('password');

    assertGuest();
});

it('password must be at least 8 characters', function () {
    $response = post(route('register.store'), [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors('password');

    assertGuest();
});

it('password must be confirmed', function () {
    $response = post(route('register.store'), [
        'name' => 'João Silva',
        'email' => 'joao@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors('password');

    assertGuest();
});

it('email is stored in lowercase', function () {
    $response = post(route('register.store'), [
        'name' => 'João Silva',
        'email' => 'JoAo@ExAmPlE.CoM',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    assertDatabaseHas('users', [
        'email' => 'joao@example.com',
    ]);

    assertDatabaseMissing('users', [
        'email' => 'JoAo@ExAmPlE.CoM',
    ]);
});

it('authenticated users cannot register', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = post(route('register.store'), [
        'name' => 'Outro Usuário',
        'email' => 'outro@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('dashboard'));

    assertDatabaseMissing('users', ['email' => 'outro@example.com']);
});

it('name has a maximum of 255 characters', function () {
    $response = post(route('register.store'), [
        'name' => str_repeat('a', 256),
        'email' => 'joao@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('name');

    assertGuest();
});
