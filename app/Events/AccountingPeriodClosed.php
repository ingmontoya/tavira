<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado cuando se completa un cierre contable mensual
 *
 * Permite integrar notificaciones, backups automáticos y otras funcionalidades
 * posteriores al cierre sin afectar la funcionalidad core
 */
class AccountingPeriodClosed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $conjuntoConfigId;

    public int $month;

    public int $year;

    public array $closingResults;

    /**
     * Create a new event instance.
     */
    public function __construct(int $conjuntoConfigId, int $month, int $year, array $closingResults)
    {
        $this->conjuntoConfigId = $conjuntoConfigId;
        $this->month = $month;
        $this->year = $year;
        $this->closingResults = $closingResults;
    }
}
