<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentConcept;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SampleInvoicesSeeder extends Seeder
{
    public function run(): void
    {
        // Find Marolys' apartment
        $apartment = Apartment::whereHas('residents', function ($q) {
            $q->where('email', 'marolys@gmail.com');
        })->first();

        if (! $apartment) {
            $this->command->error('No se encontró apartamento para marolys@gmail.com');

            return;
        }

        // Get or create payment concepts
        $administrationConcept = PaymentConcept::firstOrCreate([
            'name' => 'Administración Mensual',
            'type' => 'monthly_administration',
            'is_active' => true,
        ]);

        $maintenanceConcept = PaymentConcept::firstOrCreate([
            'name' => 'Cuota Extraordinaria Mantenimiento',
            'type' => 'special',
            'is_active' => true,
        ]);

        // Create sample invoices for the last 6 months
        for ($i = 5; $i >= 0; $i--) {
            $billingDate = Carbon::now()->subMonths($i)->startOfMonth();
            $dueDate = $billingDate->copy()->addDays(10);

            // Check if invoice already exists for this period
            $existingInvoice = Invoice::where('apartment_id', $apartment->id)
                ->where('billing_period_year', $billingDate->year)
                ->where('billing_period_month', $billingDate->month)
                ->first();

            if ($existingInvoice) {
                continue; // Skip if already exists
            }

            // Determine status based on age
            $status = 'pendiente';
            $paidAmount = 0;

            if ($i > 2) { // Older invoices are paid
                $status = 'pagado';
                $paidAmount = 150000;
            } elseif ($i == 2) { // Partial payment
                $status = 'pago_parcial';
                $paidAmount = 100000;
            } elseif ($i == 1 && $dueDate->isPast()) { // Overdue
                $status = 'vencido';
            }

            $invoice = Invoice::create([
                'apartment_id' => $apartment->id,
                'type' => 'monthly',
                'billing_date' => $billingDate,
                'due_date' => $dueDate,
                'billing_period_year' => $billingDate->year,
                'billing_period_month' => $billingDate->month,
                'subtotal' => 150000,
                'early_discount' => 0,
                'late_fees' => $status === 'vencido' ? 15000 : 0,
                'total_amount' => $status === 'vencido' ? 165000 : 150000,
                'paid_amount' => $paidAmount,
                'balance_amount' => ($status === 'vencido' ? 165000 : 150000) - $paidAmount,
                'status' => $status,
                'last_payment_date' => $status === 'pagado' ? $billingDate->addDays(5) : null,
            ]);

            // Add invoice items
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'payment_concept_id' => $administrationConcept->id,
                'description' => 'Cuota de administración '.$billingDate->format('F Y'),
                'quantity' => 1,
                'unit_price' => 120000,
                'total_price' => 120000,
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'payment_concept_id' => $maintenanceConcept->id,
                'description' => 'Cuota extraordinaria para mantenimiento '.$billingDate->format('F Y'),
                'quantity' => 1,
                'unit_price' => 30000,
                'total_price' => 30000,
            ]);

            $this->command->info("Creada factura {$invoice->invoice_number} - {$billingDate->format('F Y')} - Estado: {$status}");
        }

        // Update apartment payment status
        $apartment->updatePaymentStatus();

        $this->command->info("Facturas de ejemplo creadas para {$apartment->full_address}");
        $this->command->info("Estado de pago del apartamento: {$apartment->payment_status}");
    }
}
