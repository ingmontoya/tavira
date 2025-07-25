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

        $apartments = Apartment::all();
        $updated = 0;

        foreach ($apartments as $apartment) {
            $oldStatus = $apartment->payment_status;

            // Set some apartments as overdue for testing
            if ($apartment->id % 3 === 0) {
                $apartment->payment_status = 'overdue_30';
                $apartment->outstanding_balance = $apartment->monthly_fee * 1;
                $apartment->last_payment_date = now()->subMonths(2);
            } elseif ($apartment->id % 4 === 0) {
                $apartment->payment_status = 'overdue_60';
                $apartment->outstanding_balance = $apartment->monthly_fee * 2;
                $apartment->last_payment_date = now()->subMonths(3);
            } elseif ($apartment->id % 5 === 0) {
                $apartment->payment_status = 'overdue_90';
                $apartment->outstanding_balance = $apartment->monthly_fee * 3;
                $apartment->last_payment_date = now()->subMonths(4);
            } elseif ($apartment->id % 7 === 0) {
                $apartment->payment_status = 'overdue_90_plus';
                $apartment->outstanding_balance = $apartment->monthly_fee * 4;
                $apartment->last_payment_date = now()->subMonths(5);
            } else {
                // Leave some as current
                $apartment->payment_status = 'current';
                $apartment->outstanding_balance = 0;
                $apartment->last_payment_date = now()->subDays(15);
            }

            if ($oldStatus !== $apartment->payment_status) {
                $apartment->save();
                $updated++;
                $this->line("Updated apartment {$apartment->full_address}: {$oldStatus} → {$apartment->payment_status}");
            }
        }

        $this->info("Updated {$updated} apartments successfully.");

        return 0;
    }
}
