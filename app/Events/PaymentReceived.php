<?php

namespace App\Events;

use App\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public float $paymentAmount,
        public ?string $paymentMethod = null,
        public ?string $paymentReference = null
    ) {}
}
