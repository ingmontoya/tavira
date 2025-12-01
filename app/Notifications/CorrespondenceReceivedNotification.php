<?php

namespace App\Notifications;

use App\Models\Correspondence;
use App\Notifications\Concerns\GeneratesTenantUrls;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CorrespondenceReceivedNotification extends Notification
{
    use GeneratesTenantUrls;

    public function __construct(
        private Correspondence $correspondence,
        private ?string $recipientName = null
    ) {}

    public function via(object $notifiable): array
    {
        // Only send email for on-demand notifications (from Notification facade)
        // Skip database notifications as we're not using a User model
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $recipientName = $this->recipientName ?? 'Residente';

        return (new MailMessage)
            ->subject('Nueva Correspondencia Recibida - '.$this->correspondence->tracking_number)
            ->greeting('Estimado/a '.$recipientName)
            ->line('Se ha recibido nueva correspondencia para su apartamento.')
            ->line('**Número de Seguimiento:** '.$this->correspondence->tracking_number)
            ->line('**Tipo:** '.$this->getTypeLabel($this->correspondence->type))
            ->line('**Remitente:** '.$this->correspondence->sender_name.($this->correspondence->sender_company ? ' - '.$this->correspondence->sender_company : ''))
            ->line('**Descripción:** '.$this->correspondence->description)
            ->line('**Apartamento:** '.$this->correspondence->apartment->tower.'-'.$this->correspondence->apartment->number)
            ->line('**Fecha de Recepción:** '.$this->correspondence->received_at->format('d/m/Y H:i'))
            ->action('Ver Correspondencia', $this->tenantRoute('correspondence.show', $this->correspondence->id))
            ->line('Por favor, pase por portería a recoger su correspondencia en horario de atención.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'correspondence_received',
            'correspondence_id' => $this->correspondence->id,
            'tracking_number' => $this->correspondence->tracking_number,
            'sender_name' => $this->correspondence->sender_name,
            'correspondence_type' => $this->correspondence->type,
            'apartment_number' => $this->correspondence->apartment->tower.'-'.$this->correspondence->apartment->number,
            'received_at' => $this->correspondence->received_at,
            'message' => 'Nueva correspondencia recibida: '.$this->correspondence->tracking_number,
            'action_url' => $this->tenantRoute('correspondence.show', $this->correspondence->id),
        ];
    }

    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'package' => 'Paquete',
            'letter' => 'Carta',
            'document' => 'Documento',
            'other' => 'Otro',
            default => $type,
        };
    }
}
