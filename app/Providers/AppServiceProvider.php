<?php

namespace App\Providers;

use App\Events\InvoiceCreated;
use App\Events\PaymentReceived;
use App\Events\AccountingTransactionPosted;
use App\Listeners\GenerateAccountingEntryFromInvoice;
use App\Listeners\GenerateAccountingEntryFromPayment;
use App\Listeners\UpdateBudgetExecutionFromTransaction;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configurar mapa de morfos para relaciones polimórficas
        Relation::enforceMorphMap([
            'invoice' => \App\Models\Invoice::class,
            'payment' => \App\Models\Payment::class,
            'user' => \App\Models\User::class,
            'apartment' => \App\Models\Apartment::class,
            'resident' => \App\Models\Resident::class,
        ]);

        Event::listen(Verified::class, SendWelcomeEmail::class);
        
        // Eventos contables
        Event::listen(InvoiceCreated::class, GenerateAccountingEntryFromInvoice::class);
        Event::listen(PaymentReceived::class, GenerateAccountingEntryFromPayment::class);
        Event::listen(AccountingTransactionPosted::class, UpdateBudgetExecutionFromTransaction::class);
    }
}
