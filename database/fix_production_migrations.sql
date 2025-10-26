-- =====================================================
-- SCRIPT DE LIMPIEZA PARA PRODUCCIÓN
-- =====================================================
-- Este script debe ejecutarse MANUALMENTE en producción
-- ANTES de ejecutar 'php artisan migrate'
--
-- Ejecutar con:
-- psql -h [host] -U [user] -d [database] < fix_production_migrations.sql
-- =====================================================

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

-- 3. Verificar que las tablas fueron eliminadas
SELECT
    CASE
        WHEN COUNT(*) = 0 THEN 'OK - No se encontraron tablas de tenant en central'
        ELSE 'ERROR - Aún existen ' || COUNT(*) || ' tablas de tenant'
    END as status
FROM information_schema.tables
WHERE table_schema = 'public'
AND table_name IN ('assemblies', 'assembly_attendances', 'votes', 'vote_options', 'apartment_votes', 'vote_delegates');

-- 4. Verificar que los registros fueron eliminados
SELECT
    CASE
        WHEN COUNT(*) = 0 THEN 'OK - No se encontraron registros de migraciones de tenant'
        ELSE 'ERROR - Aún existen ' || COUNT(*) || ' registros'
    END as status
FROM migrations
WHERE migration LIKE '%assembly%' OR migration LIKE '%vote%';
