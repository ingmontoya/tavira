<?php

namespace App\Notifications;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TenantCredentialsCreated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $tenant;

    protected $tempPassword;

    protected $tenantDomain;

    /**
     * Create a new notification instance.
     */
    public function __construct(Tenant $tenant, string $tempPassword, string $tenantDomain)
    {
        $this->tenant = $tenant;
        $this->tempPassword = $tempPassword;
        $this->tenantDomain = $tenantDomain;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $tenantData = $this->tenant->data ?? [];
        $tenantName = $tenantData['name'] ?? 'Sin nombre';

        return (new MailMessage)
            ->subject('🎉 Tu conjunto ha sido creado exitosamente - Tavira')
            ->greeting("¡Hola {$notifiable->name}!")
            ->line("Tu conjunto **{$tenantName}** ha sido creado exitosamente y ya está listo para usar.")
            ->line('## Credenciales de acceso')
            ->line("**URL de acceso:** https://{$this->tenantDomain}")
            ->line("**Usuario:** {$this->tenant->admin_email}")
            ->line("**Contraseña temporal:** `{$this->tempPassword}`")
            ->line('---')
            ->line('**⚠️ IMPORTANTE:** Esta contraseña es temporal y debes cambiarla en tu primer inicio de sesión por seguridad.')
            ->action('Acceder a mi conjunto', "https://{$this->tenantDomain}/login")
            ->line('## Próximos pasos')
            ->line('1. **Inicia sesión** con las credenciales proporcionadas')
            ->line('2. **Cambia tu contraseña** por una nueva y segura')
            ->line('3. **Configura tu conjunto** con la información básica')
            ->line('4. **Agrega residentes** y apartamentos')
            ->line('---')
            ->line('Si tienes alguna pregunta o necesitas ayuda, no dudes en contactarnos.')
            ->salutation('¡Bienvenido a Tavira! 🏢');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tenant_id' => $this->tenant->id,
            'tenant_domain' => $this->tenantDomain,
            'type' => 'tenant_credentials_created',
            'message' => 'Credenciales del conjunto enviadas por correo',
        ];
    }
}
