<?php

namespace Tests\Feature\Commands;

use App\Console\Commands\AppropriateMonthlyReserveFund;
use App\Models\AccountingTransaction;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Tests de integración para el comando AppropriateMonthlyReserveFund
 *
 * Valida el funcionamiento correcto del comando:
 * - Ejecución exitosa con parámetros
 * - Modo dry-run
 * - Manejo de errores
 * - Validación de parámetros
 */
class AppropriateMonthlyReserveFundTest extends TestCase
{
    use RefreshDatabase;

    private ConjuntoConfig $conjunto;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear conjunto de prueba
        $this->conjunto = ConjuntoConfig::factory()->create([
            'name' => 'Conjunto Test Reserva',
        ]);

        // Crear cuentas necesarias
        $this->createRequiredAccounts();
    }

    /** @test */
    public function ejecuta_apropiacion_exitosamente_con_parametros()
    {
        // Arrange: Crear ingresos para el período
        $this->createIncomeTransaction(1000000, 5, 2024);

        // Act: Ejecutar comando
        $exitCode = Artisan::call('reserve-fund:appropriate', [
            '--month' => 5,
            '--year' => 2024,
            '--conjunto' => $this->conjunto->id,
        ]);

        // Assert: Comando exitoso
        $this->assertEquals(0, $exitCode, 'Comando debe terminar exitosamente');

        // Verificar que se creó la transacción
        $this->assertDatabaseHas('accounting_transactions', [
            'conjunto_config_id' => $this->conjunto->id,
            'description' => 'Apropiación mensual fondo de reserva - 5/2024',
            'status' => 'contabilizado',
        ]);

        // Verificar monto apropiado (30% de $1,000,000 = $300,000)
        $transaction = AccountingTransaction::where('conjunto_config_id', $this->conjunto->id)
            ->where('description', 'LIKE', '%apropiación mensual fondo de reserva%')
            ->first();

        $this->assertEquals(300000, $transaction->total_debit);
    }

    /** @test */
    public function ejecuta_en_modo_dry_run_sin_crear_transacciones()
    {
        // Arrange: Crear ingresos para el período
        $this->createIncomeTransaction(800000, 6, 2024);

        // Act: Ejecutar comando en dry-run
        $exitCode = Artisan::call('reserve-fund:appropriate', [
            '--month' => 6,
            '--year' => 2024,
            '--conjunto' => $this->conjunto->id,
            '--dry-run' => true,
        ]);

        // Assert: Comando exitoso pero sin transacciones
        $this->assertEquals(0, $exitCode, 'Comando debe terminar exitosamente');

        // Verificar que NO se creó la transacción
        $this->assertDatabaseMissing('accounting_transactions', [
            'conjunto_config_id' => $this->conjunto->id,
            'description' => 'Apropiación mensual fondo de reserva - 6/2024',
        ]);
    }

    /** @test */
    public function usa_mes_anterior_por_defecto()
    {
        // Arrange: Crear ingresos para el mes anterior
        $previousMonth = now()->subMonth();
        $this->createIncomeTransaction(1200000, $previousMonth->month, $previousMonth->year);

        // Act: Ejecutar comando sin especificar mes
        $exitCode = Artisan::call('reserve-fund:appropriate', [
            '--conjunto' => $this->conjunto->id,
        ]);

        // Assert: Debe procesar el mes anterior
        $this->assertEquals(0, $exitCode, 'Comando debe terminar exitosamente');

        $expectedDescription = "Apropiación mensual fondo de reserva - {$previousMonth->month}/{$previousMonth->year}";
        $this->assertDatabaseHas('accounting_transactions', [
            'conjunto_config_id' => $this->conjunto->id,
            'description' => $expectedDescription,
        ]);
    }

    /** @test */
    public function no_crea_apropiacion_si_ya_existe()
    {
        // Arrange: Crear apropiación existente
        $this->createIncomeTransaction(1000000, 5, 2024);

        // Primera ejecución
        Artisan::call('reserve-fund:appropriate', [
            '--month' => 5,
            '--year' => 2024,
            '--conjunto' => $this->conjunto->id,
        ]);

        $transactionsBefore = AccountingTransaction::count();

        // Act: Segunda ejecución (debe omitirse)
        $exitCode = Artisan::call('reserve-fund:appropriate', [
            '--month' => 5,
            '--year' => 2024,
            '--conjunto' => $this->conjunto->id,
        ]);

        // Assert: No debe crear segunda transacción
        $this->assertEquals(0, $exitCode, 'Comando debe terminar exitosamente');
        $this->assertEquals($transactionsBefore, AccountingTransaction::count(), 'No debe crear transacción adicional');
    }

    /** @test */
    public function procesa_todos_los_conjuntos_si_no_se_especifica()
    {
        // Arrange: Crear segundo conjunto
        $conjunto2 = ConjuntoConfig::factory()->create(['name' => 'Conjunto Test 2']);
        $this->createRequiredAccountsForConjunto($conjunto2->id);

        // Crear ingresos para ambos conjuntos
        $this->createIncomeTransaction(1000000, 5, 2024, $this->conjunto->id);
        $this->createIncomeTransaction(800000, 5, 2024, $conjunto2->id);

        // Act: Ejecutar sin especificar conjunto
        $exitCode = Artisan::call('reserve-fund:appropriate', [
            '--month' => 5,
            '--year' => 2024,
        ]);

        // Assert: Debe procesar ambos conjuntos
        $this->assertEquals(0, $exitCode, 'Comando debe terminar exitosamente');

        $this->assertDatabaseHas('accounting_transactions', [
            'conjunto_config_id' => $this->conjunto->id,
            'description' => 'Apropiación mensual fundo de reserva - 5/2024',
        ]);

        $this->assertDatabaseHas('accounting_transactions', [
            'conjunto_config_id' => $conjunto2->id,
            'description' => 'Apropiación mensual fundo de reserva - 5/2024',
        ]);
    }

    /** @test */
    public function falla_con_mes_invalido()
    {
        // Act: Ejecutar con mes inválido
        $exitCode = Artisan::call('reserve-fund:appropriate', [
            '--month' => 13, // Mes inválido
            '--year' => 2024,
            '--conjunto' => $this->conjunto->id,
        ]);

        // Assert: Debe fallar
        $this->assertEquals(1, $exitCode, 'Comando debe fallar con mes inválido');
    }

    /** @test */
    public function falla_con_conjunto_inexistente()
    {
        // Act: Ejecutar con conjunto inexistente
        $exitCode = Artisan::call('reserve-fund:appropriate', [
            '--month' => 5,
            '--year' => 2024,
            '--conjunto' => 99999, // ID inexistente
        ]);

        // Assert: Debe fallar
        $this->assertEquals(1, $exitCode, 'Comando debe fallar con conjunto inexistente');
    }

    /** @test */
    public function maneja_correctamente_sin_ingresos()
    {
        // Act: Ejecutar sin ingresos para el período
        $exitCode = Artisan::call('reserve-fund:appropriate', [
            '--month' => 5,
            '--year' => 2024,
            '--conjunto' => $this->conjunto->id,
        ]);

        // Assert: Debe terminar exitosamente pero sin crear transacción
        $this->assertEquals(0, $exitCode, 'Comando debe manejar correctamente la falta de ingresos');

        $this->assertDatabaseMissing('accounting_transactions', [
            'conjunto_config_id' => $this->conjunto->id,
            'description' => 'Apropiación mensual fondo de reserva - 5/2024',
        ]);
    }

    /** @test */
    public function fuerza_apropiacion_con_flag_force()
    {
        // Arrange: Crear apropiación existente
        $this->createIncomeTransaction(1000000, 5, 2024);

        Artisan::call('reserve-fund:appropriate', [
            '--month' => 5,
            '--year' => 2024,
            '--conjunto' => $this->conjunto->id,
        ]);

        $transactionsBefore = AccountingTransaction::count();

        // Act: Ejecutar con --force
        $exitCode = Artisan::call('reserve-fund:appropriate', [
            '--month' => 5,
            '--year' => 2024,
            '--conjunto' => $this->conjunto->id,
            '--force' => true,
        ]);

        // Assert: Debe crear segunda transacción
        $this->assertEquals(0, $exitCode, 'Comando con --force debe terminar exitosamente');
        $transactionsAfter = AccountingTransaction::count();
        $this->assertGreaterThan($transactionsBefore, $transactionsAfter, '--force debe crear transacción adicional');
    }

    /* ========== MÉTODOS AUXILIARES ========== */

    /**
     * Crea las cuentas contables necesarias para las pruebas
     */
    private function createRequiredAccounts(): void
    {
        $this->createRequiredAccountsForConjunto($this->conjunto->id);
    }

    /**
     * Crea las cuentas necesarias para un conjunto específico
     */
    private function createRequiredAccountsForConjunto(int $conjuntoId): void
    {
        // Cuenta de ingreso operacional
        ChartOfAccounts::create([
            'conjunto_config_id' => $conjuntoId,
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
            'conjunto_config_id' => $conjuntoId,
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
            'conjunto_config_id' => $conjuntoId,
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
     * Crea una transacción de ingreso para un período específico
     */
    private function createIncomeTransaction(float $amount, int $month, int $year, ?int $conjuntoId = null): void
    {
        $conjuntoId = $conjuntoId ?? $this->conjunto->id;
        $incomeAccount = ChartOfAccounts::where('conjunto_config_id', $conjuntoId)
            ->where('code', '413501')
            ->first();

        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $conjuntoId,
            'transaction_date' => sprintf('%d-%02d-15', $year, $month),
            'description' => "Ingresos test - {$month}/{$year}",
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

        // Crear contrapartida ficticia (débito)
        $transaction->addEntry([
            'account_id' => $incomeAccount->id,
            'description' => "Contrapartida test {$month}/{$year}",
            'debit_amount' => $amount,
            'credit_amount' => 0,
        ]);

        $transaction->post();
    }
}
