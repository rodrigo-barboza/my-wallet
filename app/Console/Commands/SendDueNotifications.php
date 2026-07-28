<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\Purchase;
use App\Notifications\InvoiceClosingNotification;
use App\Notifications\InvoiceDueNotification;
use App\Notifications\PurchasePaymentDueNotification;
use Illuminate\Console\Command;

final class SendDueNotifications extends Command
{
    protected $signature = 'notifications:send';

    protected $description = 'Send notification emails for invoices closing, invoices due, and purchases due today';

    public function handle(): int
    {
        $this->sendInvoiceClosingNotifications();
        $this->sendInvoiceDueNotifications();
        $this->sendPurchaseDueNotifications();

        $this->info('Notifications sent successfully.');

        return self::SUCCESS;
    }

    private function sendInvoiceClosingNotifications(): void
    {
        $invoices = Invoice::query()
            ->whereDate('closing_date', today())
            ->whereHas('card', fn ($q) => $q->where('notify_closing', true))
            ->with('card.user')
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->card->user->notify(new InvoiceClosingNotification($invoice));
        }

        $this->info("Closing invoices: {$invoices->count()} notification(s) sent.");
    }

    private function sendInvoiceDueNotifications(): void
    {
        $invoices = Invoice::query()
            ->whereDate('due_date', today())
            ->whereHas('card', fn ($q) => $q->where('notify_due', true))
            ->with('card.user')
            ->get();

        foreach ($invoices as $invoice) {
            $invoice->card->user->notify(new InvoiceDueNotification($invoice));
        }

        $this->info("Due invoices: {$invoices->count()} notification(s) sent.");
    }

    private function sendPurchaseDueNotifications(): void
    {
        $now = now();

        $purchases = Purchase::query()
            ->whereNull('card_id')
            ->where('payment_day', $now->day)
            ->where('notify_due', true)
            ->where(function ($q) use ($now) {
                $q->where('is_recurring', true)
                    ->orWhere(function ($q) use ($now) {
                        $q->where('is_recurring', false)
                            ->where('start_date', '<=', $now->startOfDay())
                            ->where(function ($q) use ($now) {
                                $q->whereNull('installments_total')
                                    ->orWhere(function ($q) use ($now) {
                                        $startYear = (int) $now->format('Y');
                                        $startMonth = (int) $now->format('m');
                                        $q->where('installments_total', '>', 0)
                                            ->whereRaw('(strftime("%Y", ?) - strftime("%Y", start_date)) * 12 + (strftime("%m", ?) - strftime("%m", start_date)) < installments_total', [$now, $now]);
                                    });
                            });
                    });
            })
            ->whereDoesntHave('payments', function ($q) use ($now) {
                $q->where('month', $now->month)->where('year', $now->year);
            })
            ->get();

        foreach ($purchases as $purchase) {
            $purchase->user->notify(new PurchasePaymentDueNotification($purchase));
        }

        $this->info("Due purchases: {$purchases->count()} notification(s) sent.");
    }
}
