<?php

namespace Tests\Unit\Services;

use App\Models\AccountingTransaction;
use App\Models\Apartment;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Services\AccountingValidationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests unitarios para AccountingValidationService
 *
 * Valida el funcionamiento correcto de:
 * - Validaciones de integridad contable
 * - Control de períodos cerrados
 * - Validación de naturaleza de cuentas
 * - Validaciones específicas de propiedad horizontal
 */
class AccountingValidationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ConjuntoConfig $conjunto;

    private AccountingValidationService $service;

    private ChartOfAccounts $assetAccount;

    private ChartOfAccounts $incomeAccount;

    private ChartOfAccounts $carteraAccount;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear conjunto de prueba
        $this->conjunto = ConjuntoConfig::factory()->create();

        // Crear cuentas de prueba
        $this->createTestAccounts();

        // Instanciar servicio
        $this->service = new AccountingValidationService;
    }

    /** @test */
    public function valida_transaccion_correcta_sin_errores()
    {
        // Arrange: Crear transacción válida
        $transaction = $this->createValidTransaction();

        // Act: Validar transacción
        $result = $this->service->validateTransactionIntegrity($transaction);

        // Assert: No debe tener errores
        $this->assertTrue($result['is_valid'], 'Transacción válida debe pasar todas las validaciones');
        $this->assertEmpty($result['errors'], 'No debe tener errores');
        $this->assertTrue($result['validation_summary']['can_be_posted'], 'Debe poder ser contabilizada');
    }

    /** @test */
    public function detecta_transaccion_desbalanceada()
    {
        // Arrange: Crear transacción desbalanceada
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto->id,
            'transaction_date' => now()->subDays(5),
            'description' => 'Transacción desbalanceada de prueba',
            'status' => 'borrador',
            'created_by' => 1,
        ]);

        // Crear movimientos desbalanceados
        $transaction->addEntry([
            'account_id' => $this->assetAccount->id,
            'description' => 'Débito',
            'debit_amount' => 1000,
            'credit_amount' => 0,
        ]);

        $transaction->addEntry([
            'account_id' => $this->incomeAccount->id,
            'description' => 'Crédito insuficiente',
            'debit_amount' => 0,
            'credit_amount' => 800, // Diferencia de 200
        ]);

        // Act: Validar transacción
        $result = $this->service->validateTransactionIntegrity($transaction);

        // Assert: Debe detectar el error
        $this->assertFalse($result['is_valid'], 'Transacción desbalanceada debe fallar validación');
        $this->assertContains('partida doble', implode(' ', $result['errors']), 'Debe detectar error de partida doble');
    }

    /** @test */
    public function valida_periodo_abierto_correctamente()
    {
        // Arrange: Transacción con fecha reciente (válida)
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto->id,
            'transaction_date' => now()->subDays(10), // Hace 10 días
            'description' => 'Transacción con fecha válida',
            'status' => 'borrador',
            'created_by' => 1,
        ]);

        // Act: Validar período
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validatePeriodOpen');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $transaction);

        // Assert: Período debe estar abierto
        $this->assertTrue($result['is_valid'], 'Fecha reciente debe ser válida');
    }

    /** @test */
    public function rechaza_transacciones_en_periodo_muy_antiguo()
    {
        // Arrange: Transacción con fecha muy antigua
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto->id,
            'transaction_date' => now()->subMonths(4), // Hace 4 meses
            'description' => 'Transacción con fecha muy antigua',
            'status' => 'borrador',
            'created_by' => 1,
        ]);

        // Act: Validar período
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validatePeriodOpen');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $transaction);

        // Assert: Período debe estar cerrado
        $this->assertFalse($result['is_valid'], 'Fecha muy antigua debe ser inválida');
        $this->assertStringContainsString('períodos anteriores', $result['message']);
    }

    /** @test */
    public function rechaza_transacciones_futuras_lejanas()
    {
        // Arrange: Transacción con fecha futura lejana
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto->id,
            'transaction_date' => now()->addMonths(2), // En 2 meses
            'description' => 'Transacción con fecha futura',
            'status' => 'borrador',
            'created_by' => 1,
        ]);

        // Act: Validar período
        $reflection = new \ReflectionClass($this->service);
        $method = $reflection->getMethod('validatePeriodOpen');
        $method->setAccessible(true);
        $result = $method->invoke($this->service, $transaction);

        // Assert: Fecha futura debe ser inválida
        $this->assertFalse($result['is_valid'], 'Fecha futura lejana debe ser inválida');
        $this->assertStringContainsString('fecha futura', $result['message']);
    }

    /** @test */
    public function detecta_cuenta_sin_tercero_cuando_es_requerido()
    {
        // Arrange: Crear cuenta que requiere tercero
        $this->carteraAccount->update(['requires_third_party' => true]);

        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto->id,
            'transaction_date' => now()->subDays(5),
            'description' => 'Transacción sin tercero en cuenta que lo requiere',
            'status' => 'borrador',
            'created_by' => 1,
        ]);

        // Movimiento en cuenta de cartera SIN tercero
        $transaction->addEntry([
            'account_id' => $this->carteraAccount->id,
            'description' => 'Cartera sin tercero',
            'debit_amount' => 1000,
            'credit_amount' => 0,
            'third_party_id' => null, // Sin tercero
        ]);

        $transaction->addEntry([
            'account_id' => $this->incomeAccount->id,
            'description' => 'Contrapartida',
            'debit_amount' => 0,
            'credit_amount' => 1000,
        ]);

        // Act: Validar transacción
        $result = $this->service->validateTransactionIntegrity($transaction);

        // Assert: Debe detectar falta de tercero
        $this->assertFalse($result['is_valid'], 'Debe fallar por falta de tercero');
        $this->assertContains('requiere tercero', implode(' ', $result['errors']));
    }

    /** @test */
    public function valida_correctamente_cuenta_con_tercero()
    {
        // Arrange: Crear apartamento para usar como tercero
        $apartment = Apartment::factory()->create([
            'conjunto_config_id' => $this->conjunto->id,
        ]);

        $this->carteraAccount->update(['requires_third_party' => true]);

        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto->id,
            'transaction_date' => now()->subDays(5),
            'description' => 'Transacción con tercero válido',
            'status' => 'borrador',
            'created_by' => 1,
        ]);

        // Movimiento en cuenta de cartera CON tercero
        $transaction->addEntry([
            'account_id' => $this->carteraAccount->id,
            'description' => 'Cartera con tercero',
            'debit_amount' => 1000,
            'credit_amount' => 0,
            'third_party_type' => 'apartment',
            'third_party_id' => $apartment->id,
        ]);

        $transaction->addEntry([
            'account_id' => $this->incomeAccount->id,
            'description' => 'Contrapartida',
            'debit_amount' => 0,
            'credit_amount' => 1000,
        ]);

        // Act: Validar transacción
        $result = $this->service->validateTransactionIntegrity($transaction);

        // Assert: No debe tener errores relacionados con terceros
        $this->assertTrue($result['is_valid'], 'Transacción con tercero válido debe pasar');
        $tercerosErrors = array_filter($result['errors'], fn ($error) => str_contains($error, 'tercero'));
        $this->assertEmpty($tercerosErrors, 'No debe tener errores de terceros');
    }

    /** @test */
    public function advierte_sobre_naturaleza_de_cuenta_inusual()
    {
        // Arrange: Crear transacción con cuenta de activo en crédito (inusual pero posible)
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto->id,
            'transaction_date' => now()->subDays(5),
            'description' => 'Transacción con naturaleza inusual',
            'status' => 'borrador',
            'created_by' => 1,
        ]);

        // Cuenta de activo con movimiento crédito (disminución)
        $transaction->addEntry([
            'account_id' => $this->assetAccount->id,
            'description' => 'Activo en crédito',
            'debit_amount' => 0,
            'credit_amount' => 1000, // Inusual pero posible
        ]);

        $transaction->addEntry([
            'account_id' => $this->incomeAccount->id,
            'description' => 'Contrapartida',
            'debit_amount' => 1000,
            'credit_amount' => 0,
        ]);

        // Act: Validar transacción
        $result = $this->service->validateTransactionIntegrity($transaction);

        // Assert: Debe ser válida pero con advertencia
        $this->assertTrue($result['is_valid'], 'Debe ser válida aunque inusual');
        $this->assertNotEmpty($result['warnings'], 'Debe tener advertencias');
        $this->assertContains('naturaleza débito', implode(' ', $result['warnings']));
    }

    /** @test */
    public function valida_lote_de_transacciones()
    {
        // Arrange: Crear múltiples transacciones (algunas válidas, otras no)
        $validTransaction = $this->createValidTransaction();
        $invalidTransaction = $this->createInvalidTransaction();

        $transactions = collect([$validTransaction, $invalidTransaction]);

        // Act: Validar lote
        $result = $this->service->validateTransactionsBatch($transactions);

        // Assert: Debe procesar ambas transacciones
        $this->assertEquals(2, $result['total_transactions']);
        $this->assertEquals(1, $result['valid_transactions']);
        $this->assertEquals(1, $result['invalid_transactions']);
        $this->assertGreaterThan(0, $result['total_errors']);
    }

    /** @test */
    public function valida_integridad_de_periodo_completo()
    {
        // Arrange: Crear transacciones para un mes específico
        $month = 5;
        $year = 2024;

        $this->createValidTransactionForPeriod($month, $year);
        $this->createValidTransactionForPeriod($month, $year);

        // Act: Validar período
        $result = $this->service->validatePeriodIntegrity($this->conjunto->id, $month, $year);

        // Assert: Verificar validación del período
        $this->assertEquals(2, $result['total_transactions']);
        $this->assertEquals(2, $result['valid_transactions']);
        $this->assertTrue($result['period_checks']['balance_check']['is_balanced']);
    }

    /* ========== MÉTODOS AUXILIARES ========== */

    /**
     * Crea las cuentas de prueba necesarias
     */
    private function createTestAccounts(): void
    {
        $this->assetAccount = ChartOfAccounts::create([
            'conjunto_config_id' => $this->conjunto->id,
            'code' => '111001',
            'name' => 'BANCO PRINCIPAL',
            'description' => 'Cuenta bancaria principal',
            'account_type' => 'asset',
            'level' => 4,
            'is_active' => true,
            'requires_third_party' => false,
            'nature' => 'debit',
            'accepts_posting' => true,
        ]);

        $this->incomeAccount = ChartOfAccounts::create([
            'conjunto_config_id' => $this->conjunto->id,
            'code' => '413501',
            'name' => 'CUOTAS DE ADMINISTRACIÓN',
            'description' => 'Ingresos por cuotas',
            'account_type' => 'income',
            'level' => 4,
            'is_active' => true,
            'requires_third_party' => false,
            'nature' => 'credit',
            'accepts_posting' => true,
        ]);

        $this->carteraAccount = ChartOfAccounts::create([
            'conjunto_config_id' => $this->conjunto->id,
            'code' => '130501',
            'name' => 'CARTERA ADMINISTRACIÓN',
            'description' => 'Cartera por cuotas de administración',
            'account_type' => 'asset',
            'level' => 4,
            'is_active' => true,
            'requires_third_party' => false,
            'nature' => 'debit',
            'accepts_posting' => true,
        ]);
    }

    /**
     * Crea una transacción válida para pruebas
     */
    private function createValidTransaction(): AccountingTransaction
    {
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto->id,
            'transaction_date' => now()->subDays(5),
            'description' => 'Transacción válida de prueba',
            'reference_type' => 'test',
            'reference_id' => 1,
            'status' => 'borrador',
            'created_by' => 1,
        ]);

        $transaction->addEntry([
            'account_id' => $this->assetAccount->id,
            'description' => 'Débito válido',
            'debit_amount' => 1000,
            'credit_amount' => 0,
        ]);

        $transaction->addEntry([
            'account_id' => $this->incomeAccount->id,
            'description' => 'Crédito válido',
            'debit_amount' => 0,
            'credit_amount' => 1000,
        ]);

        return $transaction;
    }

    /**
     * Crea una transacción inválida para pruebas
     */
    private function createInvalidTransaction(): AccountingTransaction
    {
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto->id,
            'transaction_date' => now()->subDays(5),
            'description' => 'Transacción inválida de prueba',
            'status' => 'borrador',
            'created_by' => 1,
        ]);

        // Solo un movimiento (desbalanceada)
        $transaction->addEntry([
            'account_id' => $this->assetAccount->id,
            'description' => 'Movimiento único',
            'debit_amount' => 1000,
            'credit_amount' => 0,
        ]);

        return $transaction;
    }

    /**
     * Crea una transacción válida para un período específico
     */
    private function createValidTransactionForPeriod(int $month, int $year): AccountingTransaction
    {
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto->id,
            'transaction_date' => Carbon::create($year, $month, 15),
            'description' => "Transacción válida {$month}/{$year}",
            'status' => 'contabilizado', // Ya contabilizada
            'created_by' => 1,
        ]);

        $transaction->addEntry([
            'account_id' => $this->assetAccount->id,
            'description' => 'Débito período',
            'debit_amount' => 500,
            'credit_amount' => 0,
        ]);

        $transaction->addEntry([
            'account_id' => $this->incomeAccount->id,
            'description' => 'Crédito período',
            'debit_amount' => 0,
            'credit_amount' => 500,
        ]);

        return $transaction;
    }
}
