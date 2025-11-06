<?php

namespace App\Notifications;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Notifications\Concerns\GeneratesTenantUrls;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SupportMessageReceived extends Notification implements ShouldQueue
{
    use Queueable, GeneratesTenantUrls;

    public function __construct(
        private SupportTicket $ticket,
        private SupportMessage $message
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $isAdminReply = $this->message->is_admin_reply;
        $subject = $isAdminReply
            ? 'Respuesta a tu Ticket de Soporte - #'.$this->ticket->id
            : 'Nuevo Mensaje en Ticket de Soporte - #'.$this->ticket->id;

        return (new MailMessage)
            ->subject($subject)
            ->greeting('¡Hola!')
            ->line($isAdminReply
                ? 'Has recibido una respuesta a tu ticket de soporte.'
                : 'Se ha recibido un nuevo mensaje en el ticket de soporte.'
            )
            ->line('**Ticket:** '.$this->ticket->title)
            ->line('**De:** '.$this->message->user->name)
            ->line('**Mensaje:**')
            ->line($this->message->message)
            ->action('Ver Ticket Completo', $this->tenantRoute('support.show', $this->ticket->id))
            ->line('Puedes responder directamente desde la plataforma.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'message_id' => $this->message->id,
            'ticket_title' => $this->ticket->title,
            'sender_name' => $this->message->user->name,
            'is_admin_reply' => $this->message->is_admin_reply,
            'message' => $this->message->is_admin_reply
                ? 'Respuesta recibida en: '.$this->ticket->title
                : 'Nuevo mensaje en: '.$this->ticket->title,
            'action_url' => $this->tenantRoute('support.show', $this->ticket->id),
        ];
    }
}
