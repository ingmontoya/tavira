# Permisos de Correo Electrónico - Tavira

## Separación de Permisos Implementada

Se han implementado permisos específicos para garantizar que el correo electrónico del **Concejo** y de la **Administración** estén completamente separados y que cada rol solo pueda acceder a su respectiva bandeja de correo.

## 🔐 Permisos Específicos Creados

### Correo de Administración
- `view_admin_email` - Ver bandeja de correo de administración
- `create_admin_email` - Redactar y enviar correos desde administración
- `edit_admin_email` - Marcar como leído/no leído, editar correos
- `delete_admin_email` - Eliminar correos de administración

### Correo de Concejo
- `view_council_email` - Ver bandeja de correo del concejo
- `create_council_email` - Redactar y enviar correos desde concejo
- `edit_council_email` - Marcar como leído/no leído, editar correos
- `delete_council_email` - Eliminar correos del concejo

## 👥 Asignación de Permisos por Rol

### Administrador del Conjunto (`admin_conjunto`)
✅ **PUEDE acceder a**:
- Correo de Administración (todos los permisos: view, create, edit, delete)

❌ **NO PUEDE acceder a**:
- Correo del Concejo (ningún permiso)

### Concejo (`consejo`)
✅ **PUEDE acceder a**:
- Correo del Concejo (todos los permisos: view, create, edit, delete)
- Correspondencia física (`view_correspondence`)

❌ **NO PUEDE acceder a**:
- Correo de Administración (ningún permiso)

### Superadministrador (`superadmin`)
✅ **PUEDE acceder a**:
- Todos los correos (tiene todos los permisos)

### Otros Roles
❌ Los roles `propietario`, `residente` y `porteria` **NO TIENEN** acceso a ningún correo electrónico.

## 🛡️ Protección de Rutas

### Rutas de Administración (`/email/admin/`)
```php
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [EmailController::class, 'adminIndex'])
        ->middleware(['can:view_admin_email']);
    Route::get('/compose', [EmailController::class, 'adminCompose'])
        ->middleware(['can:create_admin_email']);
    Route::post('/send', [EmailController::class, 'send'])
        ->middleware(['can:create_admin_email']);
    // ... más rutas con permisos específicos
});
```

### Rutas de Concejo (`/email/concejo/`)
```php
Route::prefix('concejo')->name('concejo.')->group(function () {
    Route::get('/', [EmailController::class, 'concejoIndex'])
        ->middleware(['can:view_council_email']);
    Route::get('/compose', [EmailController::class, 'concejoCompose'])
        ->middleware(['can:create_council_email']);
    Route::post('/send', [EmailController::class, 'send'])
        ->middleware(['can:create_council_email']);
    // ... más rutas con permisos específicos
});
```

## 🎯 Navegación Condicional

El menú de navegación se ajusta automáticamente según los permisos del usuario:

```typescript
// Solo se muestra si tiene al menos uno de los permisos
visible: hasPermission('view_admin_email') || hasPermission('view_council_email')

// Correo Administración - solo visible para admin_conjunto
{
    title: 'Correo Administración',
    visible: hasPermission('view_admin_email')
}

// Correo Concejo - solo visible para concejo
{
    title: 'Correo Concejo', 
    visible: hasPermission('view_council_email')
}
```

## 🔒 Escenarios de Seguridad

### Escenario 1: Usuario Admin intenta acceder a Correo Concejo
- **URL**: `/email/concejo`
- **Resultado**: `403 Forbidden` - No tiene permiso `view_council_email`
- **Navegación**: No ve la opción "Correo Concejo" en el menú

### Escenario 2: Usuario Concejo intenta acceder a Correo Admin
- **URL**: `/email/admin`
- **Resultado**: `403 Forbidden` - No tiene permiso `view_admin_email`
- **Navegación**: No ve la opción "Correo Administración" en el menú

### Escenario 3: Usuario Propietario intenta acceder a cualquier correo
- **URL**: `/email/admin` o `/email/concejo`
- **Resultado**: `403 Forbidden` - No tiene ningún permiso de email
- **Navegación**: No ve ninguna opción de correo electrónico

## ✅ Verificación de Implementación

Para verificar que los permisos funcionan correctamente:

1. **Verifica los permisos en base de datos**:
   ```bash
   php artisan tinker
   >>> \Spatie\Permission\Models\Permission::where('name', 'like', '%email%')->get()
   ```

2. **Verifica asignación a roles**:
   ```bash
   >>> $admin = \Spatie\Permission\Models\Role::findByName('admin_conjunto')
   >>> $admin->permissions->where('name', 'like', '%email%')
   
   >>> $concejo = \Spatie\Permission\Models\Role::findByName('concejo')  
   >>> $concejo->permissions->where('name', 'like', '%email%')
   ```

3. **Prueba de acceso directo**:
   - Login como admin → Acceder a `/email/admin` ✅
   - Login como admin → Acceder a `/email/concejo` ❌ (403)
   - Login como concejo → Acceder a `/email/concejo` ✅
   - Login como concejo → Acceder a `/email/admin` ❌ (403)

## 📋 Próximos Pasos

- [ ] Implementar logs de auditoría para acceso a correos
- [ ] Añadir notificaciones cuando alguien intenta acceso no autorizado
- [ ] Considerar permisos granulares por apartamento/conjunto
- [ ] Implementar cifrado de correos sensibles

La separación de permisos garantiza que cada entidad (Administración y Concejo) mantenga la privacidad y confidencialidad de sus comunicaciones electrónicas.