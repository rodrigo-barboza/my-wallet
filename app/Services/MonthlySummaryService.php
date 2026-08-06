<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\IncomeMonth;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\PurchasePayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final readonly class MonthlySummaryService
{
    public function buildForMonth(User $user, int $year, int $month): Collection
    {
        $purchases = $user->purchases()
            ->with(['card', 'payments'])
            ->get()
            ->filter(fn ($p) => $p->isActiveInMonth($year, $month));

        $cardPurchases = $purchases->filter(fn ($p) => $p->card_id !== null);
        $individualPurchases = $purchases->filter(fn ($p) => $p->card_id === null);

        $summary = collect();

        $grouped = $cardPurchases->groupBy('card_id');

        foreach ($grouped as $cardId => $items) {
            $card = $items->first()->card;
            $originalTotal = (float) $items->sum(fn ($p) => $p->installments_total ? ($p->amount / $p->installments_total) : $p->amount);

            $summary->push([
                'id' => "card_{$cardId}",
                'name' => $card->name,
                'type' => 'credit_card',
                'total' => $originalTotal,
                'card_id' => (int) $cardId,
            ]);
        }

        foreach ($individualPurchases as $purchase) {
            $summary->push([
                'id' => "purchase_{$purchase->id}",
                'name' => $purchase->name,
                'type' => $purchase->type->value,
                'total' => (float) ($purchase->installments_total
                    ? $purchase->amount / $purchase->installments_total
                    : $purchase->amount),
                'purchase_id' => (int) $purchase->id,
                'payment_day' => $purchase->payment_day ?? $purchase->start_date->day,
                'is_recurring' => $purchase->is_recurring,
            ]);
        }

        return $summary;
    }

    public function incomeForMonth(User $user, int $year, int $month): float
    {
        return (float) IncomeMonth::where('month', $month)
            ->where('year', $year)
            ->whereHas('income', fn ($q) => $q->where('user_id', $user->id))
            ->sum('amount');
    }

    public function paidForMonth(User $user, int $year, int $month): float
    {
        $individualPaid = PurchasePayment::where('month', $month)
            ->where('year', $year)
            ->whereHas('purchase', fn ($q) => $q->where('user_id', $user->id))
            ->with('purchase:amount,installments_total,type')
            ->get()
            ->sum(fn ($payment) => $payment->purchase
                ? ($payment->purchase->type === 'credit_card' && $payment->purchase->installments_total
                    ? (float) $payment->purchase->amount / $payment->purchase->installments_total
                    : (float) $payment->purchase->amount)
                : 0
            );

        $cardPaid = InvoicePayment::whereHas('invoice', function ($q) use ($user, $month, $year) {
            $q->where('month', $month)
                ->where('year', $year)
                ->where('user_id', $user->id);
        })->sum('amount');

        return (float) $individualPaid + (float) $cardPaid;
    }

    public function expensesByType(User $user, int $year, int $month): Collection
    {
        $purchases = $user->purchases()
            ->get()
            ->filter(fn ($p) => $p->isActiveInMonth($year, $month));

        $typeLabels = [
            'credit_card' => 'Cartão de crédito',
            'bill' => 'Conta',
            'financing' => 'Financiamento',
            'others' => 'Outros',
        ];

        return $purchases
            ->groupBy(fn ($p) => $p->type->value)
            ->map(function ($items, $type) use ($typeLabels) {
                $total = (float) $items->sum(fn ($p) => $p->installments_total ? ($p->amount / $p->installments_total) : $p->amount);

                return [
                    'type' => $type,
                    'label' => $typeLabels[$type] ?? $type,
                    'total' => $total,
                ];
            })
            ->values();
    }

    public function upcomingPayments(User $user, int $year, int $month, int $limit = 7): Collection
    {
        $now = Carbon::now();
        $payments = collect();

        $cards = $user->cards()->get();

        foreach ($cards as $card) {
            $checkMonth = $month;
            $checkYear = $year;
            $found = false;

            for ($i = 0; $i < 12 && ! $found; $i++) {
                $active = $user->purchases()
                    ->where('card_id', $card->id)
                    ->get()
                    ->filter(fn ($p) => $p->isActiveInMonth($checkYear, $checkMonth));

                if ($active->isEmpty()) {
                    $checkMonth++;
                    if ($checkMonth > 12) {
                        $checkMonth = 1;
                        $checkYear++;
                    }

                    continue;
                }

                $total = (float) $active->sum(fn ($p) => $p->installments_total ? ($p->amount / $p->installments_total) : $p->amount);
                $dueDate = Carbon::create($checkYear, $checkMonth, (int) $card->due_day);

                $invoice = Invoice::where('card_id', $card->id)
                    ->where('month', $checkMonth)
                    ->where('year', $checkYear)
                    ->first();

                $allPurchasesPaid = $active->every(fn ($p) => $p->payments()
                    ->where('month', $checkMonth)
                    ->where('year', $checkYear)
                    ->exists());

                $isPaid = ($invoice && in_array($invoice->status, [InvoiceStatus::Paga->value, InvoiceStatus::ParcialmentePaga->value], true))
                    || $allPurchasesPaid;

                if (! $isPaid && $dueDate->gte($now->startOfDay())) {
                    $payments->push([
                        'name' => $card->name,
                        'dueDate' => $dueDate->toISOString(),
                        'amount' => $total,
                        'type' => 'credit_card',
                    ]);
                    $found = true;
                }

                $checkMonth++;
                if ($checkMonth > 12) {
                    $checkMonth = 1;
                    $checkYear++;
                }
            }
        }

        $individualPurchases = $user->purchases()
            ->whereNull('card_id')
            ->get();

        foreach ($individualPurchases as $purchase) {
            $checkMonth = $month;
            $checkYear = $year;
            $found = false;

            for ($i = 0; $i < 12 && ! $found; $i++) {
                if (! $purchase->isActiveInMonth($checkYear, $checkMonth)) {
                    if ($purchase->is_recurring || ($purchase->installments_total && $checkMonth <= $purchase->start_date->month + $purchase->installments_total)) {
                        $checkMonth++;
                        if ($checkMonth > 12) {
                            $checkMonth = 1;
                            $checkYear++;
                        }

                        continue;
                    }
                    break;
                }

                $isPaid = $purchase->payments()
                    ->where('month', $checkMonth)
                    ->where('year', $checkYear)
                    ->exists();

                $paymentDay = $purchase->payment_day ?? $purchase->start_date->day;
                $dueDate = Carbon::create($checkYear, $checkMonth, (int) $paymentDay);

                if (! $isPaid && $dueDate->gte($now->startOfDay())) {
                    $payments->push([
                        'name' => $purchase->name,
                        'dueDate' => $dueDate->toISOString(),
                        'amount' => (float) $purchase->amount,
                        'type' => $purchase->type->value,
                    ]);
                    $found = true;
                }

                if (! $purchase->is_recurring && ! $purchase->installments_total) {
                    break;
                }

                $checkMonth++;
                if ($checkMonth > 12) {
                    $checkMonth = 1;
                    $checkYear++;
                }
            }
        }

        return $payments
            ->sortBy('dueDate')
            ->take($limit)
            ->values();
    }
}
