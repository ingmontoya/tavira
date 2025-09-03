<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Tenant;
use App\Models\User;

class TenantApprovalRequest extends Notification implements ShouldQueue
{
    use Queueable;

    protected $tenant;
    protected $requestingUser;

    /**
     * Create a new notification instance.
     */
    public function __construct(Tenant $tenant, User $requestingUser)
    {
        $this->tenant = $tenant;
        $this->requestingUser = $requestingUser;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $tenantData = $this->tenant->data ?? [];
        
        return (new MailMessage)
            ->subject('Nueva Solicitud de Conjunto - Requiere Aprobación')
            ->line('Se ha creado una nueva solicitud de conjunto que requiere tu aprobación.')
            ->line("**Conjunto:** {$tenantData['name']}")
            ->line("**Email:** {$tenantData['email']}")
            ->line("**Solicitado por:** {$this->requestingUser->name} ({$this->requestingUser->email})")
            ->line("**Fecha:** " . now()->format('d/m/Y H:i'))
            ->action('Revisar Solicitud', url('/tenants/' . $this->tenant->id))
            ->line('Por favor revisa la solicitud y apruébala o recházala según corresponda.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $tenantData = $this->tenant->data ?? [];
        
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_name' => $tenantData['name'] ?? 'Sin nombre',
            'tenant_email' => $tenantData['email'] ?? null,
            'requesting_user_id' => $this->requestingUser->id,
            'requesting_user_name' => $this->requestingUser->name,
            'requesting_user_email' => $this->requestingUser->email,
            'type' => 'tenant_approval_request',
            'message' => "Nueva solicitud de conjunto: {$tenantData['name']}"
        ];
    }
}