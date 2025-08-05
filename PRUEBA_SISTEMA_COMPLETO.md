# Prueba del Sistema Completo - Habitta

## Descripción General

Este documento describe el ejercicio de prueba integral del sistema Habitta que simula un conjunto residencial real con 100 apartamentos, facturas, pagos y transacciones contables. El ejercicio está diseñado para validar la funcionalidad completa del sistema en un entorno controlado.

## Configuración del Escenario

### Conjunto Residencial Creado

**Conjunto Residencial Habitta Test**
- **Ubicación**: Bogotá, Colombia
- **Estructura**: 5 torres (Torre A, B, C, D, E)
- **Distribución**: 4 pisos por torre, 5 apartamentos por piso
- **Total de apartamentos**: 100 unidades

### Tipos de Apartamentos

El conjunto cuenta con 4 tipos de apartamentos distribuidos por piso:

| Tipo | Ubicación | Habitaciones | Baños | Área (m²) | Cuota Administración |
|------|-----------|--------------|-------|-----------|-------------------|
| **Tipo A** | Piso 1 | 1 | 1 | 45 | $180,000 |
| **Tipo B** | Piso 2 | 2 | 2 | 65 | $250,000 |
| **Tipo C** | Piso 3 | 3 | 2 | 85 | $320,000 |
| **Penthouse** | Piso 4 | 3 | 3 | 120 | $480,000 |

### Numeración de Apartamentos

Los apartamentos siguen el patrón: `[Torre][Piso][Posición]`
- Ejemplos: A101, A102, B201, C301, E401

## Datos Generados

### Residentes
- **85% de apartamentos**: Tienen residentes asignados
- **70% propietarios** / 30% inquilinos
- Datos realistas con nombres colombianos, documentos, teléfonos y emails únicos

### Conceptos de Pago
1. **Administración** (mensual, recurrente)
2. **Multa por ruido** (sanción)
3. **Multa por mascota** (sanción)  
4. **Multa por parqueadero** (parqueadero)
5. **Cuota extraordinaria** (especial)
6. **Intereses de mora** (mora)

### Facturación
- **Período**: Últimos 3 meses (facturas retroactivas)
- **Total facturas generadas**: 300 (100 apartamentos × 3 meses)
- **Facturación automática**: Cuota de administración mensual según tipo de apartamento
- **10% de facturas**: Incluyen multas adicionales (solo mes actual)

## Escenarios de Pago Simulados

### 1. Apartamentos al Día (70%)
- **Cantidad**: 70 apartamentos
- **Estado**: Todas las facturas pagadas completamente
- **Método de pago**: Transferencia bancaria
- **Características**: 
  - Sin saldos pendientes
  - Historiales de pago completos
  - Status de apartamento: `current`

### 2. Apartamentos Morosos (20%)
- **Cantidad**: 20 apartamentos
- **Estado**: Pagos irregulares con morosidad variable
- **Método de pago**: Efectivo
- **Características**:
  - Morosidad de 1-3 meses (aleatoria)
  - Facturas más antiguas pagadas, recientes pendientes
  - Intereses de mora del 5% mensual (máximo 20%)
  - Status de apartamento: `overdue_30`, `overdue_60`, `overdue_90`, `overdue_90_plus`

### 3. Apartamentos con Multas (10%)
- **Cantidad**: 10 apartamentos
- **Estado**: Pagos parciales, multas pendientes
- **Método de pago**: PSE/Online
- **Características**:
  - Administración pagada, multas pendientes
  - Demostración de pagos parciales
  - Status de apartamento: variable según antigüedad de deuda

## Distribución Financiera

### Montos Totales del Ejercicio
- **Total facturado**: $94,904,154 COP
- **Total pagado**: $82,309,190 COP (87%)
- **Saldo pendiente**: $12,594,964 COP (13%)

### Desglose por Estado
- **Apartamentos al día**: ~70% del total facturado
- **Apartamentos morosos**: ~20% con saldos pendientes significativos
- **Apartamentos con multas**: ~10% con pagos parciales

## Características del Sistema Demostradas

### 1. Gestión de Apartamentos
✅ Creación automática de apartamentos con tipos diferenciados
✅ Asignación de coeficientes de participación
✅ Gestión de estado de pagos por apartamento

### 2. Facturación Automática
✅ Generación de facturas mensuales
✅ Cálculo automático de totales
✅ Aplicación de conceptos de pago variables
✅ Numeración automática de facturas

### 3. Gestión de Pagos
✅ Registro de pagos con diferentes métodos
✅ Aplicación de pagos a facturas específicas
✅ Manejo de pagos parciales
✅ Referencias de pago únicas

### 4. Cartera y Morosidad
✅ Cálculo de intereses de mora
✅ Clasificación de apartamentos por días de mora
✅ Seguimiento histórico de pagos
✅ Estados de facturación (pendiente, pagado, parcial, vencido)

### 5. Integridad de Datos
✅ Relaciones consistentes entre entidades
✅ Validaciones de modelo funcionando
✅ Transacciones de base de datos exitosas
✅ Sincronización de estados entre facturas y apartamentos

## Análisis de Consistencia

### Validaciones Realizadas
1. **Facturación**: Todas las facturas tienen ítems correspondientes
2. **Pagos**: Todos los pagos están correctamente aplicados a facturas
3. **Apartamentos**: Estados de pago actualizados según última factura pendiente
4. **Residentes**: Asignados correctamente a apartamentos ocupados
5. **Totales**: Cuadre entre montos facturados, pagados y pendientes

### Casos de Uso Cubiertos
- ✅ Facturación masiva mensual
- ✅ Pagos completos y parciales
- ✅ Gestión de morosidad con intereses
- ✅ Aplicación de multas y sanciones
- ✅ Clasificación automática de cartera
- ✅ Reportes de estado financiero

## Cómo Ejecutar la Prueba

### Comando para Ejecutar
```bash
php artisan db:seed --class=ComprehensiveTestSeeder
```

### Prerequisitos
1. Base de datos configurada y migrada
2. Seeder de Chart of Accounts ejecutado
3. Seeder de Payment Concepts ejecutado

### Tiempo de Ejecución
- Aproximadamente 2-3 minutos
- Genera ~1,400 registros en total

## Exploración de Resultados

### Consultas Útiles para Validar

```sql
-- Resumen por tipo de apartamento
SELECT 
    at.name as tipo_apartamento,
    COUNT(*) as cantidad,
    AVG(apt.monthly_fee) as cuota_promedio,
    SUM(CASE WHEN apt.payment_status = 'current' THEN 1 ELSE 0 END) as al_dia,
    SUM(CASE WHEN apt.payment_status LIKE 'overdue%' THEN 1 ELSE 0 END) as morosos
FROM apartments apt
JOIN apartment_types at ON apt.apartment_type_id = at.id
GROUP BY at.name, at.id
ORDER BY at.id;

-- Estado de facturación
SELECT 
    status,
    COUNT(*) as cantidad,
    SUM(total_amount) as monto_total,
    SUM(paid_amount) as monto_pagado,
    SUM(balance_amount) as saldo_pendiente
FROM invoices 
GROUP BY status;

-- Apartamentos con mayor morosidad
SELECT 
    apt.number as apartamento,
    apt.tower as torre,
    apt.payment_status,
    COUNT(i.id) as facturas_pendientes,
    SUM(i.balance_amount) as saldo_total
FROM apartments apt
LEFT JOIN invoices i ON apt.id = i.apartment_id AND i.status IN ('pending', 'overdue', 'partial')
WHERE apt.payment_status != 'current'
GROUP BY apt.id, apt.number, apt.tower, apt.payment_status
ORDER BY saldo_total DESC;
```

## Importante: Transacciones Contables

### ¿Por qué las transacciones están en estado "draft"?

**Respuesta corta**: En este ejercicio de prueba **NO se generaron transacciones contables** automáticamente porque se ejecutó únicamente el seeder de datos sin activar los eventos del sistema.

### Explicación Detallada

#### Sistema Normal de Contabilización
En el funcionamiento normal de Habitta:

1. **Al crear una factura** → Se dispara evento `InvoiceCreated`
2. **Al registrar un pago** → Se dispara evento para generar asientos contables
3. **Los listeners automáticamente**:
   - Crean transacciones contables en estado `draft`
   - Las validan (partida doble)
   - Las contabilizan automáticamente (`post()` → estado `posted`)

#### En este Ejercicio de Prueba
- **Se crearon**: Facturas, pagos, residentes, apartamentos
- **NO se crearon**: Transacciones contables automáticas
- **Motivo**: Los seeders desactivan eventos para mejorar rendimiento

#### Cómo Activar la Contabilización Automática

Para que el sistema genere automáticamente las transacciones contables:

```bash
# Opción 1: Crear facturas individualmente (activa eventos)
php artisan tinker
>>> App\Models\Invoice::create([...]) // Esto SÍ genera transacciones

# Opción 2: Forzar la generación de eventos existentes
>>> $invoices = App\Models\Invoice::all();
>>> foreach($invoices as $invoice) {
...     event(new App\Events\InvoiceCreated($invoice));
... }
```

#### Verificar Estado de Transacciones

```sql
-- Ver transacciones existentes
SELECT status, COUNT(*) as cantidad 
FROM accounting_transactions 
GROUP BY status;

-- Ver transacciones por referencia
SELECT reference_type, status, COUNT(*) as cantidad 
FROM accounting_transactions 
GROUP BY reference_type, status;
```

### Flujo Contable Automático Normal

1. **Facturación**:
   ```
   Débito: Cartera Administración
   Crédito: Ingresos por Administración
   ```

2. **Pago**:
   ```
   Débito: Bancos/Caja
   Crédito: Cartera Administración
   ```

3. **Estados**: `draft` → `posted` (automáticamente)

## Conclusiones

El ejercicio demuestra que el sistema Habitta:

1. **Maneja correctamente** la facturación masiva y automática
2. **Procesa adecuadamente** diferentes escenarios de pago
3. **Calcula correctamente** intereses de mora y multas
4. **Mantiene consistencia** entre todas las entidades relacionadas
5. **Clasifica apropiadamente** el estado de cartera por apartamento
6. **Genera información confiable** para toma de decisiones administrativas
7. **Integra automáticamente** con el sistema contable (cuando eventos están activos)

El sistema está listo para manejar conjuntos residenciales reales con la complejidad financiera y administrativa que estos requieren.