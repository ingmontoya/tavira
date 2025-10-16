# Pasos para Implementar el Sistema de Sincronización de Proveedores

## ✅ Archivos Creados

### Modelos
- ✅ `app/Models/Central/Provider.php` - Modelo central con SyncMaster
- ✅ `app/Models/Provider.php` - Modelo tenant con Syncable

### Migraciones
- ✅ `database/migrations/landlord/2025_10_16_000001_create_providers_table.php` - Migración central
- ✅ `database/migrations/tenant/2025_10_16_000001_create_providers_table.php` - Migración tenant

### Listeners
- ✅ `app/Listeners/SyncProvidersToNewTenant.php` - Sincroniza proveedores al crear tenant

### Comandos
- ✅ `app/Console/Commands/SyncProviders.php` - Comando de sincronización manual

### Configuración
- ✅ `config/database.php` - Conexión 'central' agregada
- ✅ `app/Providers/AppServiceProvider.php` - Eventos registrados

### Documentación
- ✅ `PROVIDERS_SYNC_GUIDE.md` - Guía completa del sistema
- ✅ `PROVIDERS_SYNC_EXAMPLES.md` - Ejemplos prácticos de código
- ✅ `PROVIDERS_SYNC_SETUP.md` - Este archivo

---

## 📋 Pasos de Implementación

### Paso 1: Configurar Base de Datos Central

```bash
# 1. Asegúrate de que tu .env tenga la configuración correcta
DB_CONNECTION=central
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tavira_central
DB_USERNAME=root
DB_PASSWORD=tu_password
```

### Paso 2: Crear Base de Datos Central (si no existe)

```bash
# MySQL/MariaDB
mysql -u root -p
CREATE DATABASE tavira_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### Paso 3: Ejecutar Migraciones

```bash
# 1. Ejecutar migraciones centrales
php artisan migrate --path=database/migrations/landlord --database=central

# 2. Ejecutar migraciones en todos los tenants
php artisan tenants:migrate
```

**Salida esperada:**
```
Migrating: 2025_10_16_000001_create_providers_table
Migrated:  2025_10_16_000001_create_providers_table (X.XX seconds)
```

### Paso 4: Verificar Tablas Creadas

```bash
php artisan tinker

# Verificar tabla central
>>> DB::connection('central')->table('providers')->count()
=> 0

# Verificar tabla en un tenant
>>> tenancy()->initialize('tu-tenant-id');
>>> DB::table('providers')->count()
=> 0
```

### Paso 5: Limpiar Cachés

```bash
php artisan config:clear
php artisan cache:clear
php artisan event:clear
php artisan optimize
```

### Paso 6: Crear Proveedores de Prueba

```bash
php artisan tinker
```

```php
// En tinker
use App\Models\Central\Provider;

// Crear proveedor central
$provider = Provider::create([
    'name' => 'Proveedor de Prueba',
    'category' => 'Testing',
    'phone' => '3001234567',
    'email' => 'test@provider.com',
    'document_type' => 'NIT',
    'document_number' => '900123456-7',
    'is_active' => true,
]);

// Verificar que se creó
Provider::count();
// => 1
```

### Paso 7: Verificar Sincronización Automática

```bash
php artisan tinker
```

```php
// En tinker, verificar en un tenant
tenancy()->initialize('tu-tenant-id');
\App\Models\Provider::count();
// Debería ser >= 1 si la sincronización funcionó
```

### Paso 8: Probar Sincronización Manual

```bash
# Sincronizar manualmente a todos los tenants
php artisan sync:providers

# Salida esperada:
# Starting provider synchronization...
# Found 1 providers in central database.
# Syncing to X tenant(s)...
# [████████████████████████████] 100%
#
# Synchronization completed!
# ┌─────────────────────────────┬───────┐
# │ Metric                      │ Count │
# ├─────────────────────────────┼───────┤
# │ Central Providers           │ 1     │
# │ Tenants Processed           │ X     │
# │ Records Synced/Updated      │ X     │
# │ Records Skipped (no changes)│ 0     │
# │ Errors                      │ 0     │
# └─────────────────────────────┴───────┘
```

### Paso 9: Configurar Colas (Recomendado para Producción)

```bash
# 1. Configurar Redis en .env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# 2. Iniciar worker de colas
php artisan queue:work

# 3. Para desarrollo, puedes usar sync
QUEUE_CONNECTION=sync
```

### Paso 10: Auditar Sincronización

```bash
# Verificar estado de sincronización
php artisan sync:audit-providers

# Salida esperada:
# ┌─────────────────┬────────┬───────┬───────┬────────┐
# │ Tenant          │ Synced │ Local │ Total │ Status │
# ├─────────────────┼────────┼───────┼───────┼────────┤
# │ tenant1         │ 1      │ 0     │ 1     │ ✓      │
# │ tenant2         │ 1      │ 2     │ 3     │ ✓      │
# └─────────────────┴────────┴───────┴───────┴────────┘
```

---

## 🧪 Tests de Validación

### Test 1: Crear Proveedor Central

```bash
php artisan tinker
```

```php
use App\Models\Central\Provider;

$provider = Provider::create([
    'name' => 'Test Provider ' . now()->timestamp,
    'email' => 'test' . now()->timestamp . '@example.com',
    'is_active' => true,
]);

echo "Proveedor creado con ID: {$provider->id}\n";
```

### Test 2: Verificar en Tenants

```php
use App\Models\Tenant;

$tenants = Tenant::take(3)->get();

foreach ($tenants as $tenant) {
    $tenant->run(function () use ($tenant, $provider) {
        $synced = \App\Models\Provider::where('global_provider_id', $provider->id)->first();
        if ($synced) {
            echo "✓ Tenant {$tenant->id}: Sincronizado\n";
        } else {
            echo "✗ Tenant {$tenant->id}: NO sincronizado\n";
        }
    });
}
```

### Test 3: Actualizar Proveedor Central

```php
$provider->update(['name' => 'Updated Name ' . now()->timestamp]);

echo "Proveedor actualizado\n";

// Esperar un momento si usas colas
sleep(2);

// Verificar actualización en tenant
$tenant = Tenant::first();
$tenant->run(function () use ($provider) {
    $synced = \App\Models\Provider::where('global_provider_id', $provider->id)->first();
    echo "Nombre en tenant: {$synced->name}\n";
});
```

### Test 4: Crear Proveedor Local en Tenant

```php
$tenant = Tenant::first();
$tenant->run(function () {
    $local = \App\Models\Provider::create([
        'name' => 'Local Provider',
        'email' => 'local@example.com',
        'is_active' => true,
        // No tiene global_provider_id
    ]);

    echo "Proveedor local creado\n";
    echo "Es sincronizado: " . ($local->isSynced() ? 'Sí' : 'No') . "\n";
});
```

---

## 🔧 Troubleshooting

### Error: Class "App\Models\Central\Provider" not found

**Solución:**
```bash
composer dump-autoload
php artisan optimize:clear
```

### Error: SQLSTATE[42S02]: Base table or view not found

**Solución:**
```bash
# Verificar que las migraciones se ejecutaron
php artisan migrate:status --database=central
php artisan tenants:migrate --pretend
```

### Error: Connection [central] not configured

**Solución:**
```bash
# Verificar config/database.php
php artisan config:clear
php artisan config:cache
```

### Proveedores no se sincronizan automáticamente

**Solución:**
```bash
# 1. Verificar eventos registrados
php artisan event:list | grep Synced

# 2. Verificar logs
tail -f storage/logs/laravel.log

# 3. Sincronizar manualmente
php artisan sync:providers

# 4. Verificar que los listeners existan
ls -la app/Listeners/SyncProviders*
```

### Performance lento con muchos tenants

**Solución:**
```bash
# 1. Usar colas
QUEUE_CONNECTION=redis

# 2. Iniciar múltiples workers
php artisan queue:work --queue=high,default --tries=3 &
php artisan queue:work --queue=high,default --tries=3 &
php artisan queue:work --queue=high,default --tries=3 &

# 3. Supervisar con Laravel Horizon (opcional)
composer require laravel/horizon
php artisan horizon:install
php artisan horizon
```

---

## 📊 Monitoreo

### Logs a Revisar

```bash
# Ver logs en tiempo real
php artisan pail

# Filtrar logs de sincronización
tail -f storage/logs/laravel.log | grep -i "sync"
```

### Métricas Importantes

1. **Tiempo de sincronización**: Tiempo que toma sincronizar un proveedor a todos los tenants
2. **Tasa de errores**: Porcentaje de fallos en sincronización
3. **Cola de jobs**: Cantidad de jobs pendientes en cola
4. **Diferencias de datos**: Proveedores que están desactualizados en tenants

---

## 🚀 Optimizaciones Avanzadas

### 1. Sincronización Selectiva

```php
// Solo sincronizar a tenants activos
Event::listen(SyncedResourceSaved::class, function ($event) {
    $activeTenants = Tenant::where('subscription_status', 'active')->get();
    // Sincronizar solo a tenants activos
});
```

### 2. Batch Processing

```php
use Illuminate\Support\Facades\Bus;

$jobs = collect($tenants)->map(function ($tenant) use ($provider) {
    return new SyncProviderToTenant($provider->id, $tenant->id);
});

Bus::batch($jobs)->dispatch();
```

### 3. Cache de Proveedores

```php
// En modelo Provider tenant
public static function getCachedProviders()
{
    return Cache::remember('providers_list', 3600, function () {
        return self::active()->orderBy('name')->get();
    });
}
```

---

## ✅ Checklist Final

- [ ] Base de datos central creada
- [ ] Conexión 'central' configurada en database.php
- [ ] Migraciones ejecutadas (central y tenants)
- [ ] Eventos registrados en AppServiceProvider
- [ ] Listener SyncProvidersToNewTenant creado
- [ ] Comando sync:providers funcional
- [ ] Proveedor de prueba creado y sincronizado
- [ ] Colas configuradas (si aplica)
- [ ] Tests de validación pasados
- [ ] Logs verificados sin errores
- [ ] Documentación revisada

---

## 📚 Próximos Pasos

1. **Migrar datos existentes**: Si tienes la tabla `suppliers`, crea un script de migración
2. **Crear seeders**: Agrega proveedores comunes para todos los tenants
3. **Implementar UI**: Crea interfaces en Vue para gestionar proveedores
4. **Agregar permisos**: Implementa policies para controlar acceso
5. **Configurar auditoría**: Registra cambios en proveedores con audit log

---

## 🆘 Soporte

Si encuentras problemas:

1. Revisa los logs: `storage/logs/laravel.log`
2. Ejecuta `php artisan sync:audit-providers` para ver el estado
3. Consulta la documentación de Stancl Tenancy: https://tenancyforlaravel.com/docs/v3/synced-resources
4. Revisa los ejemplos en `PROVIDERS_SYNC_EXAMPLES.md`

---

**¡Sistema de Sincronización de Proveedores Implementado Exitosamente! 🎉**
