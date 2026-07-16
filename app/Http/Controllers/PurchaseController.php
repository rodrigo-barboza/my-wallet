<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Invoice;
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

        $cards = auth()->user()->cards()->latest()->get();

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases->values(),
            'summary' => $summary,
            'month' => $month,
            'year' => $year,
            'cards' => $cards,
        ]);
    }

    public function store(PurchaseRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['user_id'] = auth()->id();

        $purchase = Purchase::create($validated);

        $this->ensureInvoiceExists($purchase);

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

        $this->ensureInvoiceExists($purchase);

        return to_route('purchases.index');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        Gate::authorize('delete', $purchase);

        $purchase->delete();

        return to_route('purchases.index');
    }

    private function ensureInvoiceExists(Purchase $purchase): void
    {
        if (! $purchase->card_id) {
            return;
        }

        $card = $purchase->card;
        $startDate = $purchase->start_date;

        $closingDate = $startDate->copy()->day($card->closing_day);
        $dueDate = $startDate->copy()->day($card->due_day);

        if ($card->closing_day > $card->due_day) {
            $dueDate->addMonth();
        }

        Invoice::updateOrCreate(
            [
                'user_id' => $purchase->user_id,
                'card_id' => $purchase->card_id,
                'month' => $startDate->month,
                'year' => $startDate->year,
            ],
            [
                'closing_date' => $closingDate,
                'due_date' => $dueDate,
                'status' => now()->gte($closingDate) ? 'fechada' : 'aberta',
            ]
        );
    }

    private function buildSummary($purchases): array
    {
        $cardPurchases = $purchases->filter(fn ($p) => $p->card_id !== null);
        $individualPurchases = $purchases->filter(fn ($p) => $p->card_id === null);

        $grouped = $cardPurchases->groupBy('card_id');

        $summary = $grouped->map(function ($items) {
            $card = $items->first()->card;

            return [
                'name' => $card->name,
                'total' => $items->sum('amount'),
                'dates' => ['closing' => $card->closing_day, 'due' => $card->due_day],
                'items' => $items->values(),
            ];
        })->values();

        $individualPurchases->each(function ($purchase) use (&$summary) {
            $summary[] = [
                'name' => $purchase->name,
                'total' => $purchase->amount,
                'dates' => [$purchase->payment_day],
                'items' => [$purchase],
            ];
        });

        return $summary->all();
    }
}
