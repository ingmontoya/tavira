# Cuotas Extraordinarias - Documentación Completa

## Resumen General

Se ha implementado un sistema completo de **Cuotas Extraordinarias** para gestionar proyectos especiales en conjuntos residenciales (ej: pintura de fachadas, reparación de transformador, etc.). El sistema permite dividir el costo total entre los propietarios y cobrar en cuotas mensuales automáticas.

## ✅ IMPLEMENTACIÓN COMPLETADA (Backend)

### 1. **Base de Datos**

#### Tablas Creadas

**`extraordinary_assessments`** - Tabla principal de proyectos
```sql
- id
- conjunto_config_id
- name (ej: "Pintura de Fachada 2025")
- description (objetivo detallado)
- total_amount (monto total del proyecto)
- number_of_installments (número de cuotas/meses)
- start_date (fecha de inicio del cobro)
- end_date (fecha calculada de finalización)
- distribution_type ('equal' | 'by_coefficient')
- status ('draft' | 'active' | 'completed' | 'cancelled')
- total_collected (monto total recaudado)
- total_pending (monto pendiente)
- installments_generated (número de cuotas generadas)
- metadata (JSON para información adicional)
- notes
- created_at, updated_at, deleted_at
```

**`extraordinary_assessment_apartments`** - Tracking por apartamento
```sql
- id
- extraordinary_assessment_id
- apartment_id
- total_amount (monto asignado al apartamento)
- installment_amount (monto por cuota mensual)
- installments_paid (cuántas cuotas ha pagado)
- amount_paid (cuánto ha pagado en total)
- amount_pending (cuánto le falta)
- status ('pending' | 'in_progress' | 'completed' | 'overdue')
- first_payment_date
- last_payment_date
- created_at, updated_at
```

#### Modificaciones a Tablas Existentes

- **`invoices.type`**: Agregado tipo `'extraordinary'`
- **`payment_concepts.type`**: Agregado tipo `'extraordinary_assessment'`

### 2. **Modelos Eloquent**

#### `ExtraordinaryAssessment`

**Métodos Principales:**
```php
// Calcula y asigna montos a todos los apartamentos
public function calculateAndAssignApartments(): void

// Activa la cuota extraordinaria
public function activate(): void

// Verifica si debe generar cuota para el mes
public function shouldGenerateInstallmentForMonth(Carbon $date): bool

// Marca una cuota como generada
public function markInstallmentGenerated(): void

// Actualiza el progreso de recaudación
public function updateCollectionProgress(): void
```

**Atributos Calculados:**
- `progress_percentage`: Porcentaje de recaudación (0-100)
- `status_label`: Etiqueta en español del estado
- `distribution_label`: Tipo de distribución en español
- `is_active_for_current_month`: Si está activa para el mes actual

**Scopes:**
```php
ExtraordinaryAssessment::active() // Solo activas
ExtraordinaryAssessment::forCurrentMonth($date) // Activas para un mes específico
```

#### `ExtraordinaryAssessmentApartment`

**Métodos Principales:**
```php
// Registra un pago aplicado a esta extraordinaria
public function registerPayment(float $amount): void

// Actualiza el estado basado en progreso
public function updateStatus(): void
```

### 3. **Generación Automática de Facturas**

#### Integración en `GenerateMonthlyInvoices`

El comando de facturación mensual ha sido modificado para:

1. **Detectar extraordinarias activas** para el período
2. **Agregar ítems automáticamente** a cada factura mensual
3. **Respetar el número de cuotas** configurado
4. **Marcar cuotas como generadas** progresivamente

**Ejemplo de uso:**
```bash
php artisan invoices:generate-monthly --year=2025 --month=2
```

**Output esperado:**
```
Generating monthly invoices for 2025-2...
Cuotas extraordinarias activas: 1
Procesando 50 apartamentos elegibles...
  → Cuota extraordinaria agregada a Apt 101: $4,000,000.00 (Pintura de Fachada 2025)
  → Cuota extraordinaria agregada a Apt 102: $4,500,000.00 (Pintura de Fachada 2025)
...
Facturas generadas exitosamente: 50
Ítems de cuotas extraordinarias agregados: 50
Cuota extraordinaria generada: Pintura de Fachada 2025 (Cuota 1/12)
```

### 4. **Sistema de Pagos y Tracking**

#### Listener `UpdateExtraordinaryAssessmentProgress`

**Funcionalidad:**
- Se ejecuta automáticamente cuando se paga una factura
- Detecta ítems de cuotas extraordinarias en la factura
- Distribuye el pago proporcionalmente
- Actualiza el progreso por apartamento
- Actualiza el progreso global del proyecto

**Flujo:**
```
1. Propietario paga factura → Event: PaymentReceived
2. Listener detecta ítems de extraordinarias
3. Calcula proporción del pago aplicada a extraordinarias
4. Actualiza ExtraordinaryAssessmentApartment.amount_paid
5. Actualiza ExtraordinaryAssessmentApartment.installments_paid
6. Actualiza ExtraordinaryAssessment.total_collected
```

### 5. **Integración Contable**

#### Cuenta Contable Creada

**Código:** `13050545`
**Nombre:** CUOTAS EXTRAORDINARIAS
**Tipo:** Activo (Cuentas por Cobrar)
**Naturaleza:** Débito

#### Asientos Contables Automáticos

Cuando se paga una factura con ítems de extraordinarias:

```
DÉBITO:   Banco/Caja (cuenta según método de pago)
CRÉDITO:  13050545 - Cuotas Extraordinarias
```

**Ejemplo:**
```
Factura: $500,000
  - Administración: $300,000
  - Extraordinaria: $200,000

Asientos generados:
1. Pago Administración:
   Débito: Banco $300,000
   Crédito: 13050505 (Cartera Administración) $300,000

2. Pago Extraordinaria:
   Débito: Banco $200,000
   Crédito: 13050545 (Cartera Extraordinarias) $200,000
```

### 6. **Controlador y Rutas**

#### `ExtraordinaryAssessmentController`

**Métodos Implementados:**
```php
index()      // Listar todas las extraordinarias
create()     // Formulario de creación
store()      // Guardar nueva extraordinaria
show()       // Ver detalles y progreso
edit()       // Formulario de edición (solo draft)
update()     // Actualizar (solo draft)
destroy()    // Eliminar (solo draft)
activate()   // Activar extraordinaria
cancel()     // Cancelar extraordinaria
dashboard()  // Dashboard con estadísticas
```

#### Rutas Agregadas

```php
// Dashboard
GET  /extraordinary-assessments/dashboard

// CRUD Resource
GET  /extraordinary-assessments                    // index
GET  /extraordinary-assessments/create             // create
POST /extraordinary-assessments                    // store
GET  /extraordinary-assessments/{id}               // show
GET  /extraordinary-assessments/{id}/edit          // edit
PUT  /extraordinary-assessments/{id}               // update
DELETE /extraordinary-assessments/{id}             // destroy

// Acciones especiales
POST /extraordinary-assessments/{id}/activate      // Activar
POST /extraordinary-assessments/{id}/cancel        // Cancelar
```

**Permisos requeridos:**
- `can:view_payments` para ver
- `can:edit_payments` para crear/editar/activar

### 7. **Distribución de Costos**

#### Dos Métodos Soportados

**1. Distribución Igual (`equal`)**
```php
Monto por apartamento = Total / Número de apartamentos

Ejemplo:
$200,000,000 / 50 apartamentos = $4,000,000 por apartamento
```

**2. Distribución por Coeficiente (`by_coefficient`)**
```php
Monto por apartamento = (Coeficiente apartamento / Suma total coeficientes) × Total

Ejemplo:
Apartamento con coeficiente 0.02 (2%)
$200,000,000 × 0.02 = $4,000,000
```

### 8. **Estados y Flujo de Trabajo**

#### Estados de ExtraordinaryAssessment

```
draft → active → completed
  ↓        ↓
cancelled  cancelled
```

**`draft`**: Borrador, se puede editar/eliminar
**`active`**: Activa, generando cuotas mensuales
**`completed`**: Todas las cuotas generadas
**`cancelled`**: Cancelada manualmente

#### Estados de ExtraordinaryAssessmentApartment

**`pending`**: No ha pagado nada
**`in_progress`**: Ha pagado algo pero no todo
**`completed`**: Pagó todo
**`overdue`**: Tiene pagos vencidos

## 📊 Ejemplos de Uso

### Crear una Cuota Extraordinaria

```php
$assessment = ExtraordinaryAssessment::create([
    'conjunto_config_id' => 1,
    'name' => 'Pintura de Fachada 2025',
    'description' => 'Proyecto de pintura completa de todas las fachadas del conjunto',
    'total_amount' => 200000000, // $200 millones
    'number_of_installments' => 12, // 12 meses
    'start_date' => '2025-01-01',
    'distribution_type' => 'by_coefficient',
    'status' => 'draft',
]);

// Activar (asigna a todos los apartamentos automáticamente)
$assessment->activate();
```

### Consultar Progreso

```php
$assessment = ExtraordinaryAssessment::find(1);

echo "Proyecto: {$assessment->name}\n";
echo "Total: $" . number_format($assessment->total_amount) . "\n";
echo "Recaudado: $" . number_format($assessment->total_collected) . "\n";
echo "Pendiente: $" . number_format($assessment->total_pending) . "\n";
echo "Progreso: {$assessment->progress_percentage}%\n";
echo "Cuotas generadas: {$assessment->installments_generated}/{$assessment->number_of_installments}\n";
```

### Ver Progreso por Apartamento

```php
$assessment = ExtraordinaryAssessment::with('apartments.apartment')->find(1);

foreach ($assessment->apartments as $apt) {
    echo "Apto {$apt->apartment->number}: ";
    echo "{$apt->progress_percentage}% ";
    echo "({$apt->installments_paid}/{$assessment->number_of_installments} cuotas)\n";
}
```

## 🔒 Reglas de Negocio Implementadas

1. ✅ **Solo apartamentos ocupados o disponibles** se incluyen
2. ✅ **No se puede editar una extraordinaria activa**
3. ✅ **No se puede eliminar una extraordinaria activa**
4. ✅ **Las cuotas se generan automáticamente cada mes**
5. ✅ **El progreso se actualiza automáticamente con los pagos**
6. ✅ **Las extraordinarias generan mora** como cualquier otro concepto
7. ✅ **La mora se calcula solo sobre el capital** (sin anatocismo)
8. ✅ **Distribución configurable** (igual o por coeficiente)
9. ✅ **Tracking completo** de recaudación

## 🎯 Próximos Pasos (Frontend)

### Páginas Vue a Crear

1. **Index (`resources/js/pages/Payments/ExtraordinaryAssessments/Index.vue`)**
   - Lista de todas las extraordinarias
   - Filtros por estado
   - Tarjetas con progreso visual
   - Botón para crear nueva

2. **Create (`resources/js/pages/Payments/ExtraordinaryAssessments/Create.vue`)**
   - Formulario para crear extraordinaria
   - Validaciones en tiempo real
   - Preview de distribución

3. **Show (`resources/js/pages/Payments/ExtraordinaryAssessments/Show.vue`)**
   - Detalles del proyecto
   - Gráfico de progreso circular
   - Tabla de apartamentos con progreso
   - Botones de acción (activar/cancelar)

4. **Edit (`resources/js/pages/Payments/ExtraordinaryAssessments/Edit.vue`)**
   - Formulario de edición (solo draft)

5. **Dashboard (`resources/js/pages/Payments/ExtraordinaryAssessments/Dashboard.vue`)**
   - Resumen de todas las activas
   - Gráficos de progreso
   - Estadísticas globales

### Componentes a Crear

1. **ProgressCircle.vue** - Círculo de progreso animado
2. **ExtraordinaryCard.vue** - Tarjeta resumen de extraordinaria
3. **ApartmentProgressTable.vue** - Tabla de progreso por apartamento
4. **DistributionPreview.vue** - Preview de distribución de costos

### Navegación

Agregar al menú principal:
```
Finanzas
  ├─ Facturas
  ├─ Pagos
  ├─ Conceptos de Pago
  └─ Cuotas Extraordinarias  ← NUEVO
```

## 🧪 Testing

### Flujo Completo a Probar

1. Crear extraordinaria en draft
2. Editar datos
3. Activar → Verificar que se asigne a apartamentos
4. Generar facturas del mes → Verificar ítems agregados
5. Pagar factura → Verificar progreso actualizado
6. Generar facturas mes 2 → Verificar nueva cuota
7. Completar todas las cuotas → Verificar estado `completed`

### Comandos de Prueba

```bash
# 1. Crear extraordinaria (vía Tinker o UI)
php artisan tinker
>>> $e = \App\Models\ExtraordinaryAssessment::create(['...']);
>>> $e->activate();

# 2. Generar facturas
php artisan invoices:generate-monthly --year=2025 --month=1

# 3. Ver progreso
php artisan tinker
>>> $e = \App\Models\ExtraordinaryAssessment::find(1);
>>> $e->progress_percentage;
>>> $e->total_collected;
```

## 📝 Notas Importantes

### Corrección de Intereses de Mora

Además de implementar las extraordinarias, se corrigió el sistema de cálculo de mora para **prohibir el anatocismo**:

- ✅ Las moras se calculan SOLO sobre el capital adeudado
- ✅ Nunca se incluyen moras previas en la base de cálculo
- ✅ Nuevo método `Invoice::getCapitalBalance()` implementado
- ✅ Documentación actualizada con ejemplos correctos

### Base de Datos

- **PostgreSQL** es la base de datos usada
- Migraciones ejecutadas correctamente
- Cuenta contable agregada al PUC colombiano

### Performance

El sistema está optimizado para:
- Procesamiento por chunks en generación de facturas
- Carga eager de relaciones
- Índices en columnas clave
- Listeners en cola (ShouldQueue)

## 📖 Referencias

- Migración: `database/migrations/tenant/2025_11_01_202419_create_extraordinary_assessments_table.php`
- Modelo Principal: `app/Models/ExtraordinaryAssessment.php`
- Modelo Apartamento: `app/Models/ExtraordinaryAssessmentApartment.php`
- Controlador: `app/Http/Controllers/ExtraordinaryAssessmentController.php`
- Listener Progreso: `app/Listeners/UpdateExtraordinaryAssessmentProgress.php`
- Rutas: `routes/modules/finance.php`
- Seeder Cuentas: `database/seeders/ChartOfAccountsSeeder.php`

## ✅ Checklist de Implementación

### Backend (100% Completado)
- [x] Migraciones de base de datos
- [x] Modelos Eloquent con relaciones
- [x] Lógica de distribución de costos
- [x] Integración con generación de facturas
- [x] Sistema de tracking de pagos
- [x] Listeners para actualización automática
- [x] Integración contable
- [x] Controlador CRUD completo
- [x] Rutas configuradas
- [x] Validaciones
- [x] Eventos y logs

### Frontend (Pendiente - Siguiente Fase)
- [ ] Página Index con lista
- [ ] Página Create con formulario
- [ ] Página Show con detalles
- [ ] Página Edit con formulario
- [ ] Dashboard con gráficos
- [ ] Componentes de progreso visual
- [ ] Navegación en menú
- [ ] Traducciones
- [ ] Tests E2E

---

**Fecha de implementación:** Noviembre 2025
**Versión:** 1.0
**Estado:** Backend Completo - Frontend Pendiente
