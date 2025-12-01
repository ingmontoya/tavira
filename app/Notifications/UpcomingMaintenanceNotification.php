<?php

namespace App\Notifications;

use App\Models\MaintenanceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UpcomingMaintenanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public MaintenanceRequest $maintenanceRequest
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $daysUntil = now()->diffInDays($this->maintenanceRequest->next_occurrence_date);

        return (new MailMessage)
            ->subject('Mantenimiento Próximo: '.$this->maintenanceRequest->title)
            ->greeting('¡Hola!')
            ->line('Te recordamos que tienes un mantenimiento programado próximamente.')
            ->line('**Título:** '.$this->maintenanceRequest->title)
            ->line('**Categoría:** '.$this->maintenanceRequest->maintenanceCategory->name)
            ->line('**Fecha programada:** '.$this->maintenanceRequest->next_occurrence_date->format('d/m/Y'))
            ->line('**Días restantes:** '.$daysUntil.' días')
            ->line('**Recurrencia:** '.$this->maintenanceRequest->getRecurrenceFrequencyLabel())
            ->when($this->maintenanceRequest->location, function ($mail) {
                return $mail->line('**Ubicación:** '.$this->maintenanceRequest->location);
            })
            ->when($this->maintenanceRequest->description, function ($mail) {
                return $mail->line('**Descripción:** '.$this->maintenanceRequest->description);
            })
            ->action('Ver Detalles', route('maintenance-requests.show', $this->maintenanceRequest))
            ->line('Por favor, asegúrate de estar preparado para este mantenimiento.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'maintenance_request_id' => $this->maintenanceRequest->id,
            'title' => $this->maintenanceRequest->title,
            'category' => $this->maintenanceRequest->maintenanceCategory->name,
            'next_occurrence_date' => $this->maintenanceRequest->next_occurrence_date->toDateString(),
            'days_until' => now()->diffInDays($this->maintenanceRequest->next_occurrence_date),
            'recurrence_frequency' => $this->maintenanceRequest->getRecurrenceFrequencyLabel(),
            'location' => $this->maintenanceRequest->location,
            'priority' => $this->maintenanceRequest->priority,
            'url' => route('maintenance-requests.show', $this->maintenanceRequest),
        ];
    }
}
