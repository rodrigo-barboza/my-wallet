<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\Auth\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

final class ProfileController
{
    public function index(): Response
    {
        return Inertia::render('Profile/Index', [
            'user' => [
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'email_verified_at' => auth()->user()->email_verified_at?->toIso8601String(),
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
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:12', 'confirmed', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('toast', ['message' => 'Senha atualizada com sucesso!', 'type' => 'success']);
    }
}
