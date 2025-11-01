# 📄 Generar Facturas de Noviembre 2025

## 🔍 Problema Identificado

El comando `invoices:generate-monthly` NO estaba programado en el scheduler de Laravel (`bootstrap/app.php`), por lo que las facturas de noviembre no se generaron automáticamente el 1 de noviembre.

## ✅ Solución Aplicada

Se agregó el comando al scheduler en `bootstrap/app.php`:

```php
// Generate monthly invoices on the 1st of each month at 00:01
$schedule->command('invoices:generate-monthly')->monthlyOn(1, '00:01');
```

**Este cambio entrará en efecto después del próximo despliegue**, asegurando que las facturas se generen automáticamente a partir de diciembre 2025.

---

## 🚀 Ejecutar Manualmente para Noviembre 2025

### Opción 1: Usar Job de Kubernetes (RECOMENDADO)

#### Para STAGING:

```bash
# 1. Aplicar el job
kubectl apply -f k8s/staging/generate-november-invoices-job.yaml

# 2. Ver el progreso del job
kubectl get jobs -n tavira-user-staging
kubectl logs -n tavira-user-staging -l type=invoice-generation -f

# 3. Verificar que completó exitosamente
kubectl get jobs -n tavira-user-staging

# 4. (Opcional) Eliminar el job después de verificar
kubectl delete -f k8s/staging/generate-november-invoices-job.yaml
```

#### Para PRODUCCIÓN:

```bash
# 1. Aplicar el job
kubectl apply -f k8s/deployed/generate-november-invoices-job.yaml

# 2. Ver el progreso del job
kubectl get jobs -n default  # O tu namespace de producción
kubectl logs -n default -l type=invoice-generation -f

# 3. Verificar que completó exitosamente
kubectl get jobs -n default

# 4. (Opcional) Eliminar el job después de verificar
kubectl delete -f k8s/deployed/generate-november-invoices-job.yaml
```

---

### Opción 2: Ejecutar desde un Pod Existente

#### Para STAGING:

```bash
# 1. Listar pods disponibles
kubectl get pods -n tavira-user-staging

# 2. Conectarse a un pod de la aplicación
kubectl exec -it <nombre-del-pod> -n tavira-user-staging -- bash

# 3. Dentro del pod, ejecutar:
php artisan tenants:run invoices:generate-monthly --option="month=11" --option="year=2025" --verbose
```

#### Para PRODUCCIÓN:

```bash
# 1. Listar pods disponibles
kubectl get pods -n default  # O tu namespace de producción

# 2. Conectarse a un pod de la aplicación
kubectl exec -it <nombre-del-pod> -n default -- bash

# 3. Dentro del pod, ejecutar:
php artisan tenants:run invoices:generate-monthly --option="month=11" --option="year=2025" --verbose
```

---

## 🔍 Verificar que las Facturas se Generaron

### Desde un Pod:

```bash
# Conectarse al pod
kubectl exec -it <nombre-del-pod> -n <namespace> -- bash

# Listar tenants
php artisan tenants:list

# Para cada tenant, verificar facturas de noviembre
php artisan tinker

# Dentro de tinker:
use App\Models\Invoice;
Invoice::where('billing_period_year', 2025)
       ->where('billing_period_month', 11)
       ->count();

Invoice::where('billing_period_year', 2025)
       ->where('billing_period_month', 11)
       ->with('apartment')
       ->get();
```

---

## 📋 Checklist de Despliegue

- [ ] **Hacer commit del cambio en `bootstrap/app.php`**
- [ ] **Desplegar a staging** y verificar que el scheduler esté configurado correctamente
- [ ] **Ejecutar el job manualmente en staging** para generar facturas de noviembre
- [ ] **Verificar las facturas generadas** en staging
- [ ] **Desplegar a producción** con el cambio del scheduler
- [ ] **Ejecutar el job manualmente en producción** para generar facturas de noviembre
- [ ] **Verificar las facturas generadas** en producción
- [ ] **Monitorear el scheduler** en diciembre para confirmar que funcione automáticamente

---

## 🔄 Próximos Pasos Automáticos

A partir de **diciembre 1, 2025 a las 00:01**, el scheduler ejecutará automáticamente:

1. **00:01** - `invoices:generate-monthly` → Genera facturas mensuales
2. **09:00** - `invoices:process-late-fees` → Procesa intereses de mora

No se requerirá intervención manual para los meses siguientes.

---

## ⚠️ Notas Importantes

- El job se elimina automáticamente después de 1 hora (ttlSecondsAfterFinished: 3600)
- Si el job falla, se reintentará hasta 2 veces (backoffLimit: 2)
- Los logs del job permanecen disponibles hasta que se elimine el job
- **IMPORTANTE**: Antes de ejecutar en producción, prueba en staging primero
