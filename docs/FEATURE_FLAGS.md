# Sistema de Feature Flags Centralizados

## Resumen

Este sistema permite controlar qué módulos/características están disponibles para cada tenant desde una aplicación central, usando Laravel Pennant con un driver personalizado que consulta un API central y cachea las respuestas en Redis.

## Arquitectura

```
┌─────────────────┐    HTTP API    ┌─────────────────┐
│   Tenant App    │ ──────────────▶ │  Central App    │
│                 │                │                 │
│ Laravel Pennant │                │ Feature API     │
│ CentralApiDriver│                │ /api/internal/  │
│ Redis Cache     │                │ features/{id}   │
└─────────────────┘                └─────────────────┘
        │                                   │
        ▼                                   ▼
┌─────────────────┐                ┌─────────────────┐
│ Redis Cache     │                │ PostgreSQL DB   │
│ 5 min TTL       │                │ tenant_features │
└─────────────────┘                └─────────────────┘
```

## Componentes Implementados

### 1. Base de Datos Central

**Migración:** `create_tenant_features_table.php`
```sql
CREATE TABLE tenant_features (
    id SERIAL PRIMARY KEY,
    tenant_id VARCHAR REFERENCES tenants(id) ON DELETE CASCADE,
    feature VARCHAR NOT NULL,
    enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE(tenant_id, feature),
    INDEX(tenant_id, feature)
);
```

### 2. Modelo TenantFeature

**Archivo:** `app/Models/TenantFeature.php`

**Métodos principales:**
- `isFeatureEnabled(string $tenantId, string $feature): bool`
- `enableFeature(string $tenantId, string $feature): void`
- `disableFeature(string $tenantId, string $feature): void`

### 3. API Endpoint Central

**Endpoint:** `GET /api/internal/features/{tenant}/{feature}`

**Controlador:** `app/Http/Controllers/Api/FeaturesController.php`

**Respuesta:**
```json
{
    "feature": "correspondence",
    "enabled": true,
    "tenant_id": "tenant-uuid"
}
```

### 4. Driver Personalizado de Pennant

**Archivo:** `app/Services/FeatureFlags/CentralApiDriver.php`

**Características:**
- Implementa `Laravel\Pennant\Contracts\Driver`
- Caché de 5 minutos en Redis por feature
- Fallback seguro: retorna `false` si la API central falla
- Manejo de errores y logging

### 5. Configuración de Pennant

**Archivo:** `config/pennant.php`
```php
'default' => env('PENNANT_STORE', 'central_api'),
'stores' => [
    'central_api' => [
        'driver' => 'central_api',
    ],
],
```

**AppServiceProvider:** Registro del driver
```php
Feature::extend('central_api', function ($app, $config) {
    return new CentralApiDriver();
});
```

### 6. Panel de Gestión Central

**Controlador:** `app/Http/Controllers/TenantFeatureController.php`
**Vista:** `resources/js/pages/CentralDashboard/TenantFeatures/Index.vue`

**Rutas:**
- `GET /tenant-features` - Lista todos los tenants y sus features
- `PUT /tenant-features/{tenant}/{feature}` - Actualiza un feature específico
- `PUT /tenant-features/{tenant}/bulk` - Actualiza múltiples features
- `POST /tenant-features/{tenant}/template` - Aplica un template de plan

### 7. Ejemplo de Uso en Tenant App

**Controlador:** `app/Http/Controllers/CorrespondenceController.php`
```php
if (!Feature::active('correspondence', tenant('id'))) {
    return Inertia::render('FeatureDisabled', [
        'feature' => 'correspondence',
        'message' => 'El módulo de correspondencia no está disponible en su plan actual.',
        'upgrade_url' => route('subscription.upgrade'),
    ]);
}
```

## Flujo Completo

### 1. Configuración de Features
1. Superadmin accede al panel central (`/tenant-features`)
2. Selecciona un tenant y habilita/deshabilita features
3. O aplica un template de plan (básico, estándar, premium)
4. Los cambios se guardan en la tabla `tenant_features`
5. Se invalida el caché de Redis para ese tenant

### 2. Verificación en Tenant App
1. Tenant accede a un módulo (ej. correspondencia)
2. El controlador verifica: `Feature::active('correspondence', tenant('id'))`
3. El `CentralApiDriver` consulta el caché Redis
4. Si no existe en caché, consulta la API central
5. La respuesta se cachea por 5 minutos
6. Se retorna `true`/`false` según la configuración

### 3. Manejo de Features Deshabilitados
1. Si el feature está deshabilitado, se muestra la vista `FeatureDisabled.vue`
2. Se informa al usuario que el módulo no está disponible
3. Se ofrece opción de actualizar plan (opcional)

## Templates de Planes

### Plan Básico
- Correspondencia
- Anuncios
- Tickets de soporte

### Plan Estándar
- Todo lo del plan básico +
- Solicitudes de mantenimiento
- Documentos

### Plan Premium
- Todo lo anterior +
- Gestión de visitantes
- Contabilidad avanzada
- Sistema de reservas
- Acuerdos de pago

## Configuración de Entorno

### Variables de Entorno Requeridas
```bash
# En .env de tenant app
CENTRAL_URL=https://central.tavira.com.co
PENNANT_STORE=central_api

# En .env de central app
# (usar configuraciones estándar de Laravel)
```

### Caché Redis
El sistema requiere Redis para el funcionamiento del caché:
- TTL: 5 minutos por feature
- Claves: `feature_flag:{tenant_id}:{feature}`
- Invalidación automática al modificar features

## Testing

### Tests Unitarios Recomendados

1. **TenantFeature Model Tests**
```php
test('can enable feature for tenant', function () {
    TenantFeature::enableFeature('tenant-1', 'correspondence');
    expect(TenantFeature::isFeatureEnabled('tenant-1', 'correspondence'))->toBeTrue();
});
```

2. **CentralApiDriver Tests**
```php
test('retrieves feature from central api', function () {
    // Mock HTTP response
    Http::fake(['*/api/internal/features/*' => Http::response(['enabled' => true])]);
    
    $driver = new CentralApiDriver();
    expect($driver->retrieve('correspondence', 'tenant-1'))->toBeTrue();
});
```

3. **Controller Integration Tests**
```php
test('correspondence module blocks access when disabled', function () {
    TenantFeature::disableFeature('tenant-1', 'correspondence');
    
    $response = $this->get('/correspondence');
    $response->assertInertia(fn ($page) => $page->component('FeatureDisabled'));
});
```

### Testing Manual

1. **Configurar features en central:**
   - Acceder a `/tenant-features`
   - Habilitar/deshabilitar módulos
   - Verificar que se guardan en base de datos

2. **Verificar en tenant app:**
   - Acceder a módulos habilitados (deben funcionar)
   - Acceder a módulos deshabilitados (debe mostrar mensaje)
   - Verificar caché Redis (`redis-cli keys "feature_flag:*"`)

3. **Verificar invalidación de caché:**
   - Cambiar feature en central
   - Verificar que caché se invalida
   - Confirmar que tenant app refleja cambios inmediatamente

## Monitoreo y Logs

### Logs Importantes
- Errores de conexión a API central
- Features no encontrados
- Timeouts de API
- Invalidaciones de caché

### Métricas Recomendadas
- Tiempo de respuesta de API central
- Hit rate del caché Redis
- Frecuencia de uso por feature
- Errores de conectividad

## Extensibilidad

### Agregar Nuevos Features
1. Añadir el feature al array en `TenantFeatureController::getAvailableFeatures()`
2. Actualizar templates en `TenantFeatureController::getFeatureTemplates()`
3. Añadir verificación en el controlador correspondiente
4. Actualizar etiquetas en la vista (`featureLabels`)

### Personalización del Driver
El `CentralApiDriver` se puede extender para:
- Modificar tiempo de caché
- Añadir autenticación API
- Implementar retry logic
- Añadir métricas personalizadas

## Seguridad

### Consideraciones
- API interna no requiere autenticación (por diseño)
- Rate limiting aplicado (`throttle:300,1`)
- Caché limitado por TTL para evitar datos obsoletos
- Tenant isolation mediante tenant_id en consultas

### Recomendaciones
- Usar HTTPS en producción para API central
- Implementar autenticación si la API se expone públicamente
- Monitorear uso de API para detectar anomalías
- Regular limpieza de features obsoletos