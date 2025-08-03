<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BudgetOverspendAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private array $alertData
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $period = $this->alertData['period'];
        $type = $this->alertData['type'];
        $executions = $this->alertData['executions'];
        
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        $periodName = $monthNames[$period['month']] . ' ' . $period['year'];
        $subject = $type === 'danger' 
            ? "🚨 Alerta: Sobrepresupuesto crítico - {$periodName}"
            : "⚠️ Advertencia: Cerca del límite presupuestal - {$periodName}";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting('Estimado/a ' . $notifiable->name)
            ->line("Se han detectado cuentas que exceden los límites presupuestales para {$periodName}:");

        foreach ($executions as $execution) {
            $varianceText = $execution['variance_percentage'] > 0 
                ? "sobrepresupuesto de {$execution['variance_percentage']}%"
                : "bajo presupuesto de " . abs($execution['variance_percentage']) . "%";
                
            $message->line("**{$execution['account_code']} - {$execution['account_name']}**")
                   ->line("- Presupuestado: $" . number_format($execution['budgeted_amount'], 2))
                   ->line("- Ejecutado: $" . number_format($execution['actual_amount'], 2))
                   ->line("- Variación: $" . number_format($execution['variance_amount'], 2) . " ({$varianceText})")
                   ->line('');
        }

        $message->line('Se recomienda revisar estas cuentas y tomar las medidas necesarias.')
               ->action('Ver Presupuesto', route('budgets.index'))
               ->line('Este es un mensaje automático del sistema de gestión presupuestal.');

        return $message;
    }

    public function toArray(object $notifiable): array
    {
        $period = $this->alertData['period'];
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];

        return [
            'type' => 'budget_overspend',
            'alert_type' => $this->alertData['type'],
            'period' => $period,
            'period_name' => $monthNames[$period['month']] . ' ' . $period['year'],
            'executions_count' => count($this->alertData['executions']),
            'executions' => $this->alertData['executions'],
            'title' => $this->alertData['type'] === 'danger' 
                ? 'Sobrepresupuesto crítico detectado'
                : 'Advertencia de límite presupuestal',
        ];
    }
}
