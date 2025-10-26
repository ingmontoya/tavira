# Guía de Corrección de Migraciones en Producción

## Problema
Las migraciones de `assemblies` y `votes` se ejecutaron incorrectamente en la base de datos central cuando deberían estar solo en las bases de datos de tenant. Esto causa errores al intentar ejecutar `php artisan migrate` en producción.

## Solución

Hay **DOS OPCIONES** para resolver este problema:

---

### OPCIÓN 1: Ejecutar Script SQL Manual (RECOMENDADO)

Esta es la opción más segura y rápida.

#### Paso 1: Conectarse a la base de datos de producción

```bash
# Desde el servidor de producción
kubectl exec -it deployment/tavira-deployment -- psql -U tavira_user -d tavira_db
```

O usando el cliente PostgreSQL local:

```bash
psql -h [HOST_PRODUCCIÓN] -U [USUARIO] -d [BASE_DE_DATOS]
```

#### Paso 2: Ejecutar el siguiente SQL

```sql
-- 1. Eliminar tablas de tenant que se crearon incorrectamente en central
DROP TABLE IF EXISTS vote_delegates CASCADE;
DROP TABLE IF EXISTS apartment_votes CASCADE;
DROP TABLE IF EXISTS vote_options CASCADE;
DROP TABLE IF EXISTS votes CASCADE;
DROP TABLE IF EXISTS assembly_attendances CASCADE;
DROP TABLE IF EXISTS assemblies CASCADE;

-- 2. Eliminar registros de migraciones de tenant de la tabla migrations central
DELETE FROM migrations
WHERE migration IN (
    '2025_09_08_155748_create_assemblies_table',
    '2025_09_08_155749_create_assembly_attendances_table',
    '2025_09_06_224327_create_votes_table',
    '2025_09_06_224331_create_vote_options_table',
    '2025_09_06_224335_create_apartment_votes_table',
    '2025_09_06_224338_create_vote_delegates_table'
);

-- 3. Verificar que todo esté limpio
SELECT
    table_name
FROM information_schema.tables
WHERE table_schema = 'public'
AND table_name IN ('assemblies', 'assembly_attendances', 'votes', 'vote_options', 'apartment_votes', 'vote_delegates');
-- Debe retornar 0 filas

SELECT
    migration
FROM migrations
WHERE migration LIKE '%assembly%' OR migration LIKE '%vote%';
-- Debe retornar 0 filas
```

#### Paso 3: Ejecutar migraciones normalmente

```bash
php artisan migrate
```

Las migraciones ahora se ejecutarán correctamente y la migración de limpieza (`2025_09_08_000000_remove_assembly_migrations_from_central`) se registrará sin hacer nada (porque ya limpiaste manualmente).

---

### OPCIÓN 2: Usar Archivo SQL Proporcionado

Si prefieres usar el archivo SQL incluido en el repositorio:

```bash
# Desde el repositorio
kubectl cp database/fix_production_migrations.sql [POD_NAME]:/tmp/fix.sql
kubectl exec -it [POD_NAME] -- psql -U tavira_user -d tavira_db -f /tmp/fix.sql
```

Luego ejecutar:

```bash
php artisan migrate
```

---

## Qué hace cada cambio

### Archivos Modificados

1. **database/migrations/2019_09_15_000010_create_tenants_table_custom.php**
   - Renombrada la clase de `CreateTenantsTable` a `CreateTenantsTableCustom`
   - Evita conflicto con migración del vendor

2. **database/migrations/2025_10_25_000000_fix_tenants_migration_class_name.php**
   - Actualiza el nombre de la migración en la tabla `migrations`

3. **database/migrations/2025_09_08_000000_remove_assembly_migrations_from_central.php**
   - Limpia tablas y registros de migraciones incorrectos
   - Es idempotente (seguro ejecutar múltiples veces)

4. **Migraciones movidas a `database/migrations/tenant/`:**
   - `2025_09_08_155748_create_assemblies_table.php`
   - `2025_09_08_155749_create_assembly_attendances_table.php`

### Tablas que se Eliminarán de Central

Estas tablas NO deberían existir en la base de datos central:
- `assemblies`
- `assembly_attendances`
- `votes`
- `vote_options`
- `apartment_votes`
- `vote_delegates`

Estas tablas se crearán correctamente en cada base de datos de tenant cuando ejecutes:

```bash
php artisan tenants:migrate
```

---

## Verificación Post-Migración

### En Central Database

```bash
# Verificar que no haya migraciones pendientes
php artisan migrate:status

# Verificar que las tablas de tenant no existan en central
psql -c "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name LIKE '%assembl%';"
# Debe retornar 0 filas
```

### En Tenant Databases

```bash
# Ejecutar migraciones de tenant
php artisan tenants:migrate

# Verificar estado
php artisan tenants:run "php artisan migrate:status"
```

---

## Rollback (Solo si algo sale mal)

Si necesitas revertir los cambios:

```sql
-- Solo si ejecutaste la limpieza manual y necesitas revertir
-- (NO recomendado, solo para emergencias)

-- Restaurar registros de migraciones
INSERT INTO migrations (migration, batch) VALUES
('2025_09_08_155748_create_assemblies_table', [BATCH_NUMBER]),
('2025_09_08_155749_create_assembly_attendances_table', [BATCH_NUMBER]);
```

---

## Soporte

Si encuentras algún problema durante este proceso:

1. NO ejecutes más comandos de migración
2. Captura el error completo
3. Revisa los logs: `php artisan pail`
4. Contacta al equipo de desarrollo con el error completo

---

## Checklist de Deployment

- [ ] Hacer backup de la base de datos de producción
- [ ] Ejecutar script SQL de limpieza (OPCIÓN 1) o archivo SQL (OPCIÓN 2)
- [ ] Verificar que las tablas fueron eliminadas
- [ ] Hacer deploy del nuevo código
- [ ] Ejecutar `php artisan migrate`
- [ ] Verificar que no haya errores
- [ ] Ejecutar `php artisan tenants:migrate`
- [ ] Verificar que las tablas de tenant se crearon correctamente
- [ ] Probar funcionalidad de assemblies y votes en un tenant de prueba
