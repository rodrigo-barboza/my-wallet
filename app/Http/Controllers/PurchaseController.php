<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\PurchaseRequest;
use App\Models\Invoice;
use App\Models\Purchase;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

final readonly class PurchaseController
{
    public function index(): Response
    {
        $month = (int) request()->input('month', now()->month);
        $year = (int) request()->input('year', now()->year);

        $purchases = Purchase::where('user_id', auth()->id())
            ->with('card')
            ->get()
            ->filter(fn ($p) => $p->isActiveInMonth($year, $month));

        $summary = $this->buildSummary($purchases, $month, $year);

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

        Inertia::flash('toast', ['message' => 'Compra criada com sucesso!', 'type' => 'success']);

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

        Inertia::flash('toast', ['message' => 'Compra atualizada com sucesso!', 'type' => 'success']);

        return to_route('purchases.index');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        Gate::authorize('delete', $purchase);

        $purchase->delete();

        Inertia::flash('toast', ['message' => 'Compra excluída com sucesso!', 'type' => 'success']);

        return to_route('purchases.index');
    }

    public function markAsPaid(Purchase $purchase): RedirectResponse
    {
        Gate::authorize('update', $purchase);

        if ($purchase->card_id) {
            $invoice = Invoice::where('card_id', $purchase->card_id)
                ->where('month', $purchase->start_date->month)
                ->where('year', $purchase->start_date->year)
                ->first();

            if ($invoice) {
                $invoice->update(['status' => 'paga', 'paid_at' => now()]);
            }
        } else {
            $purchase->update(['status' => 'paga', 'paid_at' => now()]);
        }

        Inertia::flash('toast', ['message' => 'Marcado como pago!', 'type' => 'success']);

        return to_route('purchases.index');
    }

    public function unmarkAsPaid(Purchase $purchase): RedirectResponse
    {
        Gate::authorize('update', $purchase);

        if ($purchase->card_id) {
            $invoice = Invoice::where('card_id', $purchase->card_id)
                ->where('month', $purchase->start_date->month)
                ->where('year', $purchase->start_date->year)
                ->first();

            if ($invoice) {
                $invoice->update(['status' => 'fechada', 'paid_at' => null]);
            }
        } else {
            $purchase->update(['status' => 'aberta', 'paid_at' => null]);
        }

        Inertia::flash('toast', ['message' => 'Pagamento desmarcado!', 'type' => 'success']);

        return to_route('purchases.index');
    }

    public function reorder(): JsonResponse
    {
        $data = request()->validate([
            'order' => ['required', 'array'],
            'order.*' => ['required', 'string'],
        ]);

        auth()->user()->update(['purchase_order' => $data['order']]);

        return response()->json(['message' => 'ok']);
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

    private function buildSummary($purchases, int $month, int $year): array
    {
        $now = now();

        $cardPurchases = $purchases->filter(fn ($p) => $p->card_id !== null);
        $individualPurchases = $purchases->filter(fn ($p) => $p->card_id === null);

        $grouped = $cardPurchases->groupBy('card_id');

        $summary = $grouped->map(function ($items) use ($month, $year) {
            $card = $items->first()->card;
            $invoice = Invoice::where('card_id', $card->id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            $status = $invoice?->status ?? 'aberta';

            return [
                'name' => $card->name,
                'total' => $items->sum('amount'),
                'dates' => ['closing' => $card->closing_day, 'due' => $card->due_day],
                'status' => $status,
                'paid_at' => $invoice?->paid_at?->toISOString(),
                'items' => $items->values(),
            ];
        })->values();

        $individualPurchases->each(function ($purchase) use (&$summary, $month, $year, $now) {
            $paymentDay = $purchase->payment_day ?? $purchase->start_date->day;
            $status = $this->resolveIndividualStatus($purchase, $paymentDay, $month, $year, $now);

            $summary[] = [
                'name' => $purchase->name,
                'total' => $purchase->amount,
                'dates' => [$paymentDay],
                'status' => $status,
                'paid_at' => $purchase->paid_at?->toISOString(),
                'items' => [$purchase],
            ];
        });

        $order = auth()->user()->purchase_order ?? [];

        if (! empty($order)) {
            $positions = array_flip($order);

            $sorted = $summary->sortBy(fn ($item) => $this->itemOrderKey($item, $positions));

            return $sorted->values()->all();
        }

        return $summary->all();
    }

    private function itemOrderKey(array $item, array $positions): int
    {
        $firstItem = $item['items'][0] ?? null;

        if ($firstItem === null) {
            return PHP_INT_MAX;
        }

        if (isset($firstItem->card_id)) {
            $key = 'card_'.$firstItem->card_id;
        } else {
            $key = 'purchase_'.$firstItem->id;
        }

        return $positions[$key] ?? PHP_INT_MAX;
    }

    private function resolveIndividualStatus(Purchase $purchase, int $paymentDay, int $month, int $year, Carbon $now): string
    {
        if ($purchase->paid_at) {
            $paidMonth = (int) $purchase->paid_at->month;
            $paidYear = (int) $purchase->paid_at->year;

            if ($month === $paidMonth && $year === $paidYear) {
                return 'paga';
            }
        }

        if ($year < $now->year || ($year === $now->year && $month < $now->month)) {
            return 'atrasada';
        }

        if ($year === $now->year && $month === $now->month) {
            return $now->day > $paymentDay ? 'atrasada' : 'aberta';
        }

        return 'aberta';
    }
}
