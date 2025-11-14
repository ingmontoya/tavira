<?php

namespace App\Services\ExogenousReporting;

use App\Models\ExogenousReport;
use App\Models\ExogenousReportItem;

class ExogenousReportValidationService
{
    /**
     * Validate a complete exogenous report
     */
    public function validateReport(ExogenousReport $report): array
    {
        $errors = [];
        $warnings = [];

        // Validate report has items
        if ($report->total_items === 0) {
            $errors[] = 'El reporte no contiene items';

            return [
                'is_valid' => false,
                'errors' => $errors,
                'warnings' => $warnings,
            ];
        }

        // Validate each item
        $itemErrors = [];
        $itemWarnings = [];

        foreach ($report->items as $index => $item) {
            $itemValidation = $item->validate();

            if (! $itemValidation['is_valid']) {
                foreach ($itemValidation['errors'] as $error) {
                    $itemNumber = $index + 1;
                    $itemErrors[] = "Item #{$itemNumber} ({$item->third_party_name}): {$error}";
                }
            }
        }

        if (! empty($itemErrors)) {
            $errors = array_merge($errors, $itemErrors);
        }

        // Validate totals match
        $calculatedPaymentTotal = $report->items()->sum('payment_amount');
        $calculatedWithholdingTotal = $report->items()->sum('withholding_amount');

        if (abs($calculatedPaymentTotal - $report->total_amount) > 0.01) {
            $errors[] = 'El total de pagos no coincide con la suma de items';
        }

        if (abs($calculatedWithholdingTotal - $report->total_withholding) > 0.01) {
            $errors[] = 'El total de retenciones no coincide con la suma de items';
        }

        // Check for duplicate third parties (warning only)
        $duplicates = $report->items
            ->groupBy('third_party_document_number')
            ->filter(function ($group) {
                return $group->count() > 1;
            });

        if ($duplicates->isNotEmpty()) {
            foreach ($duplicates as $documentNumber => $items) {
                $warnings[] = "El tercero con documento {$documentNumber} aparece {$items->count()} veces. Considere consolidar.";
            }
        }

        // Specific validations per report type
        $typeValidation = $this->validateByReportType($report);
        $errors = array_merge($errors, $typeValidation['errors']);
        $warnings = array_merge($warnings, $typeValidation['warnings']);

        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
            'validated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Validate based on specific report type requirements
     */
    private function validateByReportType(ExogenousReport $report): array
    {
        $errors = [];
        $warnings = [];

        switch ($report->report_type) {
            case '1001':
                // Format 1001: Payments and withholdings
                // All items should have payment amounts
                $itemsWithoutPayment = $report->items()->where('payment_amount', '<=', 0)->count();
                if ($itemsWithoutPayment > 0) {
                    $errors[] = "Formato 1001: {$itemsWithoutPayment} items sin monto de pago";
                }
                break;

            case '1003':
                // Format 1003: Withholdings only
                // All items should have withholding amounts
                $itemsWithoutWithholding = $report->items()->where('withholding_amount', '<=', 0)->count();
                if ($itemsWithoutWithholding > 0) {
                    $errors[] = "Formato 1003: {$itemsWithoutWithholding} items sin retención";
                }

                // Validate withholding rates
                foreach ($report->items as $item) {
                    if ($item->withholding_rate && ($item->withholding_rate < 0 || $item->withholding_rate > 100)) {
                        $errors[] = "Item {$item->third_party_name}: Tarifa de retención inválida ({$item->withholding_rate}%)";
                    }
                }
                break;

            case '1647':
                // Format 1647: 1.5% withholdings
                // All items should have 1.5% rate (approximately)
                foreach ($report->items as $item) {
                    if ($item->withholding_rate && abs($item->withholding_rate - 1.5) > 0.2) {
                        $warnings[] = "Item {$item->third_party_name}: Tarifa esperada 1.5%, encontrada {$item->withholding_rate}%";
                    }
                }
                break;
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Check if report meets DIAN file format requirements
     */
    public function validateForExport(ExogenousReport $report, string $format = 'xml'): array
    {
        $validation = $this->validateReport($report);

        if (! $validation['is_valid']) {
            return $validation;
        }

        $errors = $validation['errors'];
        $warnings = $validation['warnings'];

        // Additional validations for export
        $config = $report->conjuntoConfig;

        // Validate we have entity information from configuration
        $reportConfig = \App\Models\ExogenousReportConfiguration::forConjunto($report->conjunto_config_id)
            ->byFiscalYear($report->fiscal_year)
            ->byReportType($report->report_type)
            ->first();

        if (! $reportConfig) {
            $errors[] = 'No existe configuración para este tipo de reporte y año fiscal';
        } else {
            $configValidation = $reportConfig->validate();
            if (! $configValidation['is_valid']) {
                $errors = array_merge($errors, $configValidation['errors']);
            }
            if (! empty($configValidation['warnings'])) {
                $warnings = array_merge($warnings, $configValidation['warnings']);
            }
        }

        // Format-specific validations
        if ($format === 'xml') {
            // Validate special characters that might break XML
            foreach ($report->items as $item) {
                if (preg_match('/[<>&"\']/', $item->third_party_name)) {
                    $warnings[] = "Item {$item->third_party_name}: Contiene caracteres especiales que serán escapados en XML";
                }
            }
        }

        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
            'validated_for_export' => true,
            'export_format' => $format,
        ];
    }
}
