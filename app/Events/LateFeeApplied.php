<?php

namespace App\Events;

use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LateFeeApplied
{
    use Dispatchable, SerializesModels;

    public Invoice $invoice;

    public float $lateFeeAmount;

    public Carbon $processDate;

    public string $month;

    public function __construct(Invoice $invoice, float $lateFeeAmount, Carbon $processDate)
    {
        $this->invoice = $invoice;
        $this->lateFeeAmount = $lateFeeAmount;
        $this->processDate = $processDate;
        $this->month = $processDate->format('Y-m');
    }
}
