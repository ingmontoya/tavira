<?php

namespace App\Providers;

use App\Events\AccountingTransactionPosted;
use App\Events\InvoiceCreated;
use App\Events\LateFeeApplied;
use App\Events\PaymentReceived;
use App\Listeners\GenerateAccountingEntryFromInvoice;
use App\Listeners\GenerateAccountingEntryFromLateFee;
use App\Listeners\GenerateAccountingEntryFromPayment;
use App\Listeners\SendWelcomeEmail;
use App\Listeners\UpdateBudgetExecutionFromTransaction;
use App\Services\FeatureFlags\CentralApiDriver;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

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
            'expense' => \App\Models\Expense::class,
            'expense_payment' => \App\Models\Expense::class,
            'user' => \App\Models\User::class,
            'apartment' => \App\Models\Apartment::class,
            'resident' => \App\Models\Resident::class,
            'supplier' => \App\Models\Supplier::class,
        ]);

        // Registrar driver personalizado de Pennant
        Feature::extend('central_api', function ($app, $config) {
            return new CentralApiDriver;
        });

        Event::listen(Verified::class, SendWelcomeEmail::class);

        // Eventos contables
        Event::listen(InvoiceCreated::class, GenerateAccountingEntryFromInvoice::class);
        Event::listen(PaymentReceived::class, GenerateAccountingEntryFromPayment::class);
        Event::listen(LateFeeApplied::class, GenerateAccountingEntryFromLateFee::class);
        Event::listen(AccountingTransactionPosted::class, UpdateBudgetExecutionFromTransaction::class);
    }
}
