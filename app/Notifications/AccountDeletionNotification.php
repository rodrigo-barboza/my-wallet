<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class AccountDeletionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $confirmationUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirmação de exclusão de conta')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Recebemos uma solicitação para excluir sua conta e todos os dados associados.')
            ->line('Esta ação é irreversível. Todos os seus cartões, compras, entradas e demais dados serão permanentemente removidos.')
            ->action('Confirmar exclusão da conta', $this->confirmationUrl)
            ->line('Se você não solicitou esta exclusão, ignore este e-mail.');
    }
}
