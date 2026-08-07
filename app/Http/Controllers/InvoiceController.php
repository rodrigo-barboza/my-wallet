<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Purchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final readonly class InvoiceController
{
    public function markAsPaid(Invoice $invoice): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $total = $this->invoiceTotal($invoice);

        $invoice->update([
            'status' => 'paga',
            'paid_amount' => $total,
            'paid_at' => now(),
        ]);

        return to_route('purchases.index')->with('flash', ['message' => 'Fatura marcada como paga!', 'type' => 'success']);
    }

    public function unmarkAsPaid(Invoice $invoice): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $invoice->payments()->delete();
        $invoice->update([
            'status' => 'fechada',
            'paid_amount' => null,
            'paid_at' => null,
        ]);

        return to_route('purchases.index')->with('flash', ['message' => 'Pagamento desmarcado!', 'type' => 'success']);
    }

    private function invoiceTotal(Invoice $invoice): float
    {
        return (float) Purchase::where('card_id', $invoice->card_id)
            ->where('user_id', $invoice->user_id)
            ->get()
            ->filter(fn ($p) => $p->isActiveInMonth($invoice->year, $invoice->month))
            ->sum(fn ($p) => $p->installments_total
                ? round($p->amount / $p->installments_total, 2)
                : $p->amount
            );
    }
}
