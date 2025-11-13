<?php

namespace Database\Seeders;

use App\Models\ConjuntoConfig;
use App\Models\ExogenousReportConfiguration;
use Illuminate\Database\Seeder;

class ExogenousReportConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if (! $conjunto) {
            $this->command->error('No active conjunto configuration found');

            return;
        }

        $currentYear = now()->year;
        $reportTypes = ['1001', '1003', '1005', '1647'];

        foreach ($reportTypes as $reportType) {
            ExogenousReportConfiguration::updateOrCreate(
                [
                    'conjunto_config_id' => $conjunto->id,
                    'fiscal_year' => $currentYear,
                    'report_type' => $reportType,
                ],
                [
                    // Use the conjunto's information for entity data
                    'entity_document_number' => $conjunto->nit ?? '900000000-1',
                    'entity_name' => $conjunto->nombre,
                    'entity_address' => $conjunto->direccion ?? 'Dirección no especificada',
                    'entity_city' => $conjunto->ciudad ?? 'Bogotá',
                    'entity_country' => 'Colombia',

                    // Responsible person - use admin or default
                    'responsible_name' => 'Administrador',
                    'responsible_id' => '1234567890',
                    'responsible_position' => 'Administrador',
                    'responsible_email' => 'admin@tavira.com.co',
                    'responsible_phone' => '3001234567',

                    // Default threshold: $100M COP
                    'minimum_reporting_amount' => 100000000,

                    // Get default withholding rates from model
                    'withholding_rates' => $this->getDefaultWithholdingRates($reportType),
                ]
            );
        }

        $this->command->info("Created/updated exogenous report configurations for conjunto: {$conjunto->nombre}");
    }

    private function getDefaultWithholdingRates(string $reportType): array
    {
        return match ($reportType) {
            '1001', '1003' => [
                '28' => 4.0,  // Servicios varios - 4%
                '29' => 11.0, // Honorarios - 11%
                '30' => 3.5,  // Arrendamientos - 3.5%
                '31' => 2.5,  // Gastos diversos - 2.5%
            ],
            '1005' => [
                '01' => 0.0,  // Ingresos sin retención
            ],
            '1647' => [
                '01' => 1.5,  // Retención 1.5%
            ],
            default => [],
        };
    }
}
