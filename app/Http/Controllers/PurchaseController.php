<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Http\Requests\PurchaseRequest;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Purchase;
use App\Models\PurchasePayment;
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
            ->with(['card', 'payments'])
            ->get()
            ->filter(fn ($p) => $p->isActiveInMonth($year, $month));

        $summary = $this->buildSummary($purchases, $month, $year);

        $cards = auth()->user()->cards()->latest()->get();

        $individualPayments = PurchasePayment::where('month', $month)
            ->where('year', $year)
            ->whereHas('purchase', fn ($q) => $q->where('user_id', auth()->id()))
            ->with('purchase:id,name,amount,installments_total,type')
            ->get()
            ->map(fn ($payment) => [
                'id' => $payment->id,
                'name' => $payment->purchase->name,
                'amount' => $payment->purchase->type === 'credit_card' && $payment->purchase->installments_total
                    ? (float) $payment->purchase->amount / $payment->purchase->installments_total
                    : (float) $payment->purchase->amount,
                'paid_at' => $payment->paid_at->toISOString(),
                'type' => $payment->purchase->type,
            ]);

        $cardPayments = InvoicePayment::whereHas('invoice', function ($q) use ($month, $year) {
            $q->where('month', $month)
                ->where('year', $year)
                ->where('user_id', auth()->id());
        })
            ->with('invoice:id,card_id,month,year,paid_amount,status,paid_at', 'invoice.card:id,name')
            ->orderBy('paid_at')
            ->get()
            ->groupBy('invoice_id')
            ->flatMap(function ($payments) {
                $invoice = $payments->first()->invoice;
                $purchasesInMonth = Purchase::where('card_id', $invoice->card_id)
                    ->where('user_id', auth()->id())
                    ->get()
                    ->filter(fn ($p) => $p->isActiveInMonth($invoice->year, $invoice->month));
                $total = (float) $purchasesInMonth->sum(fn ($p) => $p->installments_total ? ($p->amount / $p->installments_total) : $p->amount);

                $runningTotal = 0.0;

                return $payments->map(function ($payment) use ($invoice, &$runningTotal, $total) {
                    $runningTotal += (float) $payment->amount;
                    $isPartial = $runningTotal < $total - 0.01;

                    return [
                        'id' => $payment->id,
                        'name' => $invoice->card->name,
                        'amount' => (float) $payment->amount,
                        'paid_at' => $payment->paid_at->toISOString(),
                        'type' => 'credit_card',
                        'partial' => $isPartial,
                    ];
                });
            });

        $paymentHistory = $individualPayments->concat($cardPayments)
            ->sortByDesc('paid_at')
            ->values();

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases->values(),
            'summary' => $summary,
            'paymentHistory' => $paymentHistory,
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

        if ($request->boolean('mark_as_paid') && ! $purchase->card_id && ! $purchase->is_recurring) {
            $purchase->payments()->create([
                'month' => $purchase->start_date->month,
                'year' => $purchase->start_date->year,
                'paid_at' => now(),
            ]);
        }

        Inertia::flash('toast', ['message' => 'Compra criada com sucesso!', 'type' => 'success']);

        return $this->redirectToPurchase($purchase);
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

        return $this->redirectToPurchase($purchase);
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        Gate::authorize('delete', $purchase);

        $cardId = $purchase->card_id;
        $startDate = $purchase->start_date;

        $purchase->delete();

        Inertia::flash('toast', ['message' => 'Compra excluída com sucesso!', 'type' => 'success']);

        $month = (int) request()->input('month', $startDate->month);
        $year = (int) request()->input('year', $startDate->year);

        if ($cardId) {
            return to_route('cards.purchases', [
                'card' => $cardId,
                'month' => $month,
                'year' => $year,
            ]);
        }

        return to_route('purchases.index', ['month' => $month, 'year' => $year]);
    }

    public function markAsPaid(Purchase $purchase): RedirectResponse
    {
        Gate::authorize('update', $purchase);

        $month = (int) request()->input('month', now()->month);
        $year = (int) request()->input('year', now()->year);

        if ($purchase->card_id) {
            $card = $purchase->card;
            $invoiceDate = Carbon::create($year, $month, 1);
            $closingDate = $invoiceDate->copy()->day($card->closing_day);
            $dueDate = $invoiceDate->copy()->day($card->due_day);

            if ($card->closing_day > $card->due_day) {
                $dueDate->addMonth();
            }

            $invoice = Invoice::firstOrCreate(
                [
                    'user_id' => $purchase->user_id,
                    'card_id' => $purchase->card_id,
                    'month' => $month,
                    'year' => $year,
                ],
                [
                    'status' => InvoiceStatus::Aberta->value,
                    'closing_date' => $closingDate,
                    'due_date' => $dueDate,
                ],
            );

            $amount = (float) request()->input('amount');
            $purchasesInMonth = Purchase::where('card_id', $purchase->card_id)
                ->where('user_id', $purchase->user_id)
                ->get()
                ->filter(fn ($p) => $p->isActiveInMonth($year, $month));

            $total = (float) $purchasesInMonth->sum(fn ($p) => $p->installments_total ? ($p->amount / $p->installments_total) : $p->amount);

            $invoice->payments()->create([
                'amount' => $amount,
                'paid_at' => now(),
            ]);

            $newPaidAmount = (float) $invoice->payments()->sum('amount');

            if ($newPaidAmount >= $total - 0.01) {
                $invoice->update([
                    'paid_amount' => $newPaidAmount,
                    'status' => InvoiceStatus::Paga,
                    'paid_at' => $invoice->payments()->latest('paid_at')->first()->paid_at,
                ]);
            } elseif ($newPaidAmount > 0) {
                $invoice->update([
                    'paid_amount' => $newPaidAmount,
                    'status' => InvoiceStatus::ParcialmentePaga,
                    'paid_at' => null,
                ]);
            }
        } else {
            $purchase->payments()->updateOrCreate(
                ['month' => $month, 'year' => $year],
                ['paid_at' => now()],
            );
        }

        Inertia::flash('toast', ['message' => 'Marcado como pago!', 'type' => 'success']);

        if (request()->input('redirect') === 'purchases') {
            return to_route('purchases.index', ['month' => $month, 'year' => $year]);
        }

        if ($purchase->card_id) {
            return to_route('cards.purchases', [
                'card' => $purchase->card_id,
                'month' => $month,
                'year' => $year,
            ]);
        }

        return to_route('purchases.index', ['month' => $month, 'year' => $year]);
    }

    public function unmarkAsPaid(Purchase $purchase): RedirectResponse
    {
        Gate::authorize('update', $purchase);

        $month = (int) request()->input('month', now()->month);
        $year = (int) request()->input('year', now()->year);

        if ($purchase->card_id) {
            $invoice = Invoice::where('card_id', $purchase->card_id)
                ->where('month', $month)
                ->where('year', $year)
                ->first();

            if ($invoice) {
                $invoice->payments()->delete();
                $invoice->update([
                    'paid_amount' => null,
                    'paid_at' => null,
                    'status' => InvoiceStatus::Aberta,
                ]);
            }
        } else {
            $purchase->payments()->where('month', $month)->where('year', $year)->delete();
        }

        Inertia::flash('toast', ['message' => 'Pagamento desmarcado!', 'type' => 'success']);

        if (request()->input('redirect') === 'purchases') {
            return to_route('purchases.index', ['month' => $month, 'year' => $year]);
        }

        if ($purchase->card_id) {
            return to_route('cards.purchases', [
                'card' => $purchase->card_id,
                'month' => $month,
                'year' => $year,
            ]);
        }

        return to_route('purchases.index', ['month' => $month, 'year' => $year]);
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

        $invoice = Invoice::where('card_id', $purchase->card_id)
            ->where('month', $startDate->month)
            ->where('year', $startDate->year)
            ->first();

        if ($invoice) {
            $invoice->update([
                'closing_date' => $closingDate,
                'due_date' => $dueDate,
            ]);
        } else {
            Invoice::create([
                'user_id' => $purchase->user_id,
                'card_id' => $purchase->card_id,
                'month' => $startDate->month,
                'year' => $startDate->year,
                'closing_date' => $closingDate,
                'due_date' => $dueDate,
                'status' => now()->gte($closingDate) ? 'fechada' : 'aberta',
            ]);
        }
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

            $paidAmount = $invoice?->paid_amount;
            $originalTotal = (float) $items->sum(fn ($p) => $p->installments_total ? ($p->amount / $p->installments_total) : $p->amount);

            $status = match (true) {
                $paidAmount !== null && (float) $paidAmount >= $originalTotal - 0.01 => 'paga',
                $paidAmount !== null && (float) $paidAmount > 0 => 'parcialmente_paga',
                default => $invoice?->status ?? 'aberta',
            };

            return [
                'name' => $card->name,
                'total' => $originalTotal,
                'dates' => ['closing' => $card->closing_day, 'due' => $card->due_day],
                'status' => $status,
                'paid_at' => $invoice?->paid_at?->toISOString(),
                'paid_amount' => $paidAmount !== null ? (float) $paidAmount : null,
                'items' => $items->values(),
            ];
        })->values();

        $individualPurchases->each(function ($purchase) use (&$summary, $month, $year, $now) {
            $paymentDay = $purchase->payment_day ?? $purchase->start_date->day;
            $status = $this->resolveIndividualStatus($purchase, $paymentDay, $month, $year, $now);
            $payment = $purchase->payments->firstWhere('month', $month) ?? $purchase->payments->firstWhere('year', $year);

            $summary[] = [
                'name' => $purchase->name,
                'total' => $purchase->amount,
                'dates' => [$paymentDay],
                'status' => $status,
                'paid_at' => $payment?->paid_at?->toISOString(),
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

    private function redirectToPurchase(Purchase $purchase): RedirectResponse
    {
        $month = (int) request()->input('month', $purchase->start_date->month);
        $year = (int) request()->input('year', $purchase->start_date->year);

        if ($purchase->card_id) {
            return to_route('cards.purchases', [
                'card' => $purchase->card_id,
                'month' => $month,
                'year' => $year,
            ]);
        }

        return to_route('purchases.index', ['month' => $month, 'year' => $year]);
    }

    private function resolveIndividualStatus(Purchase $purchase, int $paymentDay, int $month, int $year, Carbon $now): string
    {
        if ($purchase->payments->firstWhere('month', $month)) {
            return 'paga';
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
