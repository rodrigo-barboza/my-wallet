<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final readonly class EmailVerificationController
{
    public function notice(): Response|RedirectResponse
    {
        /** @var User $user */
        $user = request()->user();

        if ($user->hasVerifiedEmail()) {
            return to_route('home');
        }

        return Inertia::render('Auth/VerifyEmail');
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->getKey() !== $id) {
            return to_route('home');
        }

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return to_route('verification.notice');
        }

        if ($user->hasVerifiedEmail()) {
            return to_route('home');
        }

        $user->markEmailAsVerified();

        return to_route('home');
    }

    public function send(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return to_route('home');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
