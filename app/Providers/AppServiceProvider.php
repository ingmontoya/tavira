<?php

namespace App\Providers;

use App\Events\InvoiceCreated;
use App\Events\PaymentReceived;
use App\Listeners\GenerateAccountingEntryFromInvoice;
use App\Listeners\GenerateAccountingEntryFromPayment;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Auth\Events\Verified;
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
        Event::listen(Verified::class, SendWelcomeEmail::class);
        
        // Eventos contables
        Event::listen(InvoiceCreated::class, GenerateAccountingEntryFromInvoice::class);
        Event::listen(PaymentReceived::class, GenerateAccountingEntryFromPayment::class);
    }
}
