<?php

namespace App\Services\ExogenousReporting;

use App\Models\ExogenousReport;
use App\Models\ExogenousReportConfiguration;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ExogenousReportExporter
{
    private ExogenousReportValidationService $validator;

    public function __construct(ExogenousReportValidationService $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Export report to specified format
     */
    public function export(ExogenousReport $report, string $format = 'xml'): array
    {
        // Validate report before export
        $validation = $this->validator->validateForExport($report, $format);

        if (! $validation['is_valid']) {
            throw new \Exception('El reporte no pasó las validaciones de exportación: '.implode(', ', $validation['errors']));
        }

        // Export based on format
        $result = match ($format) {
            'xml' => $this->exportToXml($report),
            'txt' => $this->exportToText($report),
            'excel' => $this->exportToExcel($report),
            default => throw new \Exception("Formato de exportación no soportado: {$format}"),
        };

        // Mark report as exported
        $report->markAsExported($result['file_path'], $format);

        return $result;
    }

    /**
     * Export to XML format (DIAN standard)
     */
    private function exportToXml(ExogenousReport $report): array
    {
        $config = $this->getReportConfiguration($report);

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><InformacionExogena></InformacionExogena>');

        // Add header information
        $header = $xml->addChild('Cabecera');
        $header->addChild('TipoFormato', $report->report_type);
        $header->addChild('VersionFormato', '1.0');
        $header->addChild('NumeroEnvio', '1');
        $header->addChild('FechaEnvio', now()->format('Y-m-d'));
        $header->addChild('FechaInicio', $report->period_start->format('Y-m-d'));
        $header->addChild('FechaFin', $report->period_end->format('Y-m-d'));

        // Reporting entity information
        $reportante = $header->addChild('Reportante');
        $reportante->addChild('TipoDocumento', $config->entity_document_type);
        $reportante->addChild('NumeroIdentificacion', $config->entity_document_number);
        if ($config->entity_verification_digit) {
            $reportante->addChild('DigitoVerificacion', $config->entity_verification_digit);
        }
        $reportante->addChild('RazonSocial', htmlspecialchars($config->entity_name));
        if ($config->entity_address) {
            $reportante->addChild('Direccion', htmlspecialchars($config->entity_address));
        }
        if ($config->entity_city) {
            $reportante->addChild('Ciudad', htmlspecialchars($config->entity_city));
        }

        // Items
        $items = $xml->addChild('Items');

        foreach ($report->items as $index => $item) {
            $itemNode = $items->addChild('Item');
            $itemNode->addChild('Secuencia', $index + 1);
            $itemNode->addChild('TipoDocumento', $item->third_party_document_type);
            $itemNode->addChild('NumeroIdentificacion', $item->third_party_document_number);

            if ($item->third_party_verification_digit) {
                $itemNode->addChild('DigitoVerificacion', $item->third_party_verification_digit);
            }

            $itemNode->addChild('PrimerApellido', htmlspecialchars($item->third_party_name));
            $itemNode->addChild('RazonSocial', htmlspecialchars($item->third_party_name));

            if ($item->third_party_address) {
                $itemNode->addChild('Direccion', htmlspecialchars($item->third_party_address));
            }

            if ($item->third_party_city) {
                $itemNode->addChild('Ciudad', htmlspecialchars($item->third_party_city));
            }

            if ($item->concept_code) {
                $itemNode->addChild('CodigoConcepto', $item->concept_code);
            }

            $itemNode->addChild('ValorPago', number_format($item->payment_amount, 2, '.', ''));
            $itemNode->addChild('ValorRetencion', number_format($item->withholding_amount, 2, '.', ''));

            if ($item->tax_base > 0) {
                $itemNode->addChild('BaseGravable', number_format($item->tax_base, 2, '.', ''));
            }

            if ($item->withholding_rate) {
                $itemNode->addChild('TarifaRetencion', number_format($item->withholding_rate, 2, '.', ''));
            }
        }

        // Summary
        $summary = $xml->addChild('Resumen');
        $summary->addChild('TotalRegistros', $report->total_items);
        $summary->addChild('TotalPagos', number_format($report->total_amount, 2, '.', ''));
        $summary->addChild('TotalRetenciones', number_format($report->total_withholding, 2, '.', ''));

        // Save file with formatting
        $fileName = $this->generateFileName($report, 'xml');
        $filePath = "exogenous-reports/{$report->conjunto_config_id}/{$report->fiscal_year}/{$fileName}";

        // Format XML with proper indentation
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        Storage::disk('local')->put($filePath, $dom->saveXML());

        return [
            'success' => true,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'format' => 'xml',
            'file_size' => Storage::disk('local')->size($filePath),
        ];
    }

    /**
     * Export to plain text format (DIAN fixed-width format)
     */
    private function exportToText(ExogenousReport $report): array
    {
        $config = $this->getReportConfiguration($report);
        $lines = [];

        // Header line (Type 1)
        $headerLine = $this->formatTextLine([
            '1', // Record type
            $report->report_type,
            $config->entity_document_number,
            $config->entity_verification_digit ?? '',
            str_pad(Str::limit($config->entity_name, 200, ''), 200),
            $report->fiscal_year,
            $report->period_start->format('Ymd'),
            $report->period_end->format('Ymd'),
        ]);
        $lines[] = $headerLine;

        // Detail lines (Type 2)
        foreach ($report->items as $item) {
            $detailLine = $this->formatTextLine([
                '2', // Record type
                $item->third_party_document_type,
                $item->third_party_document_number,
                $item->third_party_verification_digit ?? '',
                str_pad(Str::limit($item->third_party_name, 200, ''), 200),
                str_pad(Str::limit($item->third_party_address ?? '', 200, ''), 200),
                str_pad(Str::limit($item->third_party_city ?? '', 100, ''), 100),
                $item->concept_code ?? '',
                $this->formatAmount($item->payment_amount),
                $this->formatAmount($item->withholding_amount),
                $this->formatAmount($item->tax_base),
                $this->formatRate($item->withholding_rate),
            ]);
            $lines[] = $detailLine;
        }

        // Footer line (Type 3)
        $footerLine = $this->formatTextLine([
            '3', // Record type
            $report->total_items,
            $this->formatAmount($report->total_amount),
            $this->formatAmount($report->total_withholding),
        ]);
        $lines[] = $footerLine;

        // Join all lines
        $content = implode("\n", $lines);

        // Save file
        $fileName = $this->generateFileName($report, 'txt');
        $filePath = "exogenous-reports/{$report->conjunto_config_id}/{$report->fiscal_year}/{$fileName}";

        Storage::disk('local')->put($filePath, $content);

        return [
            'success' => true,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'format' => 'txt',
            'file_size' => Storage::disk('local')->size($filePath),
        ];
    }

    /**
     * Export to Excel format (for internal review, not DIAN submission)
     */
    private function exportToExcel(ExogenousReport $report): array
    {
        $config = $this->getReportConfiguration($report);

        // Build CSV content (Excel-compatible)
        $csvLines = [];

        // Header row
        $csvLines[] = [
            'Reporte',
            $report->report_number,
            'Tipo',
            $report->report_type_label,
        ];
        $csvLines[] = [
            'Entidad',
            $config->entity_name,
            'NIT',
            $config->entity_document_number.($config->entity_verification_digit ? '-'.$config->entity_verification_digit : ''),
        ];
        $csvLines[] = [
            'Período',
            $report->period_start->format('Y-m-d').' a '.$report->period_end->format('Y-m-d'),
            'Año Fiscal',
            $report->fiscal_year,
        ];
        $csvLines[] = []; // Empty row

        // Column headers
        $csvLines[] = [
            'Tipo Doc',
            'Número Documento',
            'Dígito',
            'Nombre/Razón Social',
            'Dirección',
            'Ciudad',
            'Concepto',
            'Código Concepto',
            'Base Gravable',
            'Valor Pago',
            'Valor Retención',
            'Tarifa %',
            'Pago Neto',
        ];

        // Data rows
        foreach ($report->items as $item) {
            $csvLines[] = [
                $item->third_party_document_type,
                $item->third_party_document_number,
                $item->third_party_verification_digit ?? '',
                $item->third_party_name,
                $item->third_party_address ?? '',
                $item->third_party_city ?? '',
                $item->concept_name,
                $item->concept_code ?? '',
                number_format($item->tax_base, 2, '.', ''),
                number_format($item->payment_amount, 2, '.', ''),
                number_format($item->withholding_amount, 2, '.', ''),
                $item->withholding_rate ? number_format($item->withholding_rate, 2, '.', '') : '',
                number_format($item->net_payment, 2, '.', ''),
            ];
        }

        // Totals row
        $csvLines[] = []; // Empty row
        $csvLines[] = [
            'TOTALES',
            '',
            '',
            "Total Registros: {$report->total_items}",
            '',
            '',
            '',
            '',
            '',
            number_format($report->total_amount, 2, '.', ''),
            number_format($report->total_withholding, 2, '.', ''),
            '',
            number_format($report->total_amount - $report->total_withholding, 2, '.', ''),
        ];

        // Convert to CSV string
        $csvContent = '';
        foreach ($csvLines as $line) {
            $csvContent .= $this->arrayToCsvLine($line)."\n";
        }

        // Save file
        $fileName = $this->generateFileName($report, 'csv');
        $filePath = "exogenous-reports/{$report->conjunto_config_id}/{$report->fiscal_year}/{$fileName}";

        Storage::disk('local')->put($filePath, "\xEF\xBB\xBF".$csvContent); // Add BOM for Excel UTF-8

        return [
            'success' => true,
            'file_path' => $filePath,
            'file_name' => $fileName,
            'format' => 'excel',
            'file_size' => Storage::disk('local')->size($filePath),
        ];
    }

    /**
     * Get report configuration or throw exception
     */
    private function getReportConfiguration(ExogenousReport $report): ExogenousReportConfiguration
    {
        $config = ExogenousReportConfiguration::forConjunto($report->conjunto_config_id)
            ->byFiscalYear($report->fiscal_year)
            ->byReportType($report->report_type)
            ->first();

        if (! $config) {
            throw new \Exception('No se encontró configuración para este reporte');
        }

        return $config;
    }

    /**
     * Generate standardized file name
     */
    private function generateFileName(ExogenousReport $report, string $extension): string
    {
        return sprintf(
            '%s_%s_%s_%s.%s',
            $report->report_type,
            $report->fiscal_year,
            $report->report_number,
            now()->format('Ymd_His'),
            $extension
        );
    }

    /**
     * Format line for text export
     */
    private function formatTextLine(array $fields): string
    {
        return implode('|', $fields);
    }

    /**
     * Format amount for text export (without decimals, pad with zeros)
     */
    private function formatAmount(?float $amount): string
    {
        if ($amount === null) {
            return '0';
        }

        return str_pad((int) ($amount * 100), 15, '0', STR_PAD_LEFT);
    }

    /**
     * Format rate for text export
     */
    private function formatRate(?float $rate): string
    {
        if ($rate === null) {
            return '0.00';
        }

        return number_format($rate, 2, '.', '');
    }

    /**
     * Convert array to CSV line
     */
    private function arrayToCsvLine(array $fields): string
    {
        $escapedFields = array_map(function ($field) {
            // Escape quotes and wrap in quotes if contains comma, quote, or newline
            if (preg_match('/[,"\n\r]/', $field)) {
                return '"'.str_replace('"', '""', $field).'"';
            }

            return $field;
        }, $fields);

        return implode(',', $escapedFields);
    }

    /**
     * Download exported file
     */
    public function download(ExogenousReport $report): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        if (! $report->export_file_path) {
            throw new \Exception('El reporte no ha sido exportado');
        }

        if (! Storage::disk('local')->exists($report->export_file_path)) {
            throw new \Exception('El archivo exportado no existe');
        }

        $fileName = basename($report->export_file_path);

        return Storage::disk('local')->download($report->export_file_path, $fileName);
    }
}
