<?php

namespace App\Services\ExogenousReporting;

use App\Models\ConjuntoConfig;
use App\Models\Expense;
use App\Models\ExogenousReport;
use App\Models\ExogenousReportConfiguration;
use App\Models\ExogenousReportItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExogenousReportGenerator
{
    /**
     * Generate an exogenous report for a given period and type
     */
    public function generateReport(
        int $conjuntoConfigId,
        string $reportType,
        int $fiscalYear,
        ?Carbon $periodStart = null,
        ?Carbon $periodEnd = null,
        array $options = []
    ): ExogenousReport {
        // Default period to full fiscal year if not specified
        $periodStart = $periodStart ?? Carbon::create($fiscalYear, 1, 1);
        $periodEnd = $periodEnd ?? Carbon::create($fiscalYear, 12, 31);

        // Get or create configuration for this report type and year
        $configuration = ExogenousReportConfiguration::getOrCreateForYear(
            $conjuntoConfigId,
            $fiscalYear,
            $reportType
        );

        // Validate configuration
        $configValidation = $configuration->validate();
        if (! $configValidation['is_valid']) {
            $errors = implode(', ', $configValidation['errors']);
            throw new \Exception("La configuración del reporte no es válida: {$errors}");
        }

        return DB::transaction(function () use (
            $conjuntoConfigId,
            $reportType,
            $fiscalYear,
            $periodStart,
            $periodEnd,
            $configuration,
            $options
        ) {
            // Create the report
            $report = ExogenousReport::create([
                'conjunto_config_id' => $conjuntoConfigId,
                'report_type' => $reportType,
                'report_name' => $configuration->report_type_label,
                'fiscal_year' => $fiscalYear,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'status' => 'draft',
            ]);

            // Generate items based on report type
            $itemsGenerated = match ($reportType) {
                '1001' => $this->generateFormat1001Items($report, $configuration, $periodStart, $periodEnd, $options),
                '1003' => $this->generateFormat1003Items($report, $configuration, $periodStart, $periodEnd, $options),
                '1005' => $this->generateFormat1005Items($report, $configuration, $periodStart, $periodEnd, $options),
                '1647' => $this->generateFormat1647Items($report, $configuration, $periodStart, $periodEnd, $options),
                default => throw new \Exception("Tipo de reporte no soportado: {$reportType}"),
            };

            // Calculate totals
            $report->calculateTotals();

            // Mark as generated
            $report->markAsGenerated();

            Log::info('Exogenous report generated', [
                'report_id' => $report->id,
                'report_number' => $report->report_number,
                'report_type' => $reportType,
                'fiscal_year' => $fiscalYear,
                'items_generated' => $itemsGenerated,
                'total_amount' => $report->total_amount,
                'total_withholding' => $report->total_withholding,
            ]);

            return $report->fresh(['items', 'conjuntoConfig']);
        });
    }

    /**
     * Generate Format 1001 items: Payments and withholdings to third parties
     */
    private function generateFormat1001Items(
        ExogenousReport $report,
        ExogenousReportConfiguration $configuration,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $options
    ): int {
        // Get all paid expenses in the period
        $expenses = Expense::forConjunto($report->conjunto_config_id)
            ->paid()
            ->byPeriod($periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d'))
            ->whereNotNull('provider_id')
            ->with(['provider', 'debitAccount', 'taxAccount'])
            ->get();

        // Group by provider and calculate totals
        $providerTotals = $expenses->groupBy('provider_id')->map(function ($providerExpenses) {
            return [
                'payment_amount' => $providerExpenses->sum('total_amount'),
                'withholding_amount' => $providerExpenses->sum('tax_amount'),
                'expenses' => $providerExpenses,
            ];
        });

        $itemsCreated = 0;
        $threshold = $configuration->minimum_reporting_amount;

        foreach ($providerTotals as $providerId => $totals) {
            // Apply threshold filter unless configured otherwise
            if (! $configuration->include_amounts_below_threshold && $totals['payment_amount'] < $threshold) {
                continue;
            }

            // Get the first expense to extract provider information
            $firstExpense = $totals['expenses']->first();

            // Create a single aggregated item per provider for Format 1001
            ExogenousReportItem::createFromExpense($report, $firstExpense);
            $itemsCreated++;
        }

        return $itemsCreated;
    }

    /**
     * Generate Format 1003 items: Withholdings at source
     */
    private function generateFormat1003Items(
        ExogenousReport $report,
        ExogenousReportConfiguration $configuration,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $options
    ): int {
        // Get all expenses with withholdings in the period
        $expenses = Expense::forConjunto($report->conjunto_config_id)
            ->paid()
            ->byPeriod($periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d'))
            ->whereNotNull('provider_id')
            ->where('tax_amount', '>', 0)
            ->with(['provider', 'debitAccount', 'taxAccount'])
            ->get();

        $itemsCreated = 0;

        foreach ($expenses as $expense) {
            // For format 1003, we report individual withholdings
            // regardless of threshold (withholdings must always be reported)
            ExogenousReportItem::createFromExpense($report, $expense);
            $itemsCreated++;
        }

        return $itemsCreated;
    }

    /**
     * Generate Format 1005 items: Income received for third parties
     * For conjuntos: administration fees and common area charges
     */
    private function generateFormat1005Items(
        ExogenousReport $report,
        ExogenousReportConfiguration $configuration,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $options
    ): int {
        // Format 1005 is less relevant for residential complexes
        // Would need to query invoices/payments if implemented
        Log::info('Format 1005 generation requested but not fully implemented', [
            'report_id' => $report->id,
        ]);

        return 0;
    }

    /**
     * Generate Format 1647 items: Special 1.5% withholdings
     */
    private function generateFormat1647Items(
        ExogenousReport $report,
        ExogenousReportConfiguration $configuration,
        Carbon $periodStart,
        Carbon $periodEnd,
        array $options
    ): int {
        // Get expenses with 1.5% withholding rate
        $expenses = Expense::forConjunto($report->conjunto_config_id)
            ->paid()
            ->byPeriod($periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d'))
            ->whereNotNull('provider_id')
            ->where('tax_amount', '>', 0)
            ->with(['provider', 'debitAccount', 'taxAccount'])
            ->get()
            ->filter(function ($expense) {
                // Calculate withholding rate
                if ($expense->tax_amount > 0 && $expense->subtotal > 0) {
                    $rate = ($expense->tax_amount / $expense->subtotal) * 100;

                    return abs($rate - 1.5) < 0.1; // Within 0.1% of 1.5%
                }

                return false;
            });

        $itemsCreated = 0;

        foreach ($expenses as $expense) {
            ExogenousReportItem::createFromExpense($report, $expense);
            $itemsCreated++;
        }

        return $itemsCreated;
    }

    /**
     * Preview report items without creating the report
     * Useful for UI preview before generation
     */
    public function previewReport(
        int $conjuntoConfigId,
        string $reportType,
        int $fiscalYear,
        ?Carbon $periodStart = null,
        ?Carbon $periodEnd = null
    ): array {
        $periodStart = $periodStart ?? Carbon::create($fiscalYear, 1, 1);
        $periodEnd = $periodEnd ?? Carbon::create($fiscalYear, 12, 31);

        $configuration = ExogenousReportConfiguration::getOrCreateForYear(
            $conjuntoConfigId,
            $fiscalYear,
            $reportType
        );

        // Get expenses that would be included
        $expenses = Expense::forConjunto($conjuntoConfigId)
            ->paid()
            ->byPeriod($periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d'))
            ->whereNotNull('provider_id')
            ->with(['provider', 'debitAccount'])
            ->get();

        $threshold = $configuration->minimum_reporting_amount;

        // Group by provider
        $providerTotals = $expenses->groupBy('provider_id')->map(function ($providerExpenses) use ($threshold, $reportType) {
            $paymentAmount = $providerExpenses->sum('total_amount');
            $withholdingAmount = $providerExpenses->sum('tax_amount');

            // Determine if would be included
            $wouldBeIncluded = match ($reportType) {
                '1003' => $withholdingAmount > 0, // Only withholdings
                '1647' => $withholdingAmount > 0 && $this->hasRate($providerExpenses, 1.5),
                default => $paymentAmount >= $threshold, // Format 1001
            };

            return [
                'provider' => $providerExpenses->first()->provider,
                'payment_amount' => $paymentAmount,
                'withholding_amount' => $withholdingAmount,
                'transaction_count' => $providerExpenses->count(),
                'would_be_included' => $wouldBeIncluded,
            ];
        })->values();

        $includedItems = $providerTotals->where('would_be_included', true);

        return [
            'total_providers' => $providerTotals->count(),
            'included_providers' => $includedItems->count(),
            'excluded_providers' => $providerTotals->count() - $includedItems->count(),
            'total_payment_amount' => $includedItems->sum('payment_amount'),
            'total_withholding_amount' => $includedItems->sum('withholding_amount'),
            'threshold' => $threshold,
            'period_start' => $periodStart->format('Y-m-d'),
            'period_end' => $periodEnd->format('Y-m-d'),
            'preview_items' => $includedItems->take(10)->toArray(), // Show first 10
        ];
    }

    private function hasRate($expenses, float $targetRate): bool
    {
        foreach ($expenses as $expense) {
            if ($expense->tax_amount > 0 && $expense->subtotal > 0) {
                $rate = ($expense->tax_amount / $expense->subtotal) * 100;
                if (abs($rate - $targetRate) < 0.1) {
                    return true;
                }
            }
        }

        return false;
    }
}
