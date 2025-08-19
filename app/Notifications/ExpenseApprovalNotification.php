<?php

namespace App\Notifications;

use App\Models\Expense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Expense $expense,
        protected string $type = 'pending_approval'
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return match ($this->type) {
            'pending_approval' => $this->pendingApprovalMail($notifiable),
            'pending_council' => $this->pendingCouncilMail($notifiable),
            'approved' => $this->approvedMail($notifiable),
            'rejected' => $this->rejectedMail($notifiable),
            'overdue' => $this->overdueMail($notifiable),
            default => $this->pendingApprovalMail($notifiable),
        };
    }

    protected function pendingApprovalMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("📋 Gasto pendiente de aprobación - {$this->expense->expense_number}")
            ->greeting("Hola {$notifiable->name},")
            ->line('Hay un nuevo gasto pendiente de tu aprobación:')
            ->line("**Número:** {$this->expense->expense_number}")
            ->line("**Proveedor:** {$this->expense->getVendorDisplayName()}")
            ->line("**Concepto:** {$this->expense->description}")
            ->line('**Monto:** '.number_format($this->expense->total_amount, 0, ',', '.').' COP')
            ->line("**Categoría:** {$this->expense->expenseCategory->name}")
            ->line("**Fecha del gasto:** {$this->expense->expense_date->format('d/m/Y')}")
            ->line("**Creado por:** {$this->expense->createdBy->name}")
            ->action('Ver Gasto', url("/expenses/{$this->expense->id}"))
            ->line('Puedes aprobar o rechazar este gasto desde el panel de administración.')
            ->salutation('Saludos,')
            ->salutation('Sistema de Gestión Tavira');
    }

    protected function pendingCouncilMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🏛️ Gasto requiere aprobación del concejo - {$this->expense->expense_number}")
            ->greeting("Estimado miembro del concejo {$notifiable->name},")
            ->line('Un gasto de alto valor requiere aprobación del concejo:')
            ->line("**Número:** {$this->expense->expense_number}")
            ->line("**Proveedor:** {$this->expense->getVendorDisplayName()}")
            ->line("**Concepto:** {$this->expense->description}")
            ->line('**Monto:** '.number_format($this->expense->total_amount, 0, ',', '.').' COP')
            ->line("**Categoría:** {$this->expense->expenseCategory->name}")
            ->line("**Aprobado inicialmente por:** {$this->expense->approvedBy?->name}")
            ->line("**Fecha de aprobación inicial:** {$this->expense->approved_at?->format('d/m/Y H:i')}")
            ->action('Revisar y Aprobar', url("/expenses/{$this->expense->id}"))
            ->line('Este gasto supera el umbral de $4,000,000 COP y requiere tu aprobación.')
            ->salutation('Cordialmente,')
            ->salutation('Sistema de Gestión Tavira');
    }

    protected function approvedMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("✅ Gasto aprobado - {$this->expense->expense_number}")
            ->greeting("Hola {$notifiable->name},")
            ->line('Te informamos que tu gasto ha sido aprobado:')
            ->line("**Número:** {$this->expense->expense_number}")
            ->line("**Concepto:** {$this->expense->description}")
            ->line('**Monto:** '.number_format($this->expense->total_amount, 0, ',', '.').' COP')
            ->line("**Aprobado por:** {$this->expense->approvedBy?->name}")
            ->line("**Fecha de aprobación:** {$this->expense->approved_at?->format('d/m/Y H:i')}")
            ->action('Ver Gasto', url("/expenses/{$this->expense->id}"))
            ->line('El gasto está ahora listo para ser pagado.')
            ->salutation('Saludos,')
            ->salutation('Sistema de Gestión Tavira');
    }

    protected function rejectedMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("❌ Gasto rechazado - {$this->expense->expense_number}")
            ->greeting("Hola {$notifiable->name},")
            ->line('Lamentamos informarte que tu gasto ha sido rechazado:')
            ->line("**Número:** {$this->expense->expense_number}")
            ->line("**Concepto:** {$this->expense->description}")
            ->line('**Monto:** '.number_format($this->expense->total_amount, 0, ',', '.').' COP')
            ->action('Ver Gasto', url("/expenses/{$this->expense->id}"))
            ->line('Puedes revisar los comentarios y crear un nuevo gasto si es necesario.')
            ->salutation('Saludos,')
            ->salutation('Sistema de Gestión Tavira');
    }

    protected function overdueMail(object $notifiable): MailMessage
    {
        $daysOverdue = $this->expense->days_overdue;

        return (new MailMessage)
            ->subject("⚠️ Gasto vencido - {$this->expense->expense_number} ({$daysOverdue} días)")
            ->greeting("Atención {$notifiable->name},")
            ->line('El siguiente gasto está vencido y requiere pago urgente:')
            ->line("**Número:** {$this->expense->expense_number}")
            ->line("**Proveedor:** {$this->expense->getVendorDisplayName()}")
            ->line("**Concepto:** {$this->expense->description}")
            ->line('**Monto:** '.number_format($this->expense->total_amount, 0, ',', '.').' COP')
            ->line("**Fecha de vencimiento:** {$this->expense->due_date?->format('d/m/Y')}")
            ->line("**Días vencido:** {$daysOverdue} días")
            ->action('Marcar como Pagado', url("/expenses/{$this->expense->id}"))
            ->line('Por favor, procede con el pago lo antes posible.')
            ->salutation('Urgente,')
            ->salutation('Sistema de Gestión Tavira');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'expense_id' => $this->expense->id,
            'expense_number' => $this->expense->expense_number,
            'type' => $this->type,
            'amount' => $this->expense->total_amount,
            'vendor' => $this->expense->getVendorDisplayName(),
            'description' => $this->expense->description,
        ];
    }
}
