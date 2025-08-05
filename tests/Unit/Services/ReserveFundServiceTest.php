<?php

namespace Tests\Unit\Services;

use App\Models\AccountingTransaction;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Services\ReserveFundService;
use App\Settings\PaymentSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Tests unitarios para ReserveFundService
 * 
 * Valida el funcionamiento correcto de:
 * - Cálculo del fondo de reserva
 * - Apropiación automática
 * - Validación de cumplimiento legal
 * - Manejo de errores
 */
class ReserveFundServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConjuntoConfig $conjunto;
    private ReserveFundService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear conjunto de prueba
        $this->conjunto = ConjuntoConfig::factory()->create();
        
        // Crear plan de cuentas necesario
        $this->createRequiredAccounts();
        
        // Instanciar servicio
        $this->service = new ReserveFundService($this->conjunto->id);
    }

    /** @test */
    public function puede_calcular_monto_de_reserva_mensual()
    {
        // Arrange: Crear ingresos operacionales de $1,000,000
        $this->createOperationalIncomeTransactions(1000000, 5, 2024);
        
        // Act: Calcular reserva para mayo 2024
        $reserveAmount = $this->service->calculateMonthlyReserve(5, 2024);
        
        // Assert: Debe ser el 30% de los ingresos
        $this->assertEquals(300000, $reserveAmount, 'El fondo de reserva debe ser el 30% de los ingresos operacionales');
    }

    /** @test */
    public function puede_ejecutar_apropiacion_mensual()
    {
        // Arrange: Crear ingresos operacionales
        $this->createOperationalIncomeTransactions(1500000, 5, 2024);
        
        // Act: Ejecutar apropiación
        $transaction = $this->service->executeMonthlyAppropriation(5, 2024);
        
        // Assert: Verificar que se creó la transacción
        $this->assertNotNull($transaction, 'Debe crear una transacción de apropiación');
        $this->assertEquals('contabilizado', $transaction->status, 'La transacción debe estar contabilizada');
        $this->assertEquals(450000, $transaction->total_debit, 'Debe apropiar $450,000 (30% de $1,500,000)');
        $this->assertEquals(450000, $transaction->total_credit, 'Debe cumplir con partida doble');
        
        // Verificar que tiene las entradas correctas
        $this->assertCount(2, $transaction->entries, 'Debe tener exactamente 2 movimientos');
        
        $debitEntry = $transaction->entries->where('debit_amount', '>', 0)->first();
        $creditEntry = $transaction->entries->where('credit_amount', '>', 0)->first();
        
        $this->assertEquals('530502', $debitEntry->account->code, 'Débito debe ser en cuenta de gasto de reserva');
        $this->assertEquals('320501', $creditEntry->account->code, 'Crédito debe ser en cuenta de fondo de reserva');
    }

    /** @test */
    public function no_crea_apropiacion_si_ya_existe_para_el_periodo()
    {
        // Arrange: Crear apropiación previa
        $this->createOperationalIncomeTransactions(1000000, 5, 2024);
        $firstTransaction = $this->service->executeMonthlyAppropriation(5, 2024);
        
        // Act: Intentar crear otra apropiación para el mismo período
        $secondTransaction = $this->service->executeMonthlyAppropriation(5, 2024);
        
        // Assert: No debe crear segunda transacción
        $this->assertNotNull($firstTransaction, 'Primera apropiación debe crearse');
        $this->assertNull($secondTransaction, 'Segunda apropiación no debe crearse');
    }

    /** @test */
    public function no_crea_apropiacion_si_no_hay_ingresos()
    {
        // Arrange: Sin ingresos para el período
        
        // Act: Intentar ejecutar apropiación
        $transaction = $this->service->executeMonthlyAppropriation(5, 2024);
        
        // Assert: No debe crear transacción
        $this->assertNull($transaction, 'No debe crear apropiación sin ingresos');
    }

    /** @test */
    public function puede_obtener_balance_del_fondo_de_reserva()
    {
        // Arrange: Crear apropiaciones previas
        $this->createOperationalIncomeTransactions(1000000, 4, 2024);
        $this->createOperationalIncomeTransactions(1200000, 5, 2024);
        
        $this->service->executeMonthlyAppropriation(4, 2024); // $300,000
        $this->service->executeMonthlyAppropriation(5, 2024); // $360,000
        
        // Act: Obtener balance
        $balance = $this->service->getReserveFundBalance();
        
        // Assert: Debe sumar todas las apropiaciones
        $this->assertEquals(660000, $balance, 'Balance debe ser $660,000 ($300,000 + $360,000)');
    }

    /** @test */
    public function valida_cumplimiento_legal_del_fondo_de_reserva()
    {
        // Arrange: Crear ingresos anuales y apropiaciones
        $totalIncome = 0;
        $totalAppropriated = 0;
        
        for ($month = 1; $month <= 6; $month++) {
            $monthlyIncome = 1000000 + ($month * 50000); // Ingresos crecientes
            $this->createOperationalIncomeTransactions($monthlyIncome, $month, 2024);
            $transaction = $this->service->executeMonthlyAppropriation($month, 2024);
            
            $totalIncome += $monthlyIncome;
            $totalAppropriated += $transaction->total_debit;
        }
        
        // Act: Validar cumplimiento
        $compliance = $this->service->validateLegalCompliance(2024);
        
        // Assert: Verificar cumplimiento
        $this->assertTrue($compliance['is_compliant'], 'Debe cumplir con el porcentaje legal');
        $this->assertEquals($totalIncome, $compliance['total_income'], 'Ingresos totales deben coincidir');
        $this->assertEquals($totalAppropriated, $compliance['total_appropriated'], 'Apropiaciones totales deben coincidir');
        $this->assertGreaterThanOrEqual(30, $compliance['compliance_percentage'], 'Debe cumplir mínimo 30%');
    }

    /** @test */
    public function lanza_excepcion_si_no_existe_cuenta_de_gasto_de_reserva()
    {
        // Arrange: Eliminar cuenta de gasto de reserva
        ChartOfAccounts::where('code', '530502')->delete();
        $this->createOperationalIncomeTransactions(1000000, 5, 2024);
        
        // Act & Assert: Debe lanzar excepción
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No se encontró la cuenta 530502');
        
        $this->service->executeMonthlyAppropriation(5, 2024);
    }

    /** @test */
    public function lanza_excepcion_si_no_existe_cuenta_de_fondo_de_reserva()
    {
        // Arrange: Eliminar cuenta de fondo de reserva
        ChartOfAccounts::where('code', '320501')->delete();
        $this->createOperationalIncomeTransactions(1000000, 5, 2024);
        
        // Act & Assert: Debe lanzar excepción
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('No se encontró la cuenta 320501');
        
        $this->service->executeMonthlyAppropriation(5, 2024);
    }

    /** @test */
    public function respeta_porcentaje_personalizado_de_reserva()
    {
        // Arrange: Configurar porcentaje personalizado (35%)
        $paymentSettings = app(PaymentSettings::class);
        $paymentSettings->set('reserve_fund_percentage', 35);
        
        $this->createOperationalIncomeTransactions(1000000, 5, 2024);
        
        // Act: Calcular reserva
        $reserveAmount = $this->service->calculateMonthlyReserve(5, 2024);
        
        // Assert: Debe usar el 35%
        $this->assertEquals(350000, $reserveAmount, 'Debe usar el porcentaje personalizado del 35%');
    }

    /* ========== MÉTODOS AUXILIARES ========== */

    /**
     * Crea las cuentas contables necesarias para las pruebas
     */
    private function createRequiredAccounts(): void
    {
        // Cuenta de ingreso operacional
        ChartOfAccounts::create([
            'conjunto_config_id' => $this->conjunto->id,
            'code' => '413501',
            'name' => 'CUOTAS DE ADMINISTRACIÓN',
            'description' => 'Ingresos por cuotas ordinarias',
            'account_type' => 'income',
            'level' => 4,
            'is_active' => true,
            'requires_third_party' => false,
            'nature' => 'credit',
            'accepts_posting' => true,
        ]);

        // Cuenta de gasto para apropiación de reserva
        ChartOfAccounts::create([
            'conjunto_config_id' => $this->conjunto->id,
            'code' => '530502',
            'name' => 'APROPIACIÓN FONDO DE RESERVA',
            'description' => 'Gasto por apropiación mensual al fondo de reserva',
            'account_type' => 'expense',
            'level' => 4,
            'is_active' => true,
            'requires_third_party' => false,
            'nature' => 'debit',
            'accepts_posting' => true,
        ]);

        // Cuenta de fondo de reserva
        ChartOfAccounts::create([
            'conjunto_config_id' => $this->conjunto->id,
            'code' => '320501',
            'name' => 'FONDO DE RESERVA (LEY 675)',
            'description' => 'Fondo de reserva obligatorio según Ley 675',
            'account_type' => 'equity',
            'level' => 4,
            'is_active' => true,
            'requires_third_party' => false,
            'nature' => 'credit',
            'accepts_posting' => true,
        ]);
    }

    /**
     * Crea transacciones de ingresos operacionales para un mes específico
     */
    private function createOperationalIncomeTransactions(float $amount, int $month, int $year): void
    {
        $incomeAccount = ChartOfAccounts::where('code', '413501')->first();
        
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto->id,
            'transaction_date' => sprintf("%d-%02d-15", $year, $month), // Medio del mes
            'description' => "Ingresos operacionales - {$month}/{$year}",
            'reference_type' => 'test',
            'reference_id' => 1,
            'status' => 'borrador',
            'created_by' => 1,
        ]);

        // Crear entrada de ingreso (crédito)
        $transaction->addEntry([
            'account_id' => $incomeAccount->id,
            'description' => "Ingreso test {$month}/{$year}",
            'debit_amount' => 0,
            'credit_amount' => $amount,
        ]);

        // Crear contrapartida ficticia (débito) - en la realidad sería cartera
        $transaction->addEntry([
            'account_id' => $incomeAccount->id, // Usar la misma cuenta para simplificar
            'description' => "Contrapartida test {$month}/{$year}",
            'debit_amount' => $amount,
            'credit_amount' => 0,
        ]);

        $transaction->post();
    }
}