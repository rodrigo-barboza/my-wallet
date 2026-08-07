<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Purchase;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PurchasePaymentDueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Purchase $purchase,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $now = now();
        $valor = $this->purchase->installments_total
            ? round($this->purchase->amount / $this->purchase->installments_total, 2)
            : $this->purchase->amount;

        return (new MailMessage)
            ->subject("Pagamento de {$this->purchase->name} vence hoje")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("O pagamento de **{$this->purchase->name}** no valor de R$ ".number_format($valor, 2, ',', '.')." vence hoje ({$this->purchase->payment_day}/{$now->format('m/Y')}).")
            ->action('Ver detalhes', route('purchases.show', $this->purchase))
            ->line('Acesse sua carteira para mais detalhes.');
    }
}
