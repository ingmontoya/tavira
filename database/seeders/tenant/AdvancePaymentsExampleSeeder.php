<?php

namespace Database\Seeders\Tenant;

use App\Models\AccountingTransaction;
use App\Models\AccountingTransactionEntry;
use App\Models\Apartment;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * EJEMPLO DE INGRESOS RECIBIDOS POR ANTICIPADO
 *
 * Este seeder implementa el ejemplo del documento:
 * "INGRESOS RECIBIDOS POR ANTICIPADO P.H.docx"
 *
 * ESCENARIO:
 * El 01/10/2025, la copropiedad recibe un pago de $1,350,000 correspondiente
 * a cuotas de administración de octubre, noviembre y diciembre.
 *
 * CONTABILIZACIÓN:
 * 1. Inicial (01/10/2025): Registrar como pasivo
 *    Débito:  111005 - Bancos nacionales          $1,350,000
 *    Crédito: 270505 - Ingresos anticipados       $1,350,000
 *
 * 2. Mensual (31/10, 30/11, 31/12): Reconocer ingreso devengado
 *    Débito:  270505 - Ingresos anticipados       $450,000
 *    Crédito: 417005 - Cuotas de administración   $450,000
 */
class AdvancePaymentsExampleSeeder extends Seeder
{
    public function run(): void
    {
        $conjunto = ConjuntoConfig::first();

        if (! $conjunto) {
            $this->command->error('No se encontró configuración del conjunto. Ejecute primero el seeder de configuración.');

            return;
        }

        $this->command->info('🔄 Creando ejemplo de Ingresos Recibidos por Anticipado...');

        // Get or create necessary accounts
        $accounts = $this->getOrCreateAccounts($conjunto->id);

        // Get a sample apartment
        $apartment = Apartment::whereIn('apartment_type_id', function ($query) use ($conjunto) {
            $query->select('id')
                ->from('apartment_types')
                ->where('conjunto_config_id', $conjunto->id);
        })->first();

        if (! $apartment) {
            $this->command->error('No se encontraron apartamentos. Ejecute primero el seeder de apartamentos.');

            return;
        }

        $this->command->info("📍 Usando apartamento: {$apartment->full_address}");

        // Get admin user for transactions
        $adminUser = User::whereHas('roles', function ($query) {
            $query->where('name', 'Super Admin');
        })->first() ?? User::first();

        // PASO 1: Registrar recepción del pago anticipado (01/10/2025)
        $this->createAdvancePaymentReceipt(
            $conjunto,
            $apartment,
            $accounts,
            $adminUser,
            Carbon::create(2025, 10, 1)
        );

        // PASO 2: Reconocer ingreso devengado de octubre (31/10/2025)
        $this->recognizeEarnedRevenue(
            $conjunto,
            $apartment,
            $accounts,
            $adminUser,
            Carbon::create(2025, 10, 31),
            'octubre',
            1
        );

        // PASO 3: Reconocer ingreso devengado de noviembre (30/11/2025)
        $this->recognizeEarnedRevenue(
            $conjunto,
            $apartment,
            $accounts,
            $adminUser,
            Carbon::create(2025, 11, 30),
            'noviembre',
            2
        );

        // PASO 4: Reconocer ingreso devengado de diciembre (31/12/2025)
        $this->recognizeEarnedRevenue(
            $conjunto,
            $apartment,
            $accounts,
            $adminUser,
            Carbon::create(2025, 12, 31),
            'diciembre',
            3
        );

        $this->command->info('✅ Ejemplo de anticipos creado exitosamente!');
        $this->command->line('');
        $this->command->info('📊 RESUMEN DEL EJEMPLO:');
        $this->command->line('Fecha: 01/10/2025 - Recibido $1,350,000 por anticipado');
        $this->command->line('  Débito:  111005 - Bancos nacionales          $1,350,000');
        $this->command->line('  Crédito: 270505 - Ingresos anticipados       $1,350,000');
        $this->command->line('');
        $this->command->line('Fecha: 31/10/2025 - Reconocer ingreso octubre');
        $this->command->line('  Débito:  270505 - Ingresos anticipados       $450,000');
        $this->command->line('  Crédito: 417005 - Cuotas de administración   $450,000');
        $this->command->line('');
        $this->command->line('Fecha: 30/11/2025 - Reconocer ingreso noviembre');
        $this->command->line('  Débito:  270505 - Ingresos anticipados       $450,000');
        $this->command->line('  Crédito: 417005 - Cuotas de administración   $450,000');
        $this->command->line('');
        $this->command->line('Fecha: 31/12/2025 - Reconocer ingreso diciembre');
        $this->command->line('  Débito:  270505 - Ingresos anticipados       $450,000');
        $this->command->line('  Crédito: 417005 - Cuotas de administración   $450,000');
        $this->command->line('');
        $this->command->info('💡 Puede ver estas transacciones en: Contabilidad > Transacciones Contables');
    }

    private function getOrCreateAccounts(int $conjuntoConfigId): array
    {
        // 111005 - Bancos nacionales
        $bank = ChartOfAccounts::forConjunto($conjuntoConfigId)
            ->where('code', '111005')
            ->first();

        if (! $bank) {
            $this->command->warn('⚠ Cuenta 111005 no encontrada, se usará una cuenta bancaria alternativa');
            $bank = ChartOfAccounts::forConjunto($conjuntoConfigId)
                ->where('code', 'LIKE', '1110%')
                ->where('accepts_posting', true)
                ->first();
        }

        // 270505 - Ingresos recibidos por anticipado (PASIVO)
        $advanceIncome = ChartOfAccounts::forConjunto($conjuntoConfigId)
            ->where('code', '270505')
            ->first();

        if (! $advanceIncome) {
            $this->command->info('📝 Creando cuenta 270505 - Ingresos recibidos por anticipado');

            // Get parent account (2705)
            $parent = ChartOfAccounts::forConjunto($conjuntoConfigId)
                ->where('code', '2705')
                ->first();

            if (! $parent) {
                // Create parent if doesn't exist
                $parent = ChartOfAccounts::create([
                    'conjunto_config_id' => $conjuntoConfigId,
                    'code' => '2705',
                    'name' => 'Ingresos recibidos por anticipado',
                    'type' => 'liability',
                    'level' => 2,
                    'parent_id' => null,
                    'accepts_posting' => false,
                    'is_active' => true,
                    'description' => 'Ingresos recibidos antes de la prestación del servicio',
                ]);
            }

            $advanceIncome = ChartOfAccounts::create([
                'conjunto_config_id' => $conjuntoConfigId,
                'code' => '270505',
                'name' => 'Cuotas de administración recibidas por anticipado',
                'type' => 'liability',
                'level' => 3,
                'parent_id' => $parent->id,
                'accepts_posting' => true,
                'is_active' => true,
                'description' => 'Cuotas de administración pagadas por adelantado que aún no se han devengado',
            ]);
        }

        // 417005 - Ingresos por cuotas de administración
        $adminFees = ChartOfAccounts::forConjunto($conjuntoConfigId)
            ->where('code', '417005')
            ->first();

        if (! $adminFees) {
            $this->command->warn('⚠ Cuenta 417005 no encontrada en el plan de cuentas');
            $this->command->info('📝 Creando cuenta 417005 - Ingresos por cuotas de administración');

            // Try to find parent account 4170 or 41
            $parent = ChartOfAccounts::forConjunto($conjuntoConfigId)
                ->where('code', 'LIKE', '4170%')
                ->whereRaw('LENGTH(code) < 6')
                ->where('accepts_posting', false)
                ->first();

            $adminFees = ChartOfAccounts::create([
                'conjunto_config_id' => $conjuntoConfigId,
                'code' => '417005',
                'name' => 'Ingresos por cuotas de administración',
                'type' => 'income',
                'level' => $parent ? ($parent->level + 1) : 3,
                'parent_id' => $parent?->id,
                'accepts_posting' => true,
                'is_active' => true,
                'description' => 'Ingresos devengados por cuotas de administración de P.H.',
            ]);
        }

        return [
            'bank' => $bank,
            'advance_income' => $advanceIncome,
            'admin_fees' => $adminFees,
        ];
    }

    private function createAdvancePaymentReceipt(
        ConjuntoConfig $conjunto,
        Apartment $apartment,
        array $accounts,
        User $user,
        Carbon $date
    ): void {
        $totalAmount = 1350000; // $450,000 x 3 meses

        $this->command->info("📥 Registrando recepción de pago anticipado ($".number_format($totalAmount, 0).")");

        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $conjunto->id,
            'transaction_number' => 'ANTICIPO-'.strtoupper(uniqid()),
            'transaction_date' => $date,
            'posted_at' => $date,
            'type' => 'standard',
            'status' => 'posted',
            'description' => "Recepción de pago anticipado - Apartamento {$apartment->full_address} - Cuotas octubre, noviembre y diciembre 2025",
            'notes' => 'Ejemplo de contabilización de ingresos recibidos por anticipado según normas contables colombianas',
            'posted_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => $date,
        ]);

        // Débito: Bancos (aumenta activo)
        AccountingTransactionEntry::create([
            'accounting_transaction_id' => $transaction->id,
            'account_id' => $accounts['bank']->id,
            'description' => 'Recepción de pago anticipado en cuenta bancaria',
            'debit_amount' => $totalAmount,
            'credit_amount' => 0,
        ]);

        // Crédito: Ingresos anticipados (aumenta pasivo)
        AccountingTransactionEntry::create([
            'accounting_transaction_id' => $transaction->id,
            'account_id' => $accounts['advance_income']->id,
            'description' => 'Registro de cuotas recibidas por anticipado (pasivo)',
            'debit_amount' => 0,
            'credit_amount' => $totalAmount,
        ]);

        $this->command->info("  ✓ Transacción #{$transaction->transaction_number} creada");
    }

    private function recognizeEarnedRevenue(
        ConjuntoConfig $conjunto,
        Apartment $apartment,
        array $accounts,
        User $user,
        Carbon $date,
        string $monthName,
        int $monthNumber
    ): void {
        $monthlyAmount = 450000;

        $this->command->info("💰 Reconociendo ingreso devengado de {$monthName} ($".number_format($monthlyAmount, 0).")");

        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $conjunto->id,
            'transaction_number' => 'DEVENGO-'.strtoupper(uniqid()),
            'transaction_date' => $date,
            'posted_at' => $date,
            'type' => 'standard',
            'status' => 'posted',
            'description' => "Reconocimiento de ingreso devengado - Apartamento {$apartment->full_address} - Cuota {$monthName} 2025 (mes {$monthNumber}/3)",
            'notes' => "Reclasificación de pasivo a ingreso. El servicio ha sido prestado durante el mes de {$monthName}, por lo tanto se reconoce el ingreso devengado.",
            'posted_by' => $user->id,
            'approved_by' => $user->id,
            'approved_at' => $date,
        ]);

        // Débito: Ingresos anticipados (disminuye pasivo)
        AccountingTransactionEntry::create([
            'accounting_transaction_id' => $transaction->id,
            'account_id' => $accounts['advance_income']->id,
            'description' => "Disminución del pasivo por servicio prestado en {$monthName}",
            'debit_amount' => $monthlyAmount,
            'credit_amount' => 0,
        ]);

        // Crédito: Ingresos por cuotas (aumenta ingreso)
        AccountingTransactionEntry::create([
            'accounting_transaction_id' => $transaction->id,
            'account_id' => $accounts['admin_fees']->id,
            'description' => "Ingreso devengado por cuota de administración de {$monthName} 2025",
            'debit_amount' => 0,
            'credit_amount' => $monthlyAmount,
        ]);

        $this->command->info("  ✓ Transacción #{$transaction->transaction_number} creada");
    }
}
