<?php

namespace App\Imports;

use App\Models\JelpitPaymentImport as JelpitPaymentImportModel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class JelpitPaymentImport implements ToCollection, WithStartRow
{
    private $conjuntoId;

    private $batchId;

    private $userId;

    private $processedCount = 0;

    private $matchedCount = 0;

    public function __construct(int $conjuntoId, string $batchId, int $userId)
    {
        $this->conjuntoId = $conjuntoId;
        $this->batchId = $batchId;
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // Skip empty rows
            if ($this->isEmptyRow($row)) {
                continue;
            }

            // Skip header rows that might have been included as data
            if ($this->isHeaderRow($row)) {
                continue;
            }

            try {
                $import = $this->createImportFromRow($row);
                if ($import) {
                    $this->processedCount++;

                    // Attempt automatic reconciliation
                    if ($import->attemptAutomaticReconciliation()) {
                        $this->matchedCount++;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error procesando fila de Excel Jelpit', [
                    'error' => $e->getMessage(),
                    'row_data' => $row->toArray(),
                ]);
            }
        }
    }

    private function isEmptyRow(Collection $row): bool
    {
        // Check if all important fields are empty (by index)
        $importantIndices = [0, 4, 2]; // tipo_de_pago, valor_transaccion, fecha_de_transaccion

        foreach ($importantIndices as $index) {
            $value = $row[$index] ?? null;
            if (! empty($value) && $value !== 'NaN' && $value !== 'N/A') {
                return false;
            }
        }

        return true;
    }

    private function isHeaderRow(Collection $row): bool
    {
        // Check if this row contains header values instead of data
        $tipoPago = $row[0] ?? '';

        return in_array($tipoPago, [
            'Tipo de pago',
            'tipo_de_pago',
            'TIPO DE PAGO',
        ]);
    }

    private function createImportFromRow(Collection $row): ?JelpitPaymentImportModel
    {
        // Map the Excel columns to our expected format
        $data = $this->mapRowData($row);

        // Skip if no transaction amount
        if (empty($data['transaction_amount']) || ! is_numeric($data['transaction_amount'])) {
            return null;
        }

        return JelpitPaymentImportModel::create([
            'conjunto_config_id' => $this->conjuntoId,
            'payment_type' => $data['payment_type'],
            'reference_number' => $data['reference_number'],
            'transaction_date' => $data['transaction_date'],
            'transaction_time' => $data['transaction_time'],
            'transaction_amount' => $data['transaction_amount'],
            'posting_date' => $data['posting_date'],
            'approval_number' => $data['approval_number'],
            'pse_cycle' => $data['pse_cycle'],
            'office_code' => $data['office_code'],
            'office_name' => $data['office_name'],
            'originator_nit' => $data['originator_nit'],
            'reference_2' => $data['reference_2'],
            'payment_detail' => $data['payment_detail'],
            'import_batch_id' => $this->batchId,
            'created_by' => $this->userId,
        ]);
    }

    private function mapRowData(Collection $row): array
    {
        // Map Excel columns by index position:
        // 0: Tipo de pago
        // 1: No. Ref
        // 2: Fecha de transacción
        // 3: Hora de transacción
        // 4: Valor transacción
        // 5: Fecha de abono
        // 6: Número de aprobación
        // 7: Ciclo PSE
        // 8: Código de oficina
        // 9: Nombre de oficina
        // 10: NIT Originador
        // 11: Referencia 2
        // 12: Detalle del pago

        return [
            'payment_type' => $this->cleanValue($row[0]),
            'reference_number' => $this->cleanValue($row[1]),
            'transaction_date' => $this->parseDate($row[2]),
            'transaction_time' => $this->parseTime($row[3]),
            'transaction_amount' => $this->parseAmount($row[4]),
            'posting_date' => $this->parseDate($row[5]),
            'approval_number' => $this->cleanValue($row[6]),
            'pse_cycle' => $this->cleanValue($row[7]),
            'office_code' => $this->cleanValue($row[8]),
            'office_name' => $this->cleanValue($row[9]),
            'originator_nit' => $this->cleanValue($row[10]),
            'reference_2' => $this->cleanValue($row[11]),
            'payment_detail' => $this->cleanValue($row[12]),
        ];
    }

    private function cleanValue($value): ?string
    {
        if (is_null($value) || $value === '' || $value === 'NaN' || $value === 'N/A') {
            return null;
        }

        return trim((string) $value);
    }

    private function parseDate($value): ?string
    {
        if (empty($value) || $value === 'NaN') {
            return null;
        }

        try {
            // Handle different date formats
            if (is_numeric($value)) {
                // Excel serial date
                $date = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value);

                return $date->format('Y-m-d');
            }

            if (is_string($value)) {
                // Try to parse string date formats like "20/01/2025"
                $date = \DateTime::createFromFormat('d/m/Y', $value);
                if ($date) {
                    return $date->format('Y-m-d');
                }

                // Try other formats
                $date = \DateTime::createFromFormat('Y-m-d', $value);
                if ($date) {
                    return $date->format('Y-m-d');
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseTime($value): ?string
    {
        if (empty($value) || $value === 'NaN') {
            return null;
        }

        try {
            if (is_string($value)) {
                // Handle time formats like "15:24:19"
                if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $value)) {
                    return $value;
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseAmount($value): ?float
    {
        if (empty($value) || $value === 'NaN') {
            return null;
        }

        try {
            // Remove any formatting and convert to float
            $cleanValue = str_replace([',', '$', ' '], '', $value);

            return (float) $cleanValue;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getProcessedCount(): int
    {
        return $this->processedCount;
    }

    public function getMatchedCount(): int
    {
        return $this->matchedCount;
    }

    public function startRow(): int
    {
        return 2; // Start from row 2 to skip header row
    }
}
