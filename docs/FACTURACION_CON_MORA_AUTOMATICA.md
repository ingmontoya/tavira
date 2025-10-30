# Facturación con Mora Automática

## Descripción General

El sistema de facturación mensual ha sido actualizado para incluir automáticamente los intereses de mora en la nueva factura del mes siguiente cuando la factura del mes anterior no ha sido pagada.

## Funcionamiento

### Ciclo de Facturación Normal

1. **Mes 1 (Enero)**: Se genera una factura con la cuota de administración
   - Fecha de facturación: 1 de Enero
   - Fecha de vencimiento: 31 de Enero
   - Conceptos:
     - Administración Mensual: $250,000

2. **Si la factura se paga antes del vencimiento:**
   - **Mes 2 (Febrero)**: Se genera una nueva factura solo con la administración
     - Conceptos:
       - Administración Mensual: $250,000
     - Total: $250,000

### Ciclo de Facturación con Mora

1. **Mes 1 (Enero)**: Se genera una factura con la cuota de administración
   - Fecha de facturación: 1 de Enero
   - Fecha de vencimiento: 31 de Enero
   - Conceptos:
     - Administración Mensual: $250,000
   - Total: $250,000

2. **Si la factura NO se paga antes del vencimiento:**
   - **Mes 2 (Febrero)**: Se genera una nueva factura que incluye:
     - Conceptos:
       - Administración Mensual (Febrero): $250,000
       - Interés de Mora (por Enero impago): $5,000 (2% de $250,000)
     - Total: $255,000

3. **Mes 3 (Marzo)**: Si tampoco se pagó Febrero:
   - Conceptos:
     - Administración Mensual (Marzo): $250,000
     - Interés de Mora (por Febrero impago): $5,100 (2% de $255,000)
   - Total: $255,100

## Configuración

### Porcentaje de Mora

El porcentaje de mora se configura en `Settings > Payment Settings`:

- **late_fee_percentage**: Porcentaje mensual de mora (default: 2.0%)
- **late_fees_enabled**: Habilitar/deshabilitar cálculo de mora (default: true)
- **grace_period_days**: Días de gracia después del vencimiento (default: 0)

### Conceptos de Pago

El sistema utiliza dos conceptos de pago:

1. **Administración Mensual** (type: `monthly_administration`)
   - Se genera siempre para cada apartamento
   - El monto viene del campo `monthly_fee` del apartamento
   - El `monthly_fee` se inicializa con el `administration_fee` del tipo de apartamento

2. **Interés de Mora** (type: `late_fee`)
   - Se genera solo cuando hay una factura del mes anterior sin pagar
   - Se calcula como: `balance_pendiente * (porcentaje_mora / 100)`
   - Se incluye como un ítem adicional en la nueva factura

## Comando de Generación

```bash
php artisan invoices:generate-monthly [--year=YYYY] [--month=MM] [--force]
```

### Parámetros

- `--year`: Año para el cual generar facturas (default: año actual)
- `--month`: Mes para el cual generar facturas (default: mes actual)
- `--force`: Forzar generación incluso si ya existen facturas para ese período

### Ejemplo de Uso

```bash
# Generar facturas para Febrero 2025
php artisan invoices:generate-monthly --year=2025 --month=2

# Forzar regeneración de facturas de Enero 2025
php artisan invoices:generate-monthly --year=2025 --month=1 --force
```

### Salida del Comando

```
Generating monthly invoices for 2025-2...
Procesando 50 apartamentos elegibles...
Usando concepto: Administración Mensual (ID: 1)
Verificando facturas del período anterior: 2025-1
  → Mora aplicada a Apt 101: $5,000.00
  → Mora aplicada a Apt 203: $3,600.00
  → Mora aplicada a Apt 305: $4,800.00
[==========================] 100%

Facturas generadas exitosamente: 50
Facturas con mora aplicada: 15
Período de facturación: Feb 2025
Fecha de vencimiento: 2025-02-28
```

## Lógica de Cálculo de Mora

### Condiciones para Aplicar Mora

La mora se aplica **solo si**:

1. Existe una factura del mes anterior para el mismo apartamento
2. La factura del mes anterior NO está pagada (status != 'pagado')
3. El período de gracia ha finalizado
4. Las moras están habilitadas en la configuración
5. El balance pendiente es mayor a 0

### Cálculo del Monto

```php
// Base de cálculo: balance pendiente de la factura anterior
$baseAmount = $previousInvoice->balance_amount;

// Cálculo de mora
$lateFeeAmount = round($baseAmount * ($late_fee_percentage / 100), 2);
```

### Ejemplo de Cálculo

Si la factura de Enero tiene:
- Subtotal: $250,000
- Pagos parciales: $100,000
- Balance pendiente: $150,000
- Porcentaje de mora: 2%

Entonces la mora en Febrero será:
```
Mora = $150,000 * (2 / 100) = $3,000
```

## Diferencias con el Sistema Anterior

### Sistema Anterior
- Las moras se aplicaban sobre la misma factura
- Se ejecutaba un comando separado: `php artisan late-fees:process`
- Las moras se acumulaban en el campo `late_fees` de la factura original
- La factura del mes siguiente era independiente

### Sistema Nuevo
- Las moras se incluyen automáticamente en la nueva factura del mes siguiente
- No requiere comando adicional
- Cada factura mensual puede tener un ítem de mora por el mes anterior
- Mejor trazabilidad: se ve claramente qué mes generó la mora

## Impacto Contable

Cada ítem de la factura genera su propio asiento contable:

### Factura con Mora

**Factura de Febrero con mora de Enero:**

| Concepto | Cuenta Débito | Cuenta Crédito | Monto |
|----------|---------------|----------------|--------|
| Administración Mensual | Cuentas por Cobrar | Ingresos por Administración | $250,000 |
| Interés de Mora | Cuentas por Cobrar | Ingresos Financieros (Mora) | $5,000 |

Total factura: $255,000

## Consideraciones Importantes

1. **Primera Generación**: Si es la primera vez que se genera una factura para un apartamento, no habrá mora (no hay factura anterior)

2. **Cambio de Año**: El sistema maneja correctamente el cambio de año. Si se generan facturas de Enero 2025, buscará facturas de Diciembre 2024.

3. **Pagos Parciales**: Si una factura tiene pagos parciales, la mora se calcula sobre el `balance_amount`, no sobre el total original.

4. **Múltiples Moras**: Si un apartamento acumula varios meses sin pagar, cada mes generará su propia mora basada en el balance del mes anterior.

5. **Desactivación de Moras**: Si se desactivan las moras en la configuración (`late_fees_enabled = false`), las nuevas facturas no incluirán ítems de mora, pero las facturas ya generadas conservan sus moras.

## Migración desde el Sistema Anterior

Si tienes facturas existentes con moras aplicadas usando el sistema anterior:

1. Las facturas antiguas mantienen sus moras en el campo `late_fees`
2. Las nuevas facturas usarán el sistema de ítems de mora
3. No es necesario hacer ninguna conversión
4. Ambos sistemas son compatibles

## Flujo de Trabajo Recomendado

1. **Inicio de Mes**: Ejecutar el comando de generación de facturas el día 1 de cada mes
2. **Configurar Cron**: Programar el comando para ejecutarse automáticamente
3. **Revisar Resumen**: Revisar el output del comando para ver cuántas moras se aplicaron
4. **Notificaciones**: Configurar notificaciones por email para apartamentos con mora

### Ejemplo de Cron

```bash
# Generar facturas el día 1 de cada mes a las 6:00 AM
0 6 1 * * cd /path/to/tavira && php artisan invoices:generate-monthly
```

## Preguntas Frecuentes

### ¿Qué pasa si un apartamento paga su factura después del vencimiento pero antes de la generación del mes siguiente?

Si la factura se marca como pagada antes de generar las facturas del mes siguiente, no se aplicará mora en la nueva factura.

### ¿Se puede modificar el porcentaje de mora para facturas ya generadas?

No. El porcentaje de mora se aplica al momento de generar la factura. Cambiar la configuración solo afectará las nuevas facturas.

### ¿Cómo se manejan los meses con diferente número de días?

El sistema usa el último día del mes como fecha de vencimiento. Febrero tendrá vencimiento el 28 o 29, otros meses el 30 o 31.

### ¿Se puede regenerar una factura si se aplicó mora incorrectamente?

Sí, usando el parámetro `--force` se pueden eliminar y regenerar las facturas de un período específico. Esto también elimina los asientos contables asociados.

```bash
php artisan invoices:generate-monthly --year=2025 --month=2 --force
```

### ¿La mora se calcula sobre el subtotal o sobre el total con descuentos?

La mora se calcula sobre el `balance_amount`, que es el saldo pendiente después de aplicar descuentos y pagos parciales.
