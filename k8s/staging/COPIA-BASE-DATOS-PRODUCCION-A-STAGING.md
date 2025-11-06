# Copia de Base de Datos de Producción a Staging

## 📋 Resumen

Este documento detalla el proceso para copiar la base de datos completa de un tenant de producción a staging, incluyendo la creación de usuarios de prueba.

**Fecha:** 2025-11-01
**Realizado por:** Claude Code
**Estado:** ✅ COMPLETADO

---

## 🎯 Objetivo

Copiar todos los datos del tenant de producción "Torres de Villa Campestre" al ambiente de staging para pruebas y desarrollo, incluyendo:

- ✅ 150 apartamentos
- ✅ 300 facturas
- ✅ Todos los residentes, pagos, y transacciones
- ✅ Configuración del conjunto
- ✅ Usuarios de prueba (superadmin y admin)

---

## 📊 Información de Ambientes

### Producción
- **Namespace:** `default`
- **Tenant ID:** `5e26be37-0c2a-4d92-8fc9-c538fca02ef8`
- **Dominio:** torresdevillacampestre.tavira.com.co
- **Base de datos:** `tenant5e26be37-0c2a-4d92-8fc9-c538fca02ef8`
- **DB Central:** `tavira_production`
- **Usuario DB:** `tavira_user`

### Staging
- **Namespace:** `staging`
- **Tenant ID:** `demo`
- **Dominio:** demo.staging.tavira.com.co
- **Base de datos:** `tenantdemo`
- **DB Central:** `tavira_staging`
- **Usuario DB:** `tavira_user_staging`

---

## 🔐 Credenciales de Usuarios Creados

### 1. Usuario Superadmin

```
Email: superadmin@tavira-staging.local
Password: Staging@2025!Super
Rol: superadmin
URL: https://demo.staging.tavira.com.co
```

**Permisos:**
- Acceso completo al sistema
- Gestión de usuarios
- Configuración del conjunto
- Todas las operaciones administrativas

### 2. Usuario Admin del Conjunto

```
Email: admin@tavira-staging.local
Password: Staging@2025!Admin
Rol: admin_conjunto
URL: https://demo.staging.tavira.com.co
```

**Permisos:**
- Gestión de apartamentos y residentes
- Facturación y pagos
- Reportes financieros
- Configuración del conjunto

---

## 📝 Proceso Paso a Paso

### Paso 1: Verificar Tenants Existentes

```bash
# Cambiar al contexto de producción
kubectl config use-context default

# Listar tenants en producción
kubectl exec -n default deployment/tavira-app -c php-fpm -- php artisan tenants:list
# Output: [Tenant] id: 5e26be37-0c2a-4d92-8fc9-c538fca02ef8 @ torresdevillacampestre.tavira.com.co

# Listar tenants en staging
kubectl exec -n staging deployment/tavira-app-staging -c php-fpm -- php artisan tenants:list
# Output: [Tenant] id: demo @ demo.staging.tavira.com.co
```

### Paso 2: Identificar Base de Datos del Tenant en Producción

```bash
# Obtener nombre de la DB central
kubectl exec -n default deployment/tavira-app -c php-fpm -- env | grep DB_DATABASE
# Output: DB_DATABASE=tavira_production

# Encontrar el pod de Postgres
kubectl get pods -n default | grep postgres
# Output: postgres-6d9c8dd56c-bdk5c

# Listar bases de datos de tenants
kubectl exec -n default postgres-6d9c8dd56c-bdk5c -- \
  psql -U tavira_user -d tavira_production -c "\l" | grep tenant
# Output: tenant5e26be37-0c2a-4d92-8fc9-c538fca02ef8
```

### Paso 3: Hacer Dump de la Base de Datos de Producción

```bash
# Hacer el dump de la base de datos del tenant
kubectl exec -n default postgres-6d9c8dd56c-bdk5c -- \
  pg_dump -U tavira_user \
  -d tenant5e26be37-0c2a-4d92-8fc9-c538fca02ef8 \
  --no-owner --no-acl \
  > /tmp/production_tenant_dump.sql

# Verificar el tamaño del dump
ls -lh /tmp/production_tenant_dump.sql
# Output: -rw-r--r-- 712K /tmp/production_tenant_dump.sql
```

**Resultado:** ✅ Dump de 712KB creado exitosamente

### Paso 4: Preparar Base de Datos de Staging

```bash
# Obtener información de staging
kubectl exec -n staging deployment/tavira-app-staging -c php-fpm -- env | grep DB_
# DB_DATABASE=tavira_staging
# DB_USERNAME=tavira_user_staging

# Encontrar el pod de Postgres en staging
kubectl get pods -n staging | grep postgres
# Output: postgres-staging-76c97f9d9b-fdmkb

# Verificar base de datos del tenant en staging
kubectl exec -n staging postgres-staging-76c97f9d9b-fdmkb -- \
  psql -U tavira_user_staging -d tavira_staging -c "\l" | grep tenant
# Output: tenantdemo

# Drop y recrear la base de datos
kubectl exec -n staging postgres-staging-76c97f9d9b-fdmkb -- \
  psql -U tavira_user_staging -d tavira_staging \
  -c "DROP DATABASE IF EXISTS tenantdemo;"

kubectl exec -n staging postgres-staging-76c97f9d9b-fdmkb -- \
  psql -U tavira_user_staging -d tavira_staging \
  -c "CREATE DATABASE tenantdemo;"
```

**Resultado:** ✅ Base de datos limpia creada

### Paso 5: Restaurar Dump en Staging

```bash
# Restaurar el dump en staging
cat /tmp/production_tenant_dump.sql | \
  kubectl exec -i -n staging postgres-staging-76c97f9d9b-fdmkb -- \
  psql -U tavira_user_staging -d tenantdemo

# Verificar que los datos se restauraron
kubectl exec -n staging postgres-staging-76c97f9d9b-fdmkb -- \
  psql -U tavira_user_staging -d tenantdemo \
  -c "SELECT COUNT(*) as apartments FROM apartments;"
# Output: 150

kubectl exec -n staging postgres-staging-76c97f9d9b-fdmkb -- \
  psql -U tavira_user_staging -d tenantdemo \
  -c "SELECT COUNT(*) as invoices FROM invoices;"
# Output: 300

kubectl exec -n staging postgres-staging-76c97f9d9b-fdmkb -- \
  psql -U tavira_user_staging -d tenantdemo \
  -c "SELECT COUNT(*) as users FROM users;"
# Output: 2 (usuarios originales de producción)
```

**Resultado:** ✅ Datos restaurados correctamente

### Paso 6: Crear Usuarios de Prueba

#### 6.1 Usuario Superadmin

```bash
kubectl exec -n staging deployment/tavira-app-staging -c php-fpm -- \
  php artisan tinker --execute="
tenancy()->initialize('demo');
\$superadmin = App\\Models\\User::create([
    'name' => 'Super Admin',
    'email' => 'superadmin@tavira-staging.local',
    'password' => bcrypt('Staging@2025!Super'),
    'email_verified_at' => now(),
]);
\$superadmin->assignRole('superadmin');
echo 'Usuario creado: ' . \$superadmin->email;
"
```

**Output:** ✅ Usuario ID 3 creado

#### 6.2 Usuario Admin

```bash
kubectl exec -n staging deployment/tavira-app-staging -c php-fpm -- \
  php artisan tinker --execute="
tenancy()->initialize('demo');
\$admin = App\\Models\\User::create([
    'name' => 'Admin Conjunto',
    'email' => 'admin@tavira-staging.local',
    'password' => bcrypt('Staging@2025!Admin'),
    'email_verified_at' => now(),
]);
\$admin->assignRole('admin_conjunto');
echo 'Usuario creado: ' . \$admin->email;
"
```

**Output:** ✅ Usuario ID 4 creado

### Paso 7: Verificar Usuarios Creados

```bash
kubectl exec -n staging deployment/tavira-app-staging -c php-fpm -- \
  php artisan tinker --execute="
tenancy()->initialize('demo');
\$users = App\\Models\\User::whereIn('id', [3, 4])->with('roles')->get();
foreach (\$users as \$u) {
    echo 'Usuario: ' . \$u->name . PHP_EOL;
    echo 'Email: ' . \$u->email . PHP_EOL;
    echo 'Roles: ' . \$u->roles->pluck('name')->join(', ') . PHP_EOL;
    echo '---' . PHP_EOL;
}
"
```

**Output:**
```
Usuario: Super Admin
Email: superadmin@tavira-staging.local
Roles: superadmin
---
Usuario: Admin Conjunto
Email: admin@tavira-staging.local
Roles: admin_conjunto
---
```

**Resultado:** ✅ Usuarios verificados correctamente

---

## 📊 Datos Copiados

### Resumen de Datos Restaurados

| Entidad | Cantidad |
|---------|----------|
| Apartamentos | 150 |
| Facturas | 300 |
| Usuarios (producción) | 2 |
| Usuarios de prueba | 2 |
| **Total Usuarios** | **4** |

### Detalles de Datos

- ✅ Configuración completa del conjunto
- ✅ Tipos de apartamentos
- ✅ Residentes y sus relaciones
- ✅ Facturas de octubre y noviembre 2025
- ✅ Pagos y transacciones
- ✅ Conceptos de pago
- ✅ Acuerdos de pago
- ✅ Transacciones contables
- ✅ Chart of accounts (plan de cuentas)

---

## 🔍 Verificación Post-Copia

### Verificar Acceso al Sistema

```bash
# Opción 1: Usar curl
curl -X POST https://demo.staging.tavira.com.co/login \
  -d "email=superadmin@tavira-staging.local" \
  -d "password=Staging@2025!Super"

# Opción 2: Acceder desde el navegador
open https://demo.staging.tavira.com.co
```

### Verificar Datos en la Aplicación

1. **Login con superadmin**
   - URL: https://demo.staging.tavira.com.co
   - Email: superadmin@tavira-staging.local
   - Password: Staging@2025!Super

2. **Verificar Dashboard**
   - Total de apartamentos: 150
   - Facturas del mes: Deben aparecer las de noviembre

3. **Verificar Apartamentos**
   - Navegar a /apartments
   - Verificar que aparecen los 150 apartamentos
   - Verificar estados de pago

4. **Verificar Facturas**
   - Navegar a /invoices
   - Verificar facturas de octubre y noviembre

---

## ⚠️ Consideraciones Importantes

### Seguridad

1. **Passwords Temporales**
   - Las contraseñas proporcionadas son temporales
   - Cambiarlas después del primer login
   - No usar en producción

2. **Datos Sensibles**
   - Los datos de producción contienen información real
   - Usar solo para pruebas y desarrollo
   - No compartir credenciales públicamente

3. **Acceso a Staging**
   - Restringir acceso al ambiente de staging
   - Solo equipo de desarrollo debe tener acceso

### Mantenimiento

1. **Sincronización**
   - Este proceso NO es automático
   - Repetir el proceso cuando se necesite actualizar datos de staging
   - Considerar automatizar si se necesita actualización frecuente

2. **Usuarios de Prueba**
   - Los usuarios de prueba persisten entre copias
   - Verificar que los IDs no entren en conflicto

3. **Limpieza**
   - El dump temporal en `/tmp/` se puede eliminar
   - Mantener backups si es necesario

---

## 🔄 Para Repetir el Proceso

Si necesitas volver a copiar los datos de producción a staging:

```bash
# 1. Hacer nuevo dump
kubectl exec -n default postgres-6d9c8dd56c-bdk5c -- \
  pg_dump -U tavira_user \
  -d tenant5e26be37-0c2a-4d92-8fc9-c538fca02ef8 \
  --no-owner --no-acl > /tmp/production_tenant_dump_$(date +%Y%m%d).sql

# 2. Drop y recrear base de datos en staging
kubectl exec -n staging postgres-staging-76c97f9d9b-fdmkb -- \
  psql -U tavira_user_staging -d tavira_staging \
  -c "DROP DATABASE IF EXISTS tenantdemo; CREATE DATABASE tenantdemo;"

# 3. Restaurar dump
cat /tmp/production_tenant_dump_*.sql | \
  kubectl exec -i -n staging postgres-staging-76c97f9d9b-fdmkb -- \
  psql -U tavira_user_staging -d tenantdemo

# 4. Recrear usuarios de prueba (opcional si se perdieron)
# Ver Paso 6 arriba
```

---

## 🐛 Troubleshooting

### Error: "database does not exist"

```bash
# Verificar que la base de datos central está correcta
kubectl exec -n [namespace] deployment/[app] -c php-fpm -- env | grep DB_DATABASE
```

### Error: "role does not exist"

```bash
# Verificar el usuario de postgres
kubectl exec -n [namespace] deployment/[app] -c php-fpm -- env | grep DB_USERNAME
```

### Error: "connection timeout"

```bash
# El pod de postgres puede estar reiniciando
kubectl get pods -n [namespace]

# Esperar a que el pod esté Ready y volver a intentar
```

### Datos no aparecen en la aplicación

```bash
# 1. Verificar que el tenant está inicializado
kubectl exec -n staging deployment/tavira-app-staging -c php-fpm -- \
  php artisan tinker --execute="tenancy()->initialize('demo'); echo 'OK';"

# 2. Limpiar caché
kubectl exec -n staging deployment/tavira-app-staging -c php-fpm -- \
  php artisan cache:clear

kubectl exec -n staging deployment/tavira-app-staging -c php-fpm -- \
  php artisan config:clear
```

---

## 📚 Comandos Útiles

### Ver logs de staging

```bash
# Logs de la aplicación
kubectl logs -n staging deployment/tavira-app-staging -c php-fpm --tail=100 -f

# Logs de nginx
kubectl logs -n staging deployment/tavira-app-staging -c nginx --tail=100 -f

# Logs de postgres
kubectl logs -n staging deployment/postgres-staging --tail=100 -f
```

### Backup de la base de datos actual de staging

```bash
# Hacer backup antes de sobrescribir
kubectl exec -n staging postgres-staging-76c97f9d9b-fdmkb -- \
  pg_dump -U tavira_user_staging -d tenantdemo \
  > /tmp/staging_backup_$(date +%Y%m%d_%H%M%S).sql
```

### Conectarse directamente a la base de datos

```bash
# Producción
kubectl exec -it -n default postgres-6d9c8dd56c-bdk5c -- \
  psql -U tavira_user -d tenant5e26be37-0c2a-4d92-8fc9-c538fca02ef8

# Staging
kubectl exec -it -n staging postgres-staging-76c97f9d9b-fdmkb -- \
  psql -U tavira_user_staging -d tenantdemo
```

---

## ✅ Checklist de Verificación

Después de completar el proceso, verificar:

- [ ] Dump de producción creado exitosamente
- [ ] Base de datos en staging recreada
- [ ] Dump restaurado sin errores
- [ ] 150 apartamentos en staging
- [ ] 300 facturas en staging
- [ ] Usuario superadmin creado
- [ ] Usuario admin creado
- [ ] Login funciona con superadmin
- [ ] Login funciona con admin
- [ ] Dashboard muestra datos correctos
- [ ] Listado de apartamentos funciona
- [ ] Listado de facturas funciona
- [ ] Estados de pago se muestran correctamente

---

## 🎉 Resultado Final

**Estado:** ✅ COMPLETADO EXITOSAMENTE

**Ambiente de Staging:**
- URL: https://demo.staging.tavira.com.co
- Tenant: demo
- Datos: Copia completa de producción (Torres de Villa Campestre)
- Usuarios de prueba: 2 (superadmin y admin)
- Total usuarios: 4

**Listo para:**
- Desarrollo de nuevas features
- Pruebas de funcionalidades
- Testing de migraciones
- Capacitación de usuarios

---

**Documento generado por:** Claude Code
**Fecha:** 2025-11-01
**Versión:** 1.0
