<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Notifications\AccountDeletionNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController
{
    public function index(): Response
    {
        $user = auth()->user();

        return Inertia::render('Profile/Index', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'avatar' => $user->preferences['avatar'] ?? null,
                'provider' => $user->preferences['provider'] ?? null,
            ],
        ]);
    }

    public function updateName(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->update($validated);

        return back()->with('toast', ['message' => 'Nome atualizado com sucesso!', 'type' => 'success']);
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        if ($request->user()->preferences['provider'] ?? null) {
            throw ValidationException::withMessages([
                'email' => 'Não é possível alterar o e-mail de uma conta vinculada ao Google.',
            ]);
        }

        $validated = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
        ]);

        $request->user()->update([
            'email' => $validated['email'],
            'email_verified_at' => null,
        ]);

        return back()->with('toast', ['message' => 'E-mail atualizado! Verifique seu novo endereço.', 'type' => 'success']);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $isGoogleUser = ($request->user()->preferences['provider'] ?? null) === 'google';

        $rules = [
            'password' => ['required', 'string', 'min:12', 'confirmed', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
        ];

        if (! $isGoogleUser) {
            $rules['current_password'] = ['required', 'string', 'current_password'];
        }

        $validated = $request->validate($rules);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        $message = $isGoogleUser
            ? 'Senha definida com sucesso! Agora você pode entrar com e-mail e senha.'
            : 'Senha atualizada com sucesso!';

        return back()->with('toast', ['message' => $message, 'type' => 'success']);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();

        $confirmationUrl = URL::temporarySignedRoute(
            'profile.confirm-destroy',
            now()->addMinutes(60),
            ['user' => $user->id],
        );

        $user->notify(new AccountDeletionNotification($confirmationUrl));

        return back()->with('toast', [
            'message' => 'Enviamos um e-mail de confirmação para excluir sua conta. Verifique sua caixa de entrada.',
            'type' => 'success',
        ]);
    }

    public function confirmDestroy(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            abort(401);
        }

        $user = $request->user();

        if (! $user || (int) $request->route('user') !== $user->id) {
            return to_route('profile');
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return to_route('home');
    }
}
