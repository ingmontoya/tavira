<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Provider;
use App\Models\WithholdingCertificate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class WithholdingCertificateService
{
    public function generateForProvider(Provider $provider, int $year): ?WithholdingCertificate
    {
        // Get all expenses with withholding for this provider in the year
        $expenses = Expense::where('provider_id', $provider->id)
            ->whereYear('expense_date', $year)
            ->where('tax_amount', '>', 0)
            ->whereNotNull('tax_account_id')
            ->with(['taxAccount', 'expenseCategory'])
            ->get();

        if ($expenses->isEmpty()) {
            return null;
        }

        // Create certificate
        $certificate = WithholdingCertificate::create([
            'conjunto_config_id' => $provider->conjunto_config_id,
            'provider_id' => $provider->id,
            'year' => $year,
            'certificate_number' => $this->generateCertificateNumber($year),
            'total_base' => $expenses->sum('subtotal'),
            'total_retained' => $expenses->sum('tax_amount'),
            'issued_at' => now(),
            'issued_by' => auth()->id(),
        ]);

        // Create details
        foreach ($expenses as $expense) {
            $certificate->details()->create([
                'expense_id' => $expense->id,
                'retention_concept' => $expense->expenseCategory->name ?? 'Servicio',
                'base_amount' => $expense->subtotal,
                'retention_percentage' => ($expense->tax_amount / $expense->subtotal) * 100,
                'retained_amount' => $expense->tax_amount,
                'retention_account_code' => $expense->taxAccount->code,
            ]);
        }

        // Generate PDF
        $this->generatePDF($certificate);

        return $certificate;
    }

    private function generateCertificateNumber(int $year): string
    {
        $lastCertificate = WithholdingCertificate::whereYear('issued_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastCertificate ? intval(substr($lastCertificate->certificate_number, -4)) + 1 : 1;

        return sprintf('CERT-RET-%d-%04d', $year, $sequence);
    }

    public function generatePDF(WithholdingCertificate $certificate): void
    {
        $pdf = Pdf::loadView('pdfs.withholding-certificate', [
            'certificate' => $certificate->load(['provider', 'details.expense', 'conjuntoConfig']),
        ]);

        $path = "certificates/retenciones/{$certificate->year}/{$certificate->certificate_number}.pdf";
        Storage::put($path, $pdf->output());

        $certificate->update(['pdf_path' => $path]);
    }

    public function downloadPDF(WithholdingCertificate $certificate)
    {
        if (! $certificate->pdf_path || ! Storage::exists($certificate->pdf_path)) {
            $this->generatePDF($certificate);
        }

        return Storage::download($certificate->pdf_path, "{$certificate->certificate_number}.pdf");
    }
}
