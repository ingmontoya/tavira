<?php

namespace App\Providers;

use App\Events\AccountingTransactionPosted;
use App\Events\CorrespondenceReceived;
use App\Events\InvoiceCreated;
use App\Events\LateFeeApplied;
use App\Events\PaymentReceived;
use App\Listeners\GenerateAccountingEntryFromInvoice;
use App\Listeners\GenerateAccountingEntryFromLateFee;
use App\Listeners\GenerateAccountingEntryFromPayment;
use App\Listeners\SendCorrespondenceNotification;
use App\Listeners\SendWelcomeEmail;
use App\Listeners\SyncProvidersToNewTenant;
use App\Listeners\UpdateBudgetExecutionFromTransaction;
use App\Listeners\UpdateExtraordinaryAssessmentProgress;
use App\Models\ConjuntoConfig;
use App\Observers\ConjuntoConfigObserver;
use App\Services\FeatureFlags\CentralApiDriver;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Stancl\Tenancy\Events\SyncedResourceSaved;
use Stancl\Tenancy\Events\TenantCreated;

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
        // Force HTTPS scheme in production and staging
        // The TrustProxies middleware handles X-Forwarded-Host for proper domain detection
        if (in_array(config('app.env'), ['production', 'staging'])) {
            \Illuminate\Support\Facades\URL::forceScheme('https');

            // IMPORTANT: Do NOT force root URL here because it breaks tenant domains
            // The TrustProxies middleware will correctly detect the domain from X-Forwarded-Host
            // Only the HTTPS scheme needs to be forced
        }

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
        Event::listen(PaymentReceived::class, UpdateExtraordinaryAssessmentProgress::class);
        Event::listen(LateFeeApplied::class, GenerateAccountingEntryFromLateFee::class);
        Event::listen(AccountingTransactionPosted::class, UpdateBudgetExecutionFromTransaction::class);

        // Eventos de correspondencia
        Event::listen(CorrespondenceReceived::class, SendCorrespondenceNotification::class);

        // Eventos de sincronización de recursos (Stancl Tenancy)
        // NOTA: SyncedResourceSaved ya está registrado en TenancyServiceProvider
        // Solo registramos el listener personalizado para nuevos tenants aquí
        Event::listen(TenantCreated::class, SyncProvidersToNewTenant::class);

        // Observers
        ConjuntoConfig::observe(ConjuntoConfigObserver::class);
    }
}
