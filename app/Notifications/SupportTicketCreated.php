<?php

namespace App\Notifications;

use App\Models\SupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportTicketCreated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private SupportTicket $ticket
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevo Ticket de Soporte - #'.$this->ticket->id)
            ->greeting('¡Hola!')
            ->line('Se ha creado un nuevo ticket de soporte que requiere tu atención.')
            ->line('**Título:** '.$this->ticket->title)
            ->line('**Categoría:** '.ucfirst($this->ticket->category))
            ->line('**Prioridad:** '.ucfirst($this->ticket->priority))
            ->line('**Usuario:** '.$this->ticket->user->name)
            ->action('Ver Ticket', route('support.show', $this->ticket->id))
            ->line('Por favor revisa y responde al ticket cuando sea posible.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'title' => $this->ticket->title,
            'category' => $this->ticket->category,
            'priority' => $this->ticket->priority,
            'user_name' => $this->ticket->user->name,
            'message' => 'Nuevo ticket de soporte creado: '.$this->ticket->title,
            'action_url' => route('support.show', $this->ticket->id),
        ];
    }
}
