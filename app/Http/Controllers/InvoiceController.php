<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

final readonly class InvoiceController
{
    public function markAsPaid(Invoice $invoice): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $invoice->update(['status' => 'paga', 'paid_at' => now()]);

        return to_route('purchases.index')->with('flash', ['message' => 'Fatura marcada como paga!', 'type' => 'success']);
    }

    public function unmarkAsPaid(Invoice $invoice): RedirectResponse
    {
        Gate::authorize('update', $invoice);

        $invoice->update(['status' => 'fechada', 'paid_at' => null]);

        return to_route('purchases.index')->with('flash', ['message' => 'Pagamento desmarcado!', 'type' => 'success']);
    }
}
