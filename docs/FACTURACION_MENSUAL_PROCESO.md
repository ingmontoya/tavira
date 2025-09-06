# Proceso de Facturación Mensual - Tavira

## ✅ Validación Completa del Sistema

Este documento describe el proceso **validado y funcional** de facturación mensual automática en Tavira, incluyendo la integración contable automática.

## Comando Principal

```bash
php artisan invoices:generate-monthly --month=12 --year=2024
```

### Opciones Disponibles
- `--year`: Año para generar facturas (por defecto: año actual)
- `--month`: Mes para generar facturas (por defecto: mes actual)
- `--force`: Forzar generación aunque ya existan facturas

## Prerequisitos del Sistema

### 1. Configuración Básica Requerida
- ✅ **ConjuntoConfig activo** (is_active = true)
- ✅ **Apartamentos configurados** con monthly_fee > 0
- ✅ **PaymentConcept** de tipo 'monthly_administration' activo
- ✅ **ChartOfAccounts** poblado con cuentas contables

### 2. Cuentas Contables Necesarias
El sistema requiere estas cuentas contables para funcionar:

| Código | Nombre | Tipo | Naturaleza |
|--------|--------|------|------------|
| 130501 | CARTERA ADMINISTRACIÓN | Activo | Débito |
| 413501 | CUOTAS DE ADMINISTRACIÓN | Ingreso | Crédito |
| 130502 | CARTERA CUOTAS EXTRAORDINARIAS | Activo | Débito |
| 413502 | CUOTAS EXTRAORDINARIAS | Ingreso | Crédito |
| 130503 | CARTERA INTERESES MORA | Activo | Débito |
| 413505 | MULTAS Y SANCIONES | Ingreso | Crédito |

### 3. Orden de Ejecución de Seeders
```bash
# 1. Configuración básica
php artisan migrate:fresh --seed

# 2. Chart of Accounts (DESPUÉS de tener conjunto activo)
php artisan db:seed --class=ChartOfAccountsSeeder

# 3. Payment Concepts
php artisan db:seed --class=PaymentConceptSeeder

# 4. Crear conjunto y apartamentos (usar TestConjuntoSeeder)
php artisan db:seed --class=TestConjuntoSeeder
```

## Flujo de Facturación Mensual

### 1. Validaciones Iniciales
- ✅ Validar año (2020-2030) y mes (1-12)
- ✅ Verificar conjunto activo existente
- ✅ Manejar facturas duplicadas (con --force para sobrescribir)

### 2. Generación de Facturas
- ✅ Procesar apartamentos ocupados ('Occupied', 'Available')
- ✅ Crear factura por apartamento con cuota de administración
- ✅ Calcular totales automáticamente
- ✅ Establecer fecha de vencimiento (último día del mes)

### 3. Contabilización Automática ⭐
**El sistema genera automáticamente:**

```
Por cada factura creada:
  Débito:  Cartera Administración (130501)  $XXX,XXX
  Crédito: Cuotas de Administración (413501) $XXX,XXX
```

- ✅ **Estado**: `posted` (contabilizada automáticamente)
- ✅ **Balanceada**: Débito = Crédito
- ✅ **Referenciada**: reference_type = 'invoice', reference_id = {invoice_id}

## Resultados de Prueba Validados

### Escenario de Prueba
- **Conjunto**: "Conjunto de Prueba Tavira"
- **Apartamentos**: 20 (10 Tipo A + 10 Tipo B)
- **Período**: Diciembre 2024

### Resultados
- ✅ **20 facturas** generadas exitosamente
- ✅ **20 transacciones contables** automáticas
- ✅ **Estado**: Todas en 'posted' (contabilizadas)
- ✅ **Balance**: Todas balanceadas (débito = crédito)
- ✅ **Integridad**: Sin errores en el proceso

### Ejemplo de Transacción Generada
```
Transaction ID: 1
Status: posted
Total Debit: 180,000.00
Total Credit: 180,000.00
Entries: 2
Is Balanced: Yes
```

## Monitoreo y Verificación

### Comandos de Verificación
```bash
# Verificar facturas generadas
php artisan tinker --execute="echo 'Facturas: ' . App\Models\Invoice::count();"

# Verificar transacciones contables
php artisan tinker --execute="echo 'Transacciones: ' . App\Models\AccountingTransaction::count();"

# Verificar estados de transacciones
php artisan tinker --execute="dd(App\Models\AccountingTransaction::selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status')->toArray());"
```

### Consultas SQL Útiles
```sql
-- Resumen de facturación mensual
SELECT 
    CONCAT(at.name, ' - ', apt.tower) as tipo_apartamento,
    COUNT(i.id) as facturas_generadas,
    SUM(i.total_amount) as total_facturado,
    AVG(i.total_amount) as promedio_factura
FROM invoices i
JOIN apartments apt ON i.apartment_id = apt.id
JOIN apartment_types at ON apt.apartment_type_id = at.id
WHERE i.type = 'monthly'
GROUP BY at.name, apt.tower
ORDER BY at.name, apt.tower;

-- Verificar integridad contable
SELECT 
    COUNT(*) as total_transacciones,
    SUM(CASE WHEN status = 'posted' THEN 1 ELSE 0 END) as contabilizadas,
    SUM(CASE WHEN total_debit = total_credit THEN 1 ELSE 0 END) as balanceadas
FROM accounting_transactions
WHERE reference_type = 'invoice';
```

## Notas Importantes para Producción

### 1. ⚠️ Secuencia Crítica
**ORDEN OBLIGATORIO de configuración:**
1. Crear ConjuntoConfig activo
2. Ejecutar ChartOfAccountsSeeder
3. Ejecutar PaymentConceptSeeder
4. Crear apartamentos con monthly_fee configurado

### 2. 🔄 Automatización Recomendada
```bash
# Comando cron para ejecutar el primer día de cada mes
0 0 1 * * cd /path/to/Tavira && php artisan invoices:generate-monthly
```

### 3. 🚨 Validaciones Pre-Ejecución
Antes de ejecutar en producción, verificar:
- [ ] Chart of Accounts poblado
- [ ] PaymentConcepts activos
- [ ] Apartamentos con monthly_fee > 0
- [ ] Conjunto activo configurado

### 4. 📊 Monitoreo Post-Ejecución
Después de cada ejecución, verificar:
- [ ] Facturas = Apartamentos elegibles
- [ ] Transacciones contables = Facturas
- [ ] Todas transacciones en estado "posted"
- [ ] Balance contable correcto

## Estado del Sistema de Pagos

**Nota**: Las transacciones contables de **pagos** no se generan automáticamente en la versión actual. Solo se genera la contabilización al momento de crear facturas. 

Para implementar contabilización automática de pagos sería necesario:
1. Agregar eventos al modelo PaymentApplication
2. Crear listener GenerateAccountingEntryFromPayment
3. Registrar el listener en EventServiceProvider

## Conclusión

✅ **El sistema de facturación mensual funciona correctamente y de manera consistente**

- Generación automática de facturas ✅
- Contabilización automática ✅
- Integridad de datos ✅
- Balances contables correctos ✅

El comando `invoices:generate-monthly` está listo para producción siguiendo los prerequisitos documentados.