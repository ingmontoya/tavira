<?php

namespace App\Providers;

use App\Events\AccountingTransactionPosted;
use App\Events\PaymentReceived;
use App\Listeners\GenerateAccountingEntryFromPayment;
use App\Listeners\SendWelcomeEmail;
use App\Listeners\UpdateBudgetExecutionFromTransaction;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

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
            'payment_application' => \App\Models\PaymentApplication::class,
            'payment_application_reversal' => \App\Models\PaymentApplication::class,
            'user' => \App\Models\User::class,
            'apartment' => \App\Models\Apartment::class,
            'resident' => \App\Models\Resident::class,
        ]);

        Event::listen(Verified::class, SendWelcomeEmail::class);

        // Eventos contables - InvoiceCreated is handled by model $dispatchesEvents
        Event::listen(PaymentReceived::class, GenerateAccountingEntryFromPayment::class);
        Event::listen(AccountingTransactionPosted::class, UpdateBudgetExecutionFromTransaction::class);
    }
}
