<?php

namespace App\Notifications;

use App\Notifications\Concerns\GeneratesTenantUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification implements ShouldQueue
{
    use Queueable, GeneratesTenantUrls;

    public function __construct(
        private $payment
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pago Registrado - $'.number_format($this->payment->amount, 2))
            ->greeting('Estimado/a '.$notifiable->name)
            ->line('Se ha registrado un nuevo pago en el sistema.')
            ->line('**Monto:** $'.number_format($this->payment->amount, 2))
            ->line('**Método de Pago:** '.$this->getPaymentMethodLabel())
            ->line('**Referencia:** '.$this->payment->reference)
            ->line('**Fecha:** '.$this->payment->payment_date->format('d/m/Y'))
            ->action('Ver Pago', $this->tenantRoute('finance.payments.show', $this->payment->id))
            ->line('El pago ha sido procesado correctamente.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment_received',
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'payment_method' => $this->payment->payment_method,
            'reference' => $this->payment->reference,
            'payment_date' => $this->payment->payment_date,
            'message' => 'Nuevo pago registrado: $'.number_format($this->payment->amount, 2),
            'action_url' => $this->tenantRoute('finance.payments.show', $this->payment->id),
        ];
    }

    private function getPaymentMethodLabel(): string
    {
        return match ($this->payment->payment_method) {
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia Bancaria',
            'consignacion' => 'Consignación',
            'tarjeta_credito' => 'Tarjeta de Crédito',
            'tarjeta_debito' => 'Tarjeta de Débito',
            'cheque' => 'Cheque',
            default => $this->payment->payment_method,
        };
    }
}
