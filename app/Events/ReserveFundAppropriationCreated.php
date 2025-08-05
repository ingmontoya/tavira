<?php

namespace App\Events;

use App\Models\AccountingTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se crea una apropiación al fondo de reserva
 *
 * Permite integrar notificaciones, reportes automáticos y otras funcionalidades
 * sin afectar la funcionalidad core del sistema contable
 */
class ReserveFundAppropriationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AccountingTransaction $transaction;

    public int $month;

    public int $year;

    public float $appropriatedAmount;

    public float $monthlyIncome;

    /**
     * Create a new event instance.
     */
    public function __construct(
        AccountingTransaction $transaction,
        int $month,
        int $year,
        float $appropriatedAmount,
        float $monthlyIncome
    ) {
        $this->transaction = $transaction;
        $this->month = $month;
        $this->year = $year;
        $this->appropriatedAmount = $appropriatedAmount;
        $this->monthlyIncome = $monthlyIncome;
    }
}
