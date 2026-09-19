<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

final readonly class WalletController
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'balance' => ['required', 'numeric'],
        ]);

        $request->user()->update(['wallet_balance' => (float) $validated['balance']]);

        Inertia::flash('toast', ['message' => 'Saldo atualizado!', 'type' => 'success']);

        return back();
    }
}
