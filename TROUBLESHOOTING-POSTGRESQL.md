# 🔧 Troubleshooting PostgreSQL - Tavira

Guía de solución de problemas comunes con PostgreSQL en entornos multi-tenant.

---

## 🚨 Error: Duplicate Key Value Violates Unique Constraint

### Síntoma

```
SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value violates unique constraint "payments_pkey"
DETAIL: Key (id)=(12) already exists.
```

### Causa

La **secuencia de auto-incremento** de PostgreSQL está desincronizada con los datos reales de la tabla. Esto ocurre cuando:

1. ✋ Se insertaron registros con IDs explícitos (manualmente o mediante seeders)
2. 📦 Se restauró un backup sin actualizar las secuencias
3. 🔄 Se importaron datos de otra base de datos
4. 🐛 Hay código que establece el ID manualmente en lugar de dejarlo auto-generar

### Solución Rápida (Producción)

#### Opción 1: Comando Artisan (Recomendado)

```bash
# Para la base de datos actual
php artisan db:reset-sequences

# Para una tabla específica
php artisan db:reset-sequences --table=payments

# Para TODOS los tenants (¡cuidado en producción!)
php artisan db:reset-sequences --tenant
```

#### Opción 2: En Kubernetes

```bash
# Conectarse al pod
POD=$(kubectl get pods -l app=tavira -o jsonpath='{.items[0].metadata.name}')
kubectl exec -it $POD -c php-fpm -- php artisan db:reset-sequences --tenant
```

#### Opción 3: SQL Directo (Un solo tenant)

```sql
-- Conectarse a la base de datos del tenant
\c tenant123

-- Resetear secuencia de la tabla payments
SELECT setval('payments_id_seq', (SELECT MAX(id) FROM payments) + 1, false);

-- Verificar
SELECT last_value FROM payments_id_seq;
```

#### Opción 4: SQL para TODAS las tablas

```sql
-- Script que resetea TODAS las secuencias
DO $$
DECLARE
    r RECORD;
BEGIN
    FOR r IN
        SELECT tablename
        FROM pg_tables
        WHERE schemaname = 'public'
    LOOP
        BEGIN
            EXECUTE format('
                SELECT setval(
                    pg_get_serial_sequence(''%I'', ''id''),
                    COALESCE((SELECT MAX(id) FROM %I), 1),
                    false
                )', r.tablename, r.tablename);
        EXCEPTION WHEN OTHERS THEN
            -- Ignora tablas sin secuencia
        END;
    END LOOP;
END $$;
```

---

## 🔍 Verificación

Después de resetear, verifica que las secuencias estén correctas:

```sql
-- Ver todas las secuencias
\ds

-- Ver el valor actual de una secuencia
SELECT last_value FROM payments_id_seq;

-- Ver el ID máximo en la tabla
SELECT MAX(id) FROM payments;

-- El last_value debe ser >= MAX(id)
```

---

## 🛡️ Prevención

### 1. Nunca Insertes con IDs Explícitos

❌ **MAL:**
```php
Payment::create([
    'id' => 123,  // ¡NUNCA HAGAS ESTO!
    'amount' => 1000,
]);
```

✅ **BIEN:**
```php
Payment::create([
    // Deja que PostgreSQL genere el ID automáticamente
    'amount' => 1000,
]);
```

### 2. Seeders Correctos

❌ **MAL:**
```php
DB::table('payments')->insert([
    'id' => 1,  // IDs explícitos
    'amount' => 1000,
]);
```

✅ **BIEN:**
```php
// Opción A: Usar el modelo (deja que el ID se auto-genere)
Payment::create(['amount' => 1000]);

// Opción B: Si DEBES usar IDs explícitos, resetea después
DB::table('payments')->insert(['id' => 1, 'amount' => 1000]);
DB::statement("SELECT setval('payments_id_seq', (SELECT MAX(id) FROM payments) + 1, false)");
```

### 3. Backups y Restores

Después de restaurar un backup, SIEMPRE resetea las secuencias:

```bash
# Después de pg_restore
psql $DATABASE_URL -c "
DO \$\$
DECLARE r RECORD;
BEGIN
    FOR r IN SELECT tablename FROM pg_tables WHERE schemaname = 'public'
    LOOP
        BEGIN
            EXECUTE format('SELECT setval(pg_get_serial_sequence(''%I'', ''id''), COALESCE((SELECT MAX(id) FROM %I), 1), false)', r.tablename, r.tablename);
        EXCEPTION WHEN OTHERS THEN NULL;
        END;
    END LOOP;
END \$\$;
"
```

### 4. Migraciones de Datos

Si importas datos de otra BD, incluye esto al final:

```php
// En el seeder o script de importación
public function run(): void
{
    // ... importar datos ...

    // Resetear TODAS las secuencias
    Artisan::call('db:reset-sequences');
}
```

---

## 🤖 Automatización

### Agregar al Entrypoint (Docker)

Si quieres que las secuencias se reseteen automáticamente al iniciar:

```bash
# En docker-entrypoint.sh
if [ "${AUTO_RESET_SEQUENCES:-false}" = "true" ]; then
    echo "🔧 Resetting database sequences..."
    php artisan db:reset-sequences --tenant
fi
```

Luego en `deployment-optimized.yaml`:

```yaml
env:
  - name: AUTO_RESET_SEQUENCES
    value: "true"  # Solo en dev/staging, NO en prod
```

⚠️ **Advertencia:** No hagas esto en producción sin supervisión.

### Job de Kubernetes (Mantenimiento)

```yaml
# k8s/jobs/reset-sequences.yaml
apiVersion: batch/v1
kind: Job
metadata:
  name: reset-db-sequences
spec:
  template:
    spec:
      containers:
      - name: reset-sequences
        image: ingmontoyav/tavira-app:latest
        command: ["php", "artisan", "db:reset-sequences", "--tenant"]
        env:
          # ... mismas variables que el deployment ...
      restartPolicy: OnFailure
```

Ejecutar:
```bash
kubectl apply -f k8s/jobs/reset-sequences.yaml
```

---

## 📊 Monitoreo

### Script de Verificación

Crea un script para verificar periódicamente:

```bash
#!/bin/bash
# check-sequences.sh

php artisan tinker --execute="
\$tables = ['payments', 'invoices', 'apartments', 'residents'];
foreach (\$tables as \$table) {
    \$max = DB::table(\$table)->max('id') ?? 0;
    \$seq = DB::selectOne(\"SELECT last_value FROM {\$table}_id_seq\")->last_value;

    if (\$seq < \$max) {
        echo \"⚠️  \$table: sequence (\$seq) < max_id (\$max)\\n\";
    } else {
        echo \"✅ \$table: OK\\n\";
    }
}
"
```

### Alertas en Sentry

Captura estos errores específicamente:

```php
// En app/Exceptions/Handler.php
use Illuminate\Database\UniqueConstraintViolationException;

public function register(): void
{
    $this->reportable(function (UniqueConstraintViolationException $e) {
        // Si es un error de secuencia, intentar auto-resolver
        if (str_contains($e->getMessage(), 'duplicate key value')) {
            \Log::warning('Sequence out of sync detected', [
                'message' => $e->getMessage(),
            ]);

            // Opcional: auto-resetear (¡con cuidado!)
            // Artisan::call('db:reset-sequences');
        }
    });
}
```

---

## 🧪 Testing

Agrega un test para detectar este problema:

```php
// tests/Feature/DatabaseSequencesTest.php
test('database sequences are in sync', function () {
    $tables = ['payments', 'invoices', 'apartments', 'residents'];

    foreach ($tables as $table) {
        $maxId = DB::table($table)->max('id') ?? 0;
        $seqName = "{$table}_id_seq";

        $sequenceValue = DB::selectOne(
            "SELECT last_value FROM {$seqName}"
        )->last_value;

        expect($sequenceValue)->toBeGreaterThanOrEqual($maxId)
            ->and("Sequence {$seqName} is behind max ID");
    }
});
```

---

## 📚 Referencias

- [PostgreSQL Sequences](https://www.postgresql.org/docs/current/functions-sequence.html)
- [Laravel Serial Keys](https://laravel.com/docs/migrations#serial-keys)
- [Troubleshooting Tenant Databases](https://tenancyforlaravel.com/docs/v3/troubleshooting/)

---

## ✅ Checklist de Resolución

Cuando te encuentres con este error:

- [ ] Identifica la tabla afectada (del error)
- [ ] Ejecuta `php artisan db:reset-sequences --table=<tabla>`
- [ ] Verifica que la secuencia esté correcta
- [ ] Prueba crear un nuevo registro
- [ ] Investiga la causa raíz (¿seeder? ¿restore? ¿código?)
- [ ] Corrige el código/proceso que causó el problema
- [ ] Documenta en el equipo
- [ ] Considera agregar el comando al proceso de deploy/restore

---

**Última actualización:** 2025-10-28
**Comando creado:** `app/Console/Commands/ResetDatabaseSequences.php`
