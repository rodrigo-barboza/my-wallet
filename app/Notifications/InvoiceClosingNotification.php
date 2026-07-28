<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class InvoiceClosingNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Invoice $invoice,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $card = $this->invoice->card;

        return (new MailMessage)
            ->subject("Fatura do cartão {$card->name} fecha hoje")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("A fatura do cartão **{$card->name}** referente a {$this->invoice->month}/{$this->invoice->year} fecha hoje ({$this->invoice->closing_date->format('d/m/Y')}).")
            ->action('Ver compras', route('cards.purchases', $card))
            ->line('Acesse sua carteira para mais detalhes.');
    }
}
