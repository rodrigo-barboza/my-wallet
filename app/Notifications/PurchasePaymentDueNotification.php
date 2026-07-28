<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Purchase;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PurchasePaymentDueNotification extends Notification
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

        return (new MailMessage)
            ->subject("Pagamento de {$this->purchase->name} vence hoje")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("O pagamento de **{$this->purchase->name}** no valor de R$ ".number_format((float) $this->purchase->amount, 2, ',', '.')." vence hoje ({$this->purchase->payment_day}/{$now->format('m/Y')}).")
            ->action('Ver detalhes', route('purchases.show', $this->purchase))
            ->line('Acesse sua carteira para mais detalhes.');
    }
}
