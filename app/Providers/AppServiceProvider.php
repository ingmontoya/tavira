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
use App\Listeners\SyncProvidersToNewTenant;
use App\Listeners\UpdateBudgetExecutionFromTransaction;
use App\Services\FeatureFlags\CentralApiDriver;
use Illuminate\Auth\Events\Verified;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;
use Stancl\Tenancy\Events\SyncedResourceSaved;
use Stancl\Tenancy\Events\TenantCreated;
use Stancl\Tenancy\Listeners\UpdateSyncedResource;

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
        // Force URL root from APP_URL in production/behind proxies
        // This ensures URL generation works correctly even outside HTTP requests
        if (config('app.url')) {
            $appUrl = config('app.url');
            \Illuminate\Support\Facades\URL::forceRootUrl($appUrl);

            // Also force scheme if APP_URL is https
            if (str_starts_with($appUrl, 'https://')) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }
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
        Event::listen(LateFeeApplied::class, GenerateAccountingEntryFromLateFee::class);
        Event::listen(AccountingTransactionPosted::class, UpdateBudgetExecutionFromTransaction::class);

        // Eventos de sincronización de recursos (Stancl Tenancy)
        // NOTA: SyncedResourceSaved ya está registrado en TenancyServiceProvider
        // Solo registramos el listener personalizado para nuevos tenants aquí
        Event::listen(TenantCreated::class, SyncProvidersToNewTenant::class);
    }
}
