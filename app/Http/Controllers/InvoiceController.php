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

        $invoice->update(['status' => 'paga']);

        return back();
    }
}
