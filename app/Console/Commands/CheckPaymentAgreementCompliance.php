<?php

namespace App\Console\Commands;

use App\Models\PaymentAgreement;
use App\Models\PaymentAgreementInstallment;
use Illuminate\Console\Command;

class CheckPaymentAgreementCompliance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payment-agreements:check-compliance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check payment agreement compliance and update overdue installments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking payment agreement compliance...');

        // Update overdue installments
        $overdueInstallments = PaymentAgreementInstallment::where('status', 'pending')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdueInstallments as $installment) {
            $installment->markAsOverdue();
            $this->line("Marked installment {$installment->installment_number} as overdue for agreement {$installment->paymentAgreement->agreement_number}");
        }

        // Check agreement compliance
        $activeAgreements = PaymentAgreement::active()->get();

        foreach ($activeAgreements as $agreement) {
            $agreement->checkCompliance();

            if ($agreement->status === 'breached') {
                $this->warn("Agreement {$agreement->agreement_number} has been marked as breached");
            } elseif ($agreement->status === 'completed') {
                $this->info("Agreement {$agreement->agreement_number} has been completed");
            }
        }

        $this->info('Payment agreement compliance check completed.');

        return 0;
    }
}
