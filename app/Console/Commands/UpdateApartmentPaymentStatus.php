<?php

namespace App\Console\Commands;

use App\Models\Apartment;
use Illuminate\Console\Command;

class UpdateApartmentPaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apartments:update-payment-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update apartment payment status and outstanding balances';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating apartment payment status...');

        $apartments = Apartment::with(['invoices' => function($query) {
            $query->whereIn('status', ['pending', 'partial', 'overdue'])
                  ->orderBy('due_date', 'desc');
        }])->get();
        
        $updated = 0;

        foreach ($apartments as $apartment) {
            $oldStatus = $apartment->payment_status;
            
            // Calculate payment status based on real invoices
            $apartment->updatePaymentStatus();

            if ($oldStatus !== $apartment->payment_status) {
                $updated++;
                $this->line("Updated apartment {$apartment->full_address}: {$oldStatus} → {$apartment->payment_status}");
            }
        }

        $this->info("Updated {$updated} apartments successfully.");

        return 0;
    }
}
