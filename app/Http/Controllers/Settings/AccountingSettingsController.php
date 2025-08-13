<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountingSettingsController extends Controller
{
    public function index()
    {
        $hasChartOfAccounts = ChartOfAccounts::count() > 0;
        $accountsCount = ChartOfAccounts::count();

        return Inertia::render('settings/AccountingSettings', [
            'hasChartOfAccounts' => $hasChartOfAccounts,
            'accountsCount' => $accountsCount,
        ]);
    }

    public function initializeAccounts(Request $request)
    {
        // Verificar si ya existen cuentas
        if (ChartOfAccounts::count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'El plan de cuentas ya existe',
            ], 400);
        }

        // Obtener todas las configuraciones de conjunto
        $conjuntoConfigs = ConjuntoConfig::all();

        if ($conjuntoConfigs->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay configuraciones de conjunto disponibles',
            ], 400);
        }

        foreach ($conjuntoConfigs as $conjuntoConfig) {
            $this->createAccountsForConjunto($conjuntoConfig->id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Plan de cuentas creado exitosamente',
        ]);
    }

    private function createAccountsForConjunto(int $conjuntoConfigId): void
    {
        $accounts = $this->getChartOfAccountsData();

        foreach ($accounts as $accountData) {
            $parentId = null;

            if ($accountData['parent_code']) {
                $parent = ChartOfAccounts::where('conjunto_config_id', $conjuntoConfigId)
                    ->where('code', $accountData['parent_code'])
                    ->first();
                $parentId = $parent?->id;
            }

            ChartOfAccounts::create([
                'conjunto_config_id' => $conjuntoConfigId,
                'code' => $accountData['code'],
                'name' => $accountData['name'],
                'description' => $accountData['description'],
                'account_type' => $accountData['account_type'],
                'parent_id' => $parentId,
                'level' => $accountData['level'],
                'is_active' => $accountData['is_active'],
                'requires_third_party' => $accountData['requires_third_party'],
                'nature' => $accountData['nature'],
                'accepts_posting' => $accountData['accepts_posting'],
            ]);
        }
    }

    private function getChartOfAccountsData(): array
    {
        return [
            // NIVEL 1 - CLASES
            ['code' => '1', 'name' => 'ACTIVOS', 'description' => 'Recursos controlados por la entidad', 'account_type' => 'asset', 'parent_code' => null, 'level' => 1, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],
            ['code' => '2', 'name' => 'PASIVOS', 'description' => 'Obligaciones presentes de la entidad', 'account_type' => 'liability', 'parent_code' => null, 'level' => 1, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '3', 'name' => 'PATRIMONIO', 'description' => 'Participación residual en los activos', 'account_type' => 'equity', 'parent_code' => null, 'level' => 1, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '4', 'name' => 'INGRESOS', 'description' => 'Incrementos en los beneficios económicos', 'account_type' => 'income', 'parent_code' => null, 'level' => 1, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '5', 'name' => 'GASTOS', 'description' => 'Decrementos en los beneficios económicos', 'account_type' => 'expense', 'parent_code' => null, 'level' => 1, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],

            // NIVEL 2 - GRUPOS DE ACTIVOS
            ['code' => '11', 'name' => 'DISPONIBLE', 'description' => 'Comprende el efectivo y los equivalentes de efectivo', 'account_type' => 'asset', 'parent_code' => '1', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],
            ['code' => '13', 'name' => 'DEUDORES', 'description' => 'Valores a favor del conjunto por cobrar', 'account_type' => 'asset', 'parent_code' => '1', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],
            ['code' => '15', 'name' => 'INVENTARIOS', 'description' => 'Bienes tangibles destinados para la venta', 'account_type' => 'asset', 'parent_code' => '1', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],
            ['code' => '16', 'name' => 'PROPIEDADES PLANTA Y EQUIPO', 'description' => 'Activos tangibles de uso permanente', 'account_type' => 'asset', 'parent_code' => '1', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],

            // NIVEL 2 - GRUPOS DE PASIVOS
            ['code' => '21', 'name' => 'OBLIGACIONES FINANCIERAS', 'description' => 'Comprende los valores a favor de bancos', 'account_type' => 'liability', 'parent_code' => '2', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '23', 'name' => 'PROVEEDORES', 'description' => 'Comprende los valores adeudados por compras a crédito', 'account_type' => 'liability', 'parent_code' => '2', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '25', 'name' => 'OBLIGACIONES LABORALES', 'description' => 'Comprende los valores adeudados a trabajadores', 'account_type' => 'liability', 'parent_code' => '2', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],

            // NIVEL 2 - GRUPOS DE PATRIMONIO
            ['code' => '31', 'name' => 'CAPITAL SOCIAL', 'description' => 'Comprende el valor del capital aportado', 'account_type' => 'equity', 'parent_code' => '3', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '32', 'name' => 'RESERVAS', 'description' => 'Comprende los valores apropiados como reservas', 'account_type' => 'equity', 'parent_code' => '3', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '36', 'name' => 'RESULTADOS DEL EJERCICIO', 'description' => 'Comprende el resultado obtenido en el ejercicio', 'account_type' => 'equity', 'parent_code' => '3', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],

            // NIVEL 2 - GRUPOS DE INGRESOS
            ['code' => '41', 'name' => 'OPERACIONALES', 'description' => 'Comprende los valores recibidos por actividades de operación', 'account_type' => 'income', 'parent_code' => '4', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '42', 'name' => 'NO OPERACIONALES', 'description' => 'Comprende los ingresos por actividades diferentes', 'account_type' => 'income', 'parent_code' => '4', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],

            // NIVEL 2 - GRUPOS DE GASTOS
            ['code' => '51', 'name' => 'OPERACIONALES DE ADMINISTRACIÓN', 'description' => 'Comprende los gastos ocasionados en la administración', 'account_type' => 'expense', 'parent_code' => '5', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],
            ['code' => '53', 'name' => 'NO OPERACIONALES', 'description' => 'Comprende los gastos ocasionados en actividades diferentes', 'account_type' => 'expense', 'parent_code' => '5', 'level' => 2, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],

            // NIVEL 3 - CUENTAS PRINCIPALES DE ACTIVOS
            ['code' => '1105', 'name' => 'CAJA', 'description' => 'Registra el efectivo disponible en caja', 'account_type' => 'asset', 'parent_code' => '11', 'level' => 3, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],
            ['code' => '1110', 'name' => 'BANCOS', 'description' => 'Registra los valores disponibles en bancos', 'account_type' => 'asset', 'parent_code' => '11', 'level' => 3, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],
            ['code' => '1305', 'name' => 'CLIENTES', 'description' => 'Registra los valores adeudados por propietarios', 'account_type' => 'asset', 'parent_code' => '13', 'level' => 3, 'is_active' => true, 'requires_third_party' => true, 'nature' => 'debit', 'accepts_posting' => false],
            ['code' => '1355', 'name' => 'ANTICIPOS Y AVANCES', 'description' => 'Registra los anticipos entregados', 'account_type' => 'asset', 'parent_code' => '13', 'level' => 3, 'is_active' => true, 'requires_third_party' => true, 'nature' => 'debit', 'accepts_posting' => false],

            // NIVEL 3 - CUENTAS PRINCIPALES DE PASIVOS
            ['code' => '2105', 'name' => 'BANCOS NACIONALES', 'description' => 'Registra las obligaciones con bancos nacionales', 'account_type' => 'liability', 'parent_code' => '21', 'level' => 3, 'is_active' => true, 'requires_third_party' => true, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '2305', 'name' => 'NACIONALES', 'description' => 'Registra las obligaciones con proveedores nacionales', 'account_type' => 'liability', 'parent_code' => '23', 'level' => 3, 'is_active' => true, 'requires_third_party' => true, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '2505', 'name' => 'SALARIOS POR PAGAR', 'description' => 'Registra los salarios pendientes de pago', 'account_type' => 'liability', 'parent_code' => '25', 'level' => 3, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => true],

            // NIVEL 3 - CUENTAS PRINCIPALES DE PATRIMONIO
            ['code' => '3105', 'name' => 'CAPITAL PAGADO', 'description' => 'Registra el capital efectivamente pagado', 'account_type' => 'equity', 'parent_code' => '31', 'level' => 3, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '3205', 'name' => 'RESERVAS OBLIGATORIAS', 'description' => 'Registra las reservas exigidas por ley', 'account_type' => 'equity', 'parent_code' => '32', 'level' => 3, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '3605', 'name' => 'UTILIDAD DEL EJERCICIO', 'description' => 'Registra la utilidad obtenida en el período', 'account_type' => 'equity', 'parent_code' => '36', 'level' => 3, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => true],

            // NIVEL 3 - CUENTAS PRINCIPALES DE INGRESOS
            ['code' => '4135', 'name' => 'COMERCIO AL POR MAYOR Y MENOR', 'description' => 'Registra los ingresos por actividades comerciales', 'account_type' => 'income', 'parent_code' => '41', 'level' => 3, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],
            ['code' => '4210', 'name' => 'FINANCIEROS', 'description' => 'Registra los ingresos por actividades financieras', 'account_type' => 'income', 'parent_code' => '42', 'level' => 3, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => false],

            // NIVEL 3 - CUENTAS PRINCIPALES DE GASTOS
            ['code' => '5105', 'name' => 'GASTOS DE PERSONAL', 'description' => 'Registra los gastos relacionados con el personal', 'account_type' => 'expense', 'parent_code' => '51', 'level' => 3, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],
            ['code' => '5135', 'name' => 'SERVICIOS', 'description' => 'Registra los gastos por servicios contratados', 'account_type' => 'expense', 'parent_code' => '51', 'level' => 3, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],
            ['code' => '5305', 'name' => 'FINANCIEROS', 'description' => 'Registra los gastos por actividades financieras', 'account_type' => 'expense', 'parent_code' => '53', 'level' => 3, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => false],

            // NIVEL 4 - SUBCUENTAS ESPECÍFICAS PARA PROPIEDAD HORIZONTAL

            // Subcuentas de Caja
            ['code' => '110501', 'name' => 'CAJA GENERAL', 'description' => 'Efectivo disponible en caja general del conjunto', 'account_type' => 'asset', 'parent_code' => '1105', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],
            ['code' => '110502', 'name' => 'CAJA MENOR', 'description' => 'Efectivo para gastos menores', 'account_type' => 'asset', 'parent_code' => '1105', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],

            // Subcuentas de Bancos
            ['code' => '111001', 'name' => 'BANCO PRINCIPAL - CUENTA CORRIENTE', 'description' => 'Cuenta corriente principal del conjunto', 'account_type' => 'asset', 'parent_code' => '1110', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],
            ['code' => '111002', 'name' => 'BANCO AHORROS - FONDO RESERVA', 'description' => 'Cuenta de ahorros para fondo de reserva', 'account_type' => 'asset', 'parent_code' => '1110', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],

            // Subcuentas de Clientes (Cartera)
            ['code' => '130501', 'name' => 'CARTERA ADMINISTRACIÓN', 'description' => 'Cartera por cuotas de administración', 'account_type' => 'asset', 'parent_code' => '1305', 'level' => 4, 'is_active' => true, 'requires_third_party' => true, 'nature' => 'debit', 'accepts_posting' => true],
            ['code' => '130502', 'name' => 'CARTERA CUOTAS EXTRAORDINARIAS', 'description' => 'Cartera por cuotas extraordinarias', 'account_type' => 'asset', 'parent_code' => '1305', 'level' => 4, 'is_active' => true, 'requires_third_party' => true, 'nature' => 'debit', 'accepts_posting' => true],
            ['code' => '130503', 'name' => 'CARTERA INTERESES MORA', 'description' => 'Cartera por intereses de mora', 'account_type' => 'asset', 'parent_code' => '1305', 'level' => 4, 'is_active' => true, 'requires_third_party' => true, 'nature' => 'debit', 'accepts_posting' => true],

            // Subcuentas de Proveedores
            ['code' => '230501', 'name' => 'PROVEEDORES DE SERVICIOS', 'description' => 'Obligaciones con proveedores de servicios', 'account_type' => 'liability', 'parent_code' => '2305', 'level' => 4, 'is_active' => true, 'requires_third_party' => true, 'nature' => 'credit', 'accepts_posting' => true],

            // Subcuentas de Patrimonio - Fondo de Reserva
            ['code' => '320501', 'name' => 'FONDO DE RESERVA (LEY 675)', 'description' => 'Fondo de reserva obligatorio según Ley 675', 'account_type' => 'equity', 'parent_code' => '3205', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => true],

            // Subcuentas de Ingresos Específicas para Propiedad Horizontal
            ['code' => '413501', 'name' => 'CUOTAS DE ADMINISTRACIÓN', 'description' => 'Ingresos por cuotas ordinarias de administración', 'account_type' => 'income', 'parent_code' => '4135', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => true],
            ['code' => '413502', 'name' => 'CUOTAS EXTRAORDINARIAS', 'description' => 'Ingresos por cuotas extraordinarias', 'account_type' => 'income', 'parent_code' => '4135', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => true],
            ['code' => '413503', 'name' => 'PARQUEADEROS', 'description' => 'Ingresos por concepto de parqueaderos', 'account_type' => 'income', 'parent_code' => '4135', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => true],
            ['code' => '413505', 'name' => 'MULTAS Y SANCIONES', 'description' => 'Ingresos por multas y sanciones', 'account_type' => 'income', 'parent_code' => '4135', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => true],
            ['code' => '413506', 'name' => 'INTERESES DE MORA', 'description' => 'Ingresos por intereses de mora', 'account_type' => 'income', 'parent_code' => '4135', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'credit', 'accepts_posting' => true],

            // Subcuentas de Gastos Específicas para Propiedad Horizontal
            ['code' => '510501', 'name' => 'SUELDOS Y SALARIOS', 'description' => 'Gastos por sueldos y salarios del personal', 'account_type' => 'expense', 'parent_code' => '5105', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],
            ['code' => '513501', 'name' => 'ENERGÍA ELÉCTRICA', 'description' => 'Gastos por servicio de energía eléctrica', 'account_type' => 'expense', 'parent_code' => '5135', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],
            ['code' => '513502', 'name' => 'ACUEDUCTO Y ALCANTARILLADO', 'description' => 'Gastos por servicios de agua y alcantarillado', 'account_type' => 'expense', 'parent_code' => '5135', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],
            ['code' => '513508', 'name' => 'VIGILANCIA', 'description' => 'Gastos por servicios de vigilancia', 'account_type' => 'expense', 'parent_code' => '5135', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],
            ['code' => '513509', 'name' => 'JARDINERÍA', 'description' => 'Gastos por servicios de jardinería', 'account_type' => 'expense', 'parent_code' => '5135', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],
            ['code' => '513510', 'name' => 'LIMPIEZA ZONAS COMUNES', 'description' => 'Gastos por limpieza de zonas comunes', 'account_type' => 'expense', 'parent_code' => '5135', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],

            // Subcuentas de Gastos Financieros
            ['code' => '530501', 'name' => 'GASTOS BANCARIOS', 'description' => 'Gastos por servicios bancarios', 'account_type' => 'expense', 'parent_code' => '5305', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],

            // Subcuenta específica para Apropiación del Fondo de Reserva (Ley 675)
            ['code' => '530502', 'name' => 'APROPIACIÓN FONDO DE RESERVA', 'description' => 'Gasto por apropiación mensual al fondo de reserva según Ley 675', 'account_type' => 'expense', 'parent_code' => '5305', 'level' => 4, 'is_active' => true, 'requires_third_party' => false, 'nature' => 'debit', 'accepts_posting' => true],
        ];
    }
}
