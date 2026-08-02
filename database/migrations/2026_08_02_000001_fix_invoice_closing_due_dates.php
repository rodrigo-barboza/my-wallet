<?php

declare(strict_types=1);

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Invoice::with('card')->each(function (Invoice $invoice): void {
            $card = $invoice->card;

            if (! $card) {
                return;
            }

            $invoiceDate = Carbon::create($invoice->year, $invoice->month, 1);
            $closingDate = $invoiceDate->copy()->day($card->closing_day);
            $dueDate = $invoiceDate->copy()->day($card->due_day);

            if ($card->closing_day > $card->due_day) {
                $dueDate->addMonth();
            } else {
                $closingDate->addMonth();
                $dueDate->addMonth();
            }

            $invoice->update([
                'closing_date' => $closingDate,
                'due_date' => $dueDate,
            ]);
        });
    }

    public function down(): void
    {
        // Data migration — no rollback needed
    }
};
