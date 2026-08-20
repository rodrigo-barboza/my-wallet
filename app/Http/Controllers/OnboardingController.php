<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class OnboardingController extends Controller
{
    public function complete(Request $request): Response
    {
        $user = $request->user();
        $preferences = $user->preferences ?? [];
        $preferences['onboarding_completed'] = true;
        $preferences['onboarding_version'] = (int) ($request->input('version') ?? 0);
        $user->update(['preferences' => $preferences]);

        return response()->noContent();
    }

    public function groupsComplete(Request $request): Response
    {
        $user = $request->user();
        $preferences = $user->preferences ?? [];
        $preferences['onboarding_version'] = (int) ($request->input('version') ?? 0);
        $user->update(['preferences' => $preferences]);

        return response()->noContent();
    }

    public function reset(Request $request): Response
    {
        $user = $request->user();
        $preferences = $user->preferences ?? [];
        $preferences['onboarding_completed'] = false;
        unset($preferences['onboarding_version']);
        $user->update(['preferences' => $preferences]);

        return response()->noContent();
    }
}
