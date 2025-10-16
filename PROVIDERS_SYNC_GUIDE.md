# Guía de Sincronización de Proveedores (Synced Resources)

## Descripción General

Este sistema implementa **Synced Resources** de Stancl Tenancy para gestionar proveedores globales que se sincronizan automáticamente desde la base de datos central hacia todos los tenants (conjuntos residenciales).

## Arquitectura

### Modelos

#### 1. Provider Central (app/Models/Central/Provider.php)
- **Ubicación**: Base de datos central (`tavira_central`)
- **Traits**: `CentralConnection`, `SoftDeletes`, `SyncMaster`
- **Función**: Modelo maestro que almacena todos los proveedores globales
- **Conexión**: `central`

#### 2. Provider Tenant (app/Models/Provider.php)
- **Ubicación**: Bases de datos tenant (`tenant{id}`)
- **Traits**: `Syncable`, `SoftDeletes`
- **Función**: Copia local del proveedor en cada tenant
- **Campo Clave**: `global_provider_id` (referencia al ID del provider central)

### Flujo de Sincronización

```
┌─────────────────────────────────────────────────────────────┐
│                    BASE DE DATOS CENTRAL                     │
│                                                              │
│  ┌───────────────────────────────────────────────────────┐  │
│  │  Provider (Central)                                    │  │
│  │  - id: 1                                              │  │
│  │  - name: "Proveedor Global"                           │  │
│  │  - category: "Construcción"                           │  │
│  │  - ...                                                │  │
│  └───────────────────────────────────────────────────────┘  │
│                         │                                    │
└─────────────────────────┼────────────────────────────────────┘
                          │
              ┌───────────┴───────────┐
              │  SyncedResourceSaved  │
              │       (Evento)        │
              └───────────┬───────────┘
                          │
              ┌───────────▼───────────────┐
              │ UpdateSyncedResource      │
              │      (Listener)           │
              └───────────┬───────────────┘
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
        ▼                 ▼                 ▼
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  Tenant DB 1  │  │  Tenant DB 2  │  │  Tenant DB N  │
│              │  │              │  │              │
│ Provider     │  │ Provider     │  │ Provider     │
│ - id: 1      │  │ - id: 1      │  │ - id: 1      │
│ - global_id:1│  │ - global_id:1│  │ - global_id:1│
│ - name: ...  │  │ - name: ...  │  │ - name: ...  │
└──────────────┘  └──────────────┘  └──────────────┘
```

## Configuración

### 1. Base de Datos

La conexión `central` ya está configurada en `config/database.php`:

```php
'central' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'database' => env('DB_DATABASE', 'tavira_central'),
    // ... resto de configuración
],
```

### 2. Eventos y Listeners

Registrados en `app/Providers/AppServiceProvider.php`:

```php
// Eventos de sincronización de recursos (Stancl Tenancy)
Event::listen(SyncedResourceSaved::class, UpdateSyncedResource::class);
Event::listen(TenantCreated::class, SyncProvidersToNewTenant::class);
```

## Migraciones

### Central Database
```bash
# Ejecutar migración central
php artisan migrate --path=database/migrations/landlord
```

### Tenant Databases
```bash
# Ejecutar migración en todos los tenants
php artisan tenants:migrate
```

## Uso del Sistema

### 1. Crear un Proveedor Central

```php
use App\Models\Central\Provider;

// En contexto central (fuera de tenant)
$provider = Provider::create([
    'name' => 'Proveedor Global ABC',
    'category' => 'Construcción',
    'phone' => '3001234567',
    'email' => 'contacto@proveedorabc.com',
    'address' => 'Calle 123 #45-67',
    'document_type' => 'NIT',
    'document_number' => '900123456-7',
    'city' => 'Bogotá',
    'country' => 'Colombia',
    'contact_name' => 'Juan Pérez',
    'contact_phone' => '3107654321',
    'contact_email' => 'juan.perez@proveedorabc.com',
    'tax_regime' => 'Responsable IVA',
    'is_active' => true,
]);

// El evento SyncedResourceSaved se dispara automáticamente
// y sincroniza el proveedor a todos los tenants
```

### 2. Actualizar un Proveedor Central

```php
use App\Models\Central\Provider;

$provider = Provider::find(1);
$provider->update([
    'phone' => '3009876543',
    'email' => 'nuevo@proveedorabc.com',
]);

// Los cambios se propagan automáticamente a todos los tenants
```

### 3. Eliminar un Proveedor Central

```php
use App\Models\Central\Provider;

$provider = Provider::find(1);
$provider->delete(); // Soft delete

// El proveedor se elimina (soft delete) en todos los tenants
```

### 4. Consultar Proveedores desde un Tenant

```php
use App\Models\Provider;

// En contexto tenant (dentro de un conjunto)
$providers = Provider::active()->get();

// Verificar si un proveedor es sincronizado
$provider = Provider::find(1);
if ($provider->isSynced()) {
    echo "Este proveedor viene de la base central";
    echo "ID Central: " . $provider->global_provider_id;
}

// Buscar por categoría
$constructionProviders = Provider::byCategory('Construcción')->get();

// Buscar por nombre
$providers = Provider::byName('ABC')->get();
```

### 5. Sincronización Manual

```bash
# Sincronizar todos los proveedores a todos los tenants
php artisan sync:providers

# Sincronizar solo a un tenant específico
php artisan sync:providers --tenant=conjunto-torres

# Forzar sincronización (actualizar incluso si no hay cambios)
php artisan sync:providers --force
```

## Eventos Importantes

### 1. SyncedResourceSaved
- **Cuándo**: Se dispara automáticamente cuando se crea, actualiza o elimina un Provider central
- **Acción**: Sincroniza el cambio a todos los tenants existentes
- **Listener**: `UpdateSyncedResource` (provisto por Stancl Tenancy)

### 2. TenantCreated
- **Cuándo**: Se dispara cuando se crea un nuevo tenant
- **Acción**: Sincroniza todos los proveedores centrales al nuevo tenant
- **Listener**: `SyncProvidersToNewTenant`

## Casos de Uso Avanzados

### Crear Proveedor Local en Tenant

```php
use App\Models\Provider;

// En contexto tenant
$localProvider = Provider::create([
    'name' => 'Proveedor Local XYZ',
    'category' => 'Mantenimiento',
    'phone' => '3201234567',
    'email' => 'local@xyz.com',
    'is_active' => true,
    'created_by' => auth()->id(), // Usuario que lo creó
    // No se especifica global_provider_id, por lo que es local
]);

// Este proveedor NO se propaga a otros tenants
// Es exclusivo de este conjunto
```

### Optimización con Colas

Para grandes cantidades de tenants, el listener `SyncProvidersToNewTenant` implementa `ShouldQueue`:

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        // configuración...
    ],
],

// .env
QUEUE_CONNECTION=redis
```

```bash
# Iniciar worker de colas
php artisan queue:work
```

## Mejores Prácticas

### 1. Evitar Conflictos de IDs
- ✅ Usar `global_provider_id` para referenciar al provider central
- ✅ Mantener el ID local del tenant separado
- ❌ No modificar `global_provider_id` manualmente

### 2. Proteger Campos Sincronizados en Tenant
```php
// Si un tenant intenta modificar un proveedor sincronizado,
// considera usar un middleware o validación:

public function update(Request $request, Provider $provider)
{
    if ($provider->isSynced()) {
        return back()->with('error',
            'No se puede modificar un proveedor global. Contacte al administrador central.'
        );
    }

    // Proceder con la actualización
}
```

### 3. Uso de withoutEvents
```php
// Si necesitas crear/actualizar sin disparar eventos de sincronización:
Provider::withoutEvents(function () {
    // Operaciones que no deben sincronizarse
});
```

### 4. Logging
El sistema incluye logging automático:
```php
// Los logs se guardan en storage/logs/laravel.log
// Buscar: "Syncing providers to newly created tenant"
```

## Troubleshooting

### Problema: Proveedores no se sincronizan
**Solución**:
```bash
# Verificar que los eventos estén registrados
php artisan event:list

# Ejecutar sincronización manual
php artisan sync:providers

# Verificar logs
tail -f storage/logs/laravel.log
```

### Problema: Error de conexión a base central
**Solución**:
```bash
# Verificar configuración de conexión
php artisan config:clear
php artisan config:cache

# Probar conexión
php artisan tinker
>>> DB::connection('central')->getPdo();
```

### Problema: Tenants nuevos no reciben proveedores
**Solución**:
```bash
# Verificar que el listener esté registrado
# Revisar AppServiceProvider.php

# Sincronizar manualmente a un tenant específico
php artisan sync:providers --tenant=ID_DEL_TENANT
```

## Diferencias con Supplier (Antiguo)

| Aspecto | Supplier (Antiguo) | Provider (Nuevo) |
|---------|-------------------|------------------|
| Ámbito | Solo tenant | Global + tenant |
| Sincronización | No | Sí, automática |
| Referencia central | No | Sí (global_provider_id) |
| Modelo central | No existe | App\Models\Central\Provider |
| Base de datos | Solo tenant | Central + tenant |

## Migración desde Supplier

Si ya tienes datos en la tabla `suppliers`, puedes migrarlos:

```php
// Crear migración de datos
php artisan make:migration migrate_suppliers_to_providers

// En la migración:
public function up()
{
    // 1. Crear proveedores centrales desde suppliers únicos
    $uniqueSuppliers = DB::table('suppliers')
        ->whereNull('deleted_at')
        ->groupBy('document_number')
        ->get();

    foreach ($uniqueSuppliers as $supplier) {
        // Crear en base central
        // Actualizar tenants con global_provider_id
    }
}
```

## Comandos Útiles

```bash
# Ver todos los providers centrales
php artisan tinker
>>> App\Models\Central\Provider::all();

# Ver providers de un tenant específico
php artisan tinker
>>> tenancy()->initialize('tenant-id');
>>> App\Models\Provider::all();

# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan event:clear
```

## Soporte y Documentación

- **Stancl Tenancy Docs**: https://tenancyforlaravel.com/docs/v3/synced-resources
- **Laravel Events**: https://laravel.com/docs/11.x/events
- **Laravel Queues**: https://laravel.com/docs/11.x/queues
