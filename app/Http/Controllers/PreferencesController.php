<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class PreferencesController
{
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'key' => ['required', 'string'],
            'value' => ['nullable'],
        ]);

        $preferences = auth()->user()->preferences ?? [];
        $preferences[$validated['key']] = $validated['value'];

        auth()->user()->update(['preferences' => $preferences]);

        return response()->json(['preferences' => $preferences]);
    }
}
