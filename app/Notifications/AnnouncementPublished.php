<?php

namespace App\Notifications;

use App\Notifications\Concerns\GeneratesTenantUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AnnouncementPublished extends Notification implements ShouldQueue
{
    use GeneratesTenantUrls, Queueable;

    public function __construct(
        private $announcement
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevo Comunicado: '.$this->announcement->title)
            ->greeting('Estimado/a '.$notifiable->name)
            ->line('Se ha publicado un nuevo comunicado.')
            ->line('**Título:** '.$this->announcement->title)
            ->line('**Prioridad:** '.$this->getPriorityLabel())
            ->line('**Publicado por:** '.$this->announcement->user->name)
            ->line('**Fecha:** '.$this->announcement->created_at->format('d/m/Y H:i'))
            ->when($this->announcement->requires_confirmation, function ($message) {
                return $message->line('**Este comunicado requiere confirmación de lectura.**');
            })
            ->action('Ver Comunicado', $this->tenantRoute('announcements.show', $this->announcement->id))
            ->line('Por favor, revise el comunicado completo.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'announcement_published',
            'announcement_id' => $this->announcement->id,
            'title' => $this->announcement->title,
            'priority' => $this->announcement->priority,
            'requires_confirmation' => $this->announcement->requires_confirmation,
            'published_by' => $this->announcement->user->name,
            'published_at' => $this->announcement->created_at,
            'message' => 'Nuevo comunicado: '.$this->announcement->title,
            'action_url' => $this->tenantRoute('announcements.show', $this->announcement->id),
        ];
    }

    private function getPriorityLabel(): string
    {
        return match ($this->announcement->priority) {
            'low' => 'Baja',
            'medium' => 'Media',
            'high' => 'Alta',
            'urgent' => 'Urgente',
            default => $this->announcement->priority,
        };
    }
}
