<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final class PurchaseController
{
    public function index(): Response
    {
        $month = (int) request()->input('month', now()->month);
        $year = (int) request()->input('year', now()->year);

        $purchases = Purchase::where('user_id', auth()->id())
            ->with('card')
            ->get()
            ->filter(fn ($p) => $p->isActiveInMonth($year, $month));

        $summary = $this->buildSummary($purchases);

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases->values(),
            'summary' => $summary,
            'month' => $month,
            'year' => $year,
        ]);
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        Purchase::create($validated);

        return to_route('purchases.index');
    }

    public function show(Purchase $purchase): Response
    {
        Gate::authorize('view', $purchase);

        $purchase->load('card');

        return Inertia::render('Purchases/Show', [
            'purchase' => $purchase,
        ]);
    }

    public function update(PurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        Gate::authorize('update', $purchase);

        $purchase->update($request->validated());

        return to_route('purchases.index');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        Gate::authorize('delete', $purchase);

        $purchase->delete();

        return to_route('purchases.index');
    }

    private function buildSummary($purchases): array
    {
        $grouped = $purchases->groupBy(fn ($p) => $p->card_id ?? 'individual');

        return $grouped->map(function ($items, $key) {
            $card = $key !== 'individual' ? $items->first()->card : null;

            return [
                'name' => $card?->name ?? null,
                'total' => $items->sum('amount'),
                'dates' => $card
                    ? ['closing' => $card->closing_day, 'due' => $card->due_day]
                    : $items->pluck('payment_day')->filter()->values(),
                'items' => $items->values(),
            ];
        })->values();
    }
}
