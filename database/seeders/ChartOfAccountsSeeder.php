<?php

namespace Database\Seeders;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conjuntoConfigs = ConjuntoConfig::all();

        foreach ($conjuntoConfigs as $conjuntoConfig) {
            $this->seedAccountsForConjunto($conjuntoConfig->id);
        }
    }

    private function seedAccountsForConjunto(int $conjuntoConfigId): void
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

            ChartOfAccounts::updateOrCreate(
                [
                    'conjunto_config_id' => $conjuntoConfigId,
                    'code' => $accountData['code'],
                ],
                [
                    'name' => $accountData['name'],
                    'description' => $accountData['description'],
                    'account_type' => $accountData['account_type'],
                    'parent_id' => $parentId,
                    'level' => $accountData['level'],
                    'is_active' => $accountData['is_active'],
                    'requires_third_party' => $accountData['requires_third_party'],
                    'nature' => $accountData['nature'],
                    'accepts_posting' => $accountData['accepts_posting'],
                ]
            );
        }
    }

    private function getChartOfAccountsData(): array
    {
        return $this->getCompleteChartOfAccounts();
    }

    /**
     * Get complete chart of accounts for propiedad horizontal based on Colombian PUC
     */
    private function getCompleteChartOfAccounts(): array
    {
        $accounts = [];

        // Helper function to determine account type and nature
        $getAccountInfo = function($code) {
            $firstDigit = substr($code, 0, 1);
            switch ($firstDigit) {
                case '1':
                    return ['type' => 'asset', 'nature' => 'debit'];
                case '2':
                    return ['type' => 'liability', 'nature' => 'credit'];
                case '3':
                    return ['type' => 'equity', 'nature' => 'credit'];
                case '4':
                    return ['type' => 'income', 'nature' => 'credit'];
                case '5':
                    return ['type' => 'expense', 'nature' => 'debit'];
                case '8':
                    return ['type' => 'asset', 'nature' => 'debit']; // Cuentas de orden deudoras
                case '9':
                    return ['type' => 'liability', 'nature' => 'credit']; // Cuentas de orden acreedoras
                default:
                    return ['type' => 'asset', 'nature' => 'debit'];
            }
        };

        // Helper function to calculate level based on code length
        $calculateLevel = function($code) {
            $length = strlen($code);
            return match ($length) {
                1 => 1,
                2 => 2,
                4 => 3,
                6 => 4,
                8 => 5,
                default => 1,
            };
        };

        // Helper function to get parent code
        $getParentCode = function($code) {
            $length = strlen($code);
            if ($length == 1) return null;
            if ($length == 2) return substr($code, 0, 1);
            if ($length == 4) return substr($code, 0, 2);
            if ($length == 6) return substr($code, 0, 4);
            if ($length == 8) return substr($code, 0, 6);
            return null;
        };

        // PUC data from SQL file
        $pucData = [
            // CLASE 1 - ACTIVO
            ['1', 'ACTIVO', 1],
            ['11', 'EFECTIVO Y SUS EQUIVALENTES (Antes Disponible)', 2],
            ['1105', 'CAJA', 3],
            ['110505', 'CAJA GENERAL', 4],
            ['110510', 'CAJAS MENORES', 4],
            ['1110', 'BANCOS', 3],
            ['111005', 'MONEDA NACIONAL', 4],
            ['1120', 'CUENTAS DE AHORRO', 3],
            ['112005', 'BANCOS', 4],
            ['1125', 'FONDOS', 3],
            ['112515', 'ESPECIALES MONEDA NACIONAL', 4],
            ['11251505', 'FONDO DE IMPREVISTOS (Bien sea en Bancos Ahorros, fiducias, CDT, etc.)', 5],
            ['11251510', 'FONDO PARA COMPRAS Y GASTOS (Para Día Niños, Navidad, etc., que se entrega al Adm. y no a Proveedores)', 5],
            ['12', 'INVERSIONES (A diferencia del Fondo de imprevistos, no tienen destinación y su objeto es rentabilidad)', 2],
            ['1205', 'ACCIONES', 3],
            ['1225', 'CERTIFICADOS', 3],
            ['122505', 'CERTIFICADOS DE DEPOSITO A TERMINO (C.D.T.)', 4],
            ['13', 'DEUDORES', 2],
            ['1305', 'PROPIETARIOS Y/O RESIDENTES', 3],
            ['130505', 'EXPENSAS Y SERVICIOS COMUNES', 4],
            ['13050505', 'CUOTAS DE ADMINISTRACIÓN', 5],
            ['13050510', 'INTERESES DE MORA CUOTAS DE ADMINISTRACIÓN', 5],
            ['13050515', 'CUOTA EXTRA PARA FACHADAS', 5],
            ['13050520', 'INTERESES DE MORA CUOTA EXTRA', 5],
            ['13050525', 'SANCIONES ASAMBLEA', 5],
            ['13050530', 'USO ZONAS COMUNES (Se sugiere una cuenta para cada servicio: BBQ, Salón, Squash, fichas, etc.)', 5],
            ['13050535', 'HONORARIOS DE ABOGADO', 5],
            ['13050540', 'COSTOS Y GASTOS DE COBRANZA (Certificados libertad o vehículo, pólizas, fotocopias, etc.)', 5],
            ['1330', 'ANTICIPOS Y AVANCES', 3],
            ['133005', 'A PROVEEDORES', 4],
            ['133010', 'A CONTRATISTAS', 4],
            ['133015', 'A TRABAJADORES', 4],
            ['1360', 'RECLAMACIONES', 3],
            ['136005', 'A COMPAÑÍAS ASEGURADORAS', 4],
            ['136010', 'REMUCION DE CUENTAS A ADMINISTRADORES (Faltantes por cobrar o legalizar en entrega del cargo)', 4],
            ['1380', 'DEUDORES VARIOS', 3],
            ['138020', 'CUENTAS POR COBRAR DE TERCEROS', 4],
            ['1399', 'DETERIORO DE CARTERA (ANTES PROVISIONES)', 3],
            ['139050', 'PROPIETARIOS Y/O RESIDENTES', 4],
            ['13990505', 'CUOTAS DE ADMINISTRACIÓN', 5],
            ['13990510', 'INTERESES DE MORA', 5],
            ['139910', 'ANTICIPOS RECLAMACIONES Y DEUDORES VARIOS', 4],
            ['15', 'PROPIEDADES PLANTA Y EQUIPO', 2],
            ['1504', 'TERRENOS', 3],
            ['1516', 'CONSTRUCCIONES Y EDIFICACIONES', 3],
            ['151605', 'EDIFICIOS', 4],
            ['1520', 'MAQUINARIA Y EQUIPO', 3],
            ['1524', 'EQUIPO DE OFICINA', 3],
            ['152405', 'MUEBLES Y ENSERES', 4],
            ['152410', 'EQUIPOS', 4],
            ['1528', 'EQUIPO DE COMPUTACIÓN Y COMUNICACIÓN', 3],
            ['1592', 'DEPRECIACIÓN ACUMULADA', 3],
            ['159205', 'CONSTRUCCIONES Y EDIFICACIONES', 4],
            ['159210', 'MAQUINARIA Y EQUIPO', 4],
            ['159215', 'EQUIPO DE OFICINA', 4],
            ['159220', 'EQUIPO DE COMPUTACIÓN Y COMUNICACIÓN', 4],
            ['17', 'DIFERIDOS', 2],
            ['1705', 'GASTOS PAGADOS POR ANTICIPADO', 3],
            ['170520', 'SEGUROS Y FIANZAS', 4],
            ['17052005', 'PÓLIZA DE COPROPIEDADES (No es intangible ni un Cargo diferido)', 5],
            ['18', 'OTROS ACTIVOS', 2],

            // CLASE 2 - PASIVO
            ['2', 'PASIVO', 1],
            ['21', 'OBLIGACIONES FINANCIERAS', 2],
            ['2105', 'BANCOS NACIONALES', 3],
            ['210505', 'SOBREGIBOS (Se deja la cuenta, pero no se deben dar sobregiros en las copropiedades)', 4],
            ['22', 'PROVEEDORES', 2],
            ['2205', 'NACIONALES (Algunas copropiedades usan esta cuenta, pero sugiero llevar todas las CXP al Grupo 2335)', 3],
            ['23', 'CUENTAS POR PAGAR', 2],
            ['2320', 'A CONTRATISTAS', 3],
            ['2335', 'COSTOS Y GASTOS POR PAGAR', 3],
            ['233505', 'GASTOS FINANCIEROS', 4],
            ['233515', 'LIBROS, SUSCRIPCIONES, PERIODICOS Y REVISTAS', 4],
            ['233525', 'HONORARIOS (Recomiendo cuentas individuales por concepto, para facilitar lectura del Balance de Prueba)', 4],
            ['23352505', 'ADMINISTRADOR', 5],
            ['23352510', 'CONTADOR', 5],
            ['23352515', 'REVISOR FISCAL', 5],
            ['23352520', 'ASESORIA JURÍDICA', 5],
            ['233530', 'SERVICIOS TÉCNICOS', 4],
            ['23353005', 'VIGILANCIA', 5],
            ['23353010', 'ÁSEO', 5],
            ['233535', 'SERVICIOS DE MANTENIMIENTO (Recomiendo cuentas individuales por concepto, para facilitar lectura Balance de Prueba)', 4],
            ['23353505', 'ASCENSORES', 5],
            ['23353510', 'MOTOBOMBAS', 5],
            ['23353515', 'PRADOS Y JARDINES', 5],
            ['23353520', '(Adicionar los códigos y conceptos necesarios)', 5],
            ['233545', 'TRANSPORTES, FLETES Y ACARREOS', 4],
            ['233550', 'SERVICIOS PUBLICOS', 4],
            ['233555', 'SEGUROS', 4],
            ['233570', 'CHEQUES GIRADOS PENDIENTES DE COBRO (Para no afectar saldos reales de Bancos y de CXP por partidas conciliar.)', 4],
            ['233595', 'OTROS', 4],
            ['2365', 'RETENCION EN LA FUENTE', 3],
            ['236505', 'SALARIOS Y PAGOS LABORALES', 4],
            ['236515', 'HONORARIOS', 4],
            ['236520', 'COMISIONES', 4],
            ['236525', 'SERVICIOS', 4],
            ['236540', 'COMPRAS', 4],
            ['2370', 'RETENCIONES Y APORTES DE NOMINA', 3],
            ['237005', 'APORTES AL I.S.S.', 4],
            ['237010', 'APORTES AL I.C.B.F., SENA Y C. C. F.', 4],
            ['2380', 'ACREEDORES VARIOS', 3],
            ['238030', 'FONDOS DE CESANTIAS Y/O PENSIONES', 4],
            ['238095', 'OTROS', 4],
            ['2404', 'IMPUESTOS, GRAVAMENES Y TASAS (Solo aplica en algunas copropiedades)', 3],
            ['2408', 'DE RENTA Y COMPLEMENTARIOS', 4],
            ['2505', 'IMPUESTO SOBRE LAS VENTAS POR PAGAR', 3],
            ['2510', 'OBLIGACIONES LABORALES', 3],
            ['2511', 'SALARIOS POR PAGAR', 4],
            ['2520', 'CESANTIAS CONSOLIDADAS', 4],
            ['2525', 'INTERESES SOBRE CESANTIAS', 4],
            ['2540', 'PRIMA DE SERVICIOS', 4],
            ['2705', 'VACACIONES CONSOLIDADAS', 3],
            ['270505', 'INDEMNIZACIONES LABORALES', 4],
            ['270510', 'INFERIDOS', 4],
            ['2805', 'INGRESOS RECIBIDOS POR ANTICIPADO', 3],
            ['2810', 'CUOTAS DE ADMINISTRACIÓN', 4],
            ['281055', 'CUOTAS EXTRAORDINARIAS', 5],
            ['281035', 'OTROS', 4],
            ['28103505', 'PARA OBRAS E INVERSIONES', 5],
            ['2815', 'INGRESOS RECIBIDOS PARA TERCEROS', 3],
            ['281505', 'VALORES RECIBIDOS PARA TERCEROS', 4],
            ['2820', 'CONSIGNACIONES SIN IDENTIFICAR', 3],
            ['2825', 'RETENCIONES A TERCEROS SOBRE CONTRATOS (Usualmente como garantía)', 3],

            // CLASE 3 - PATRIMONIO
            ['3', 'PATRIMONIO', 1],
            ['33', 'RESERVAS', 2],
            ['3305', 'RESERVAS OBLIGATORIAS', 3],
            ['330505', 'FONDO DE IMPREVISTOS', 4],
            ['3310', 'RESERVAS ESTATUTARIAS', 3],
            ['331005', 'PARA REPOSICION DE ACTIVOS', 4],
            ['3315', 'RESERVAS OCASIONALES (Recomiendo su uso para los valores que se llevaban como Provisiones - Antiguo PUC 26)', 3],
            ['331505', 'PARA MANTENIMIENTO', 4],
            ['331510', 'PARA ADMISICION DE ACTIVOS', 4],
            ['331515', 'PARA FUTUROS ENSANCHES', 4],
            ['331520', 'PARA GASTOS EXTRAORDINARIOS', 4],
            ['331545', 'A DISPOSICION DEL MAXIMO ORGANO SOCIAL', 4],
            ['36', 'RESULTADOS DEL EJERCICIO', 2],
            ['3605', 'EXCEDENTE DEL EJERCICIO', 3],
            ['360505', 'EXCEDENTE DEL EJERCICIO', 4],
            ['3610', 'DEFICIT DEL EJERCICIO', 3],
            ['361005', 'DEFICIT DEL EJERCICIO', 4],
            ['37', 'RESULTADOS DE EJERCICIOS ANTERIORES', 2],
            ['3705', 'EXCEDENTES ACUMULADOS', 3],
            ['3710', 'DEFICITS ACUMULADAS', 3],

            // CLASE 4 - INGRESOS
            ['4', 'INGRESOS', 1],
            ['41', 'EXPENSOS Y SERVICIOS COMUNES (Sugiero eliminar antiguo OPERACIONALES, pues todo ingreso y gasto es Operacional)', 2],
            ['4170', 'OTRAS ACTIVIDADES DE SERVICIOS COMUNITARIOS, SOCIALES Y PERSONALES', 3],
            ['417005', 'CUOTAS DE ADMINISTRACIÓN', 4],
            ['417010', 'INTERESES DE MORA CUOTAS DE ADMINISTRACIÓN', 4],
            ['417015', 'CUOTA EXTRA PARA FACHADAS (Aunque recomiendo su manejo en el Grupo 28)', 4],
            ['417020', 'INTERESES DE MORA CUOTA EXTRA', 4],
            ['417025', 'SANCIONES ASAMBLEA', 4],
            ['417030', 'USO ZONAS COMUNES (Se sugiere una cuenta para cada servicio: BBQ, Salón social, Squash, Fichas, etc.)', 4],
            ['41703005', 'SALON SOCIAL', 5],
            ['417035', 'INDEMNIZACIÓN SEGUROS', 4],
            ['417040', 'POR RECLAMACIONES', 4],
            ['417045', 'POR REINTEGRO PROVISIONES', 4],
            ['417050', '', 4],
            ['417056', 'REINTEGRO DE OTROS COSTOS Y GASTOS', 4],
            ['417065', 'POR INCAPACIDADES I.S.S.', 4],
            ['417070', '', 4],
            ['417085', 'APROVECHAMIENTOS', 4],
            ['41708505', 'RECICLAJE', 5],
            ['41708510', 'AJUSTE AL PESO O AL MIL', 5],
            ['41708515', 'DONACIONES', 5],
            ['417095', 'INGRESOS DE EJERCICIOS ANTERIORES', 4],
            ['41709505', 'RENDIMIENTOS BANCARIOS', 5],
            ['4175', 'DESCUENTOS (DB) (Elimina antigua denominación de Devoluciones Rebajas y Descuentos)', 3],
            ['417505', 'DESCUENTO PRONTO PAGO ADMINISTRACIÓN', 4],
            ['417510', 'CONDONACIÓN INTERESES ASAMBLEA', 4],

            // CLASE 5 - GASTOS
            ['5', 'GASTOS', 1],
            ['51', 'GASTOS DE ADMINISTRACIÓN (Sugiero eliminar antiguo OPERACIONALES, pues todo ingreso y gasto es Operacional)', 2],
            ['5105', 'GASTOS DE PERSONAL', 3],
            ['510506', 'SUELDOS', 4],
            ['510515', 'HORAS EXTRAS Y RECARGOS', 4],
            ['510524', 'INCAPACIDADES', 4],
            ['510527', 'AUXILIO DE TRANSPORTE', 4],
            ['510530', 'CESANTIAS', 4],
            ['510533', 'INTERESES SOBRE CESANTIAS', 4],
            ['510536', 'PRIMA DE SERVICIOS', 4],
            ['510539', 'VACACIONES', 4],
            ['510551', 'DOTACIÓN Y SUMINISTRO A TRABAJADORES', 4],
            ['510560', 'INDEMNIZACIONES LABORALES', 4],
            ['510569', 'APORTES AL I.S.S', 4],
            ['510572', 'APORTES CAJAS DE COMPENSACIÓN FAMILIAR', 4],
            ['510575', 'APORTES I.C.B.F.', 4],
            ['510578', 'SENA', 4],
            ['5110', 'HONORARIOS', 3],
            ['511005', 'ADMINISTRACION', 4],
            ['511010', 'REVISORIA FISCAL', 4],
            ['511015', 'CONTABILIDAD', 4],
            ['511025', 'ASESORIA JURÍDICA', 4],
            ['5115', 'IMPUESTOS', 3],
            ['511570', 'IVA DESCONTABLE', 4],
            ['5120', 'ARRENDAMIENTOS', 3],
            ['5125', 'CONTRIBUCIONES Y AFILIACIONES', 3],
            ['5130', 'SEGUROS', 3],
            ['513010', 'DE COPROPIEDADES', 4],
            ['5135', 'SERVICIOS', 3],
            ['513505', 'ÁSEO', 4],
            ['513510', 'VIGILANCIA', 4],
            ['513525', 'ACUEDUCTO Y ALCANTARILLADO', 4],
            ['513530', 'ENERGIA ELECTRICA', 4],
            ['513545', 'TELEFONO', 4],
            ['513546', 'TELEVISIÓN E INTERNET', 4],
            ['513555', 'GAS', 4],
            ['5140', 'GASTOS LEGALES', 3],
            ['514005', 'NOTARIALES', 4],
            ['514015', 'TRÁMITES Y LICENCIAS', 4],
            ['5145', 'MANTENIMIENTO Y REPARACIONES', 3],
            ['514505', 'PRADOS Y JARDINES', 4],
            ['514510', 'CONSTRUCCIONES Y EDIFICACIONES', 4],
            ['514515', 'MAQUINARIA Y EQUIPO', 4],
            ['51451505', 'ASCENSORES', 5],
            ['51451510', 'MOTOBOMBAS', 5],
            ['51451515', 'PLANTA ELÉCTRICA (INCLUYE COMBUSTIBLES Y LUBRICANTES)', 5],
            ['51451520', 'TANQUES DE AGUA POTABLE', 5],
            ['51451525', 'CAJAS DE AGUAS NEGRAS', 5],
            ['51451530', 'EQUIPO DE COMPUTO', 5],
            ['51451535', 'EQUIPO DE OFICINA', 5],
            ['51451540', 'CITOFONOS', 5],
            ['51451545', 'CCTV', 5],
            ['51451550', 'REPARACIONES LOCATIVAS', 5],
            ['514525', 'CERRAJERIA Y SIMILARES', 4],
            ['514530', 'ELÉCTRICOS Y BOMBILLOS', 4],
            ['514535', 'EXTINTORES', 4],
            ['514560', 'FUMIGACIÓN Y ROEBORES', 4],
            ['514565', 'MANTENIMIENTO CUBIERTAS Y FACHADAS', 4],
            ['514570', 'ADECUACIÓN E INSTALACIÓN (Se suprime pues este concepto aplica solo a la adecuación inicial de instalaciones)', 4],
            ['5155', 'GASTO DE VIAJE', 3],
            ['5160', 'DEPRECIACIONES', 3],
            ['516005', 'CONSTRUCCIONES Y EDIFICACIONES', 4],
            ['516010', 'MAQUINARIA Y EQUIPO', 4],
            ['516015', 'EQUIPO DE OFICINA', 4],
            ['516020', 'EQUIPO DE COMPUTACIÓN Y COMUNICACIÓN', 4],
            ['5195', 'OTROS GASTOS DE FUNCIONAMIENTO (SE ELIMINA DIVERSOS)', 3],
            ['519505', 'BONIFICACIONES', 4],
            ['519510', 'LIBROS, SUSCRIPCIONES, PERIODICOS Y REVISTAS', 4],
            ['519515', 'MÚSICA AMBIENTAL - DERECHOS SAYCO', 4],
            ['519525', 'ELEMENTOS DE ASEO Y CAFETERIA', 4],
            ['51952505', 'ÁSEO', 5],
            ['51952510', 'CAFETERIA', 5],
            ['519530', 'ÚTILES, PAPELERIA Y FOTOCOPIAS', 4],
            ['519535', '', 4],
            ['519545', 'TRANSPORTES Y ACARREOS', 4],
            ['51954505', 'TRAJS BUSES Y PARQUEADEROS', 5],
            ['519550', 'FINANCIEROS', 4],
            ['51955005', 'GASTOS BANCARIOS CHEQUERAS Y SIMILARES', 5],
            ['51955010', 'COMISIONES', 5],
            ['51955015', 'INTERESES', 5],
            ['519560', 'COSTOS Y GASTOS DE EJERCICIOS ANTERIORES', 4],
            ['519565', 'AJUSTE AL PESO O AL MIL', 4],
            ['519570', 'INDEMNIZACIÓN POR DANOS A TERCEROS', 4],
            ['519575', 'FONDO DE IMPREVISTOS', 4],
            ['5199', 'DETERIORO DE CARTERA', 3],
            ['519910', 'EXPENSAS Y SERVICIOS COMUNES', 4],
            ['519915', 'ANTICIPOS RECLAMACIONES Y DEUDORES VARIOS', 4],

            // CLASE 8 - CUENTAS DE ORDEN DEUDORAS
            ['8', 'CUENTAS DE ORDEN DEUDORAS', 1],
            ['81', 'DERECHOS CONTINGENTES', 2],
            ['8105', 'BIENES Y VALORES ENTREGADOS EN CUSTODIA', 3],
            ['810505', 'VALORES MOBILIARIOS', 4],
            ['810510', 'BIENES MUEBLES', 4],
            ['8110', 'BIENES Y VALORES ENTREGADOS EN GARANTIA', 3],
            ['8115', 'BIENES Y VALORES EN PODER DE TERCEROS', 3],
            ['8120', 'LITIGIOS Y/O DEMANDAS', 3],
            ['812005', 'EJECUTIVOS', 4],
            ['812010', 'INCUMPLIMIENTO DE CONTRATOS', 4],
            ['83', 'DEUDORAS DE CONTROL', 2],
            ['8305', 'BIENES RECIBIDOS EN ARRENDAMIENTO FINANCIERO', 3],
            ['830505', 'BIENES MUEBLES', 4],
            ['830510', 'BIENES INMUEBLES', 4],
            ['8315', 'PROPIEDADES PLANTA Y EQUIPO TOTALMENTE DEPRECIADOS, AGOTADOS Y/O AMORTIZADOS', 3],
            ['831516', 'CONSTRUCCIONES Y EDIFICACIONES', 4],
            ['831520', 'MAQUINARIA Y EQUIPO', 4],
            ['831524', 'EQUIPO DE OFICINA', 4],
            ['831528', 'EQUIPO DE COMPUTACION Y COMUNICACION', 4],
            ['8325', 'ACTIVOS CASTIGADOS', 3],
            ['832510', 'DEUDORAS', 4],
            ['832595', 'OTROS ACTIVOS', 4],
            ['8395', 'OTRAS CUENTAS DEUDORAS DE CONTROL', 3],
            ['839505', 'CHEQUES POSTFECHADOS', 4],
            ['839515', 'CHEQUES DEVUELTOS', 4],
            ['839525', 'INTERESES SOBRE DEUDAS VENCIDAS', 4],
            ['839915', 'PROPIEDADES PLANTA Y EQUIPO', 4],
            ['839920', 'INTANGIBLES', 4],
            ['839925', 'CARGOS DIFERIDOS', 4],
            ['839995', 'OTROS ACTIVOS', 4],
            ['84', 'DERECHOS CONTINGENTES POR CONTRA (CR)', 2],
            ['8401', '', 3],
            ['8499', '', 3],
            ['86', 'DEUDORAS DE CONTROL POR CONTRA (CR)', 2],
            ['8601', '', 3],
            ['8699', '', 3],

            // CLASE 9 - CUENTAS DE ORDEN ACREEDORAS
            ['9', 'CUENTAS DE ORDEN ACREEDORAS', 1],
            ['91', 'RESPONSABILIDADES CONTINGENTES', 2],
            ['9105', 'BIENES Y VALORES RECIBIDOS EN CUSTODIA', 3],
            ['910505', 'VALORES MOBILIARIOS', 4],
            ['910510', 'BIENES MUEBLES', 4],
            ['9110', 'BIENES Y VALORES RECIBIDOS EN GARANTIA', 3],
            ['911005', 'VALORES MOBILIARIOS', 4],
            ['911010', 'BIENES MUEBLES', 4],
            ['911015', 'BIENES INMUEBLES', 4],
            ['9115', 'BIENES Y VALORES RECIBIDOS DE TERCEROS', 3],
            ['911505', 'EN ARRENDAMIENTO', 4],
            ['911510', 'EN PRESTAMO', 4],
            ['911515', 'EN DEPOSITO', 4],
            ['911520', 'EN CONSIGNACION', 4],
            ['911525', 'EN COMODATO', 4],
            ['9120', 'LITIGIOS Y/O DEMANDAS', 3],
            ['912005', 'LABORALES', 4],
            ['9195', 'OTRAS RESPONSABILIDADES CONTINGENTES', 3],
            ['93', 'ACREEDORAS DE CONTROL', 2],
            ['94', 'RESPONSABILIDADES CONTINGENTES POR CONTRA (DB)', 2],
            ['9401', '', 3],
            ['9499', '', 3],
            ['96', 'ACREEDORAS DE CONTROL POR CONTRA (DB)', 2],
            ['9601', '', 3],
            ['9699', '', 3],
        ];

        // Process PUC data into account array
        foreach ($pucData as $data) {
            $code = $data[0];
            $name = $data[1];
            // Ignore the level from SQL, calculate it from code length
            $level = $calculateLevel($code);

            $info = $getAccountInfo($code);
            $parentCode = $getParentCode($code);

            // Determine if account accepts posting (only level 4 and 5 for most accounts)
            $acceptsPosting = in_array($level, [4, 5]) && !empty($name);

            // Determine if requires third party (deudores and proveedores)
            $requiresThirdParty = in_array(substr($code, 0, 2), ['13', '23']) && $level >= 3;

            $accounts[] = [
                'code' => $code,
                'name' => $name,
                'description' => $name,
                'account_type' => $info['type'],
                'parent_code' => $parentCode,
                'level' => $level,
                'is_active' => true,
                'requires_third_party' => $requiresThirdParty,
                'nature' => $info['nature'],
                'accepts_posting' => $acceptsPosting,
            ];
        }

        return $accounts;
    }
}
