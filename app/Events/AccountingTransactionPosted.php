<?php

namespace App\Events;

use App\Models\AccountingTransaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccountingTransactionPosted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public AccountingTransaction $transaction;

    public function __construct(AccountingTransaction $transaction)
    {
        $this->transaction = $transaction;
    }
}