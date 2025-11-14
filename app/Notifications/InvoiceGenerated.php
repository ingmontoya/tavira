<?php

namespace App\Notifications;

use App\Notifications\Concerns\GeneratesTenantUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceGenerated extends Notification implements ShouldQueue
{
    use GeneratesTenantUrls, Queueable;

    public function __construct(
        private $invoice
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nueva Factura Generada - #'.$this->invoice->invoice_number)
            ->greeting('Estimado/a '.$notifiable->name)
            ->line('Se ha generado una nueva factura.')
            ->line('**Número de Factura:** '.$this->invoice->invoice_number)
            ->line('**Apartamento:** '.$this->invoice->apartment->number)
            ->line('**Período:** '.$this->getFormattedPeriod())
            ->line('**Total:** $'.number_format($this->invoice->total_amount, 2))
            ->line('**Fecha de Vencimiento:** '.$this->invoice->due_date->format('d/m/Y'))
            ->action('Ver Factura', $this->tenantRoute('invoices.show', $this->invoice->id))
            ->line('La factura está disponible para revisión y envío.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice_generated',
            'invoice_id' => $this->invoice->id,
            'invoice_number' => $this->invoice->invoice_number,
            'apartment_number' => $this->invoice->apartment->number,
            'total_amount' => $this->invoice->total_amount,
            'due_date' => $this->invoice->due_date,
            'period' => $this->getFormattedPeriod(),
            'message' => 'Nueva factura generada: '.$this->invoice->invoice_number,
            'action_url' => $this->tenantRoute('invoices.show', $this->invoice->id),
        ];
    }

    private function getFormattedPeriod(): string
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $monthNames[$this->invoice->billing_period_month].' '.$this->invoice->billing_period_year;
    }
}
