# Queue Workers y Scheduler en Kubernetes

Esta guía explica cómo desplegar y gestionar los queue workers y el scheduler de Laravel en Kubernetes para el proyecto Tavira.

## Componentes

### 1. Queue Workers (`queue-worker-deployment.yaml`)

Deployment que ejecuta workers de Laravel para procesar jobs en background.

**Características:**
- **Replicas**: 2 (mínimo)
- **Comando**: `php artisan queue:work --sleep=3 --tries=3 --max-time=3600`
- **Colas**: `default`, `tenant` (para soporte multitenancy)
- **Graceful Shutdown**: 60 segundos para terminar jobs en progreso
- **Health Check**: Verifica que el proceso de queue:work esté corriendo
- **Recursos**: 256Mi-512Mi RAM, 100m-500m CPU

**Ventajas:**
- Procesamiento paralelo de jobs
- Auto-recuperación si un worker falla
- Escalado automático con HPA

### 2. Scheduler (`scheduler-cronjob.yaml`)

CronJob de Kubernetes que ejecuta el scheduler de Laravel cada minuto.

**Características:**
- **Schedule**: `* * * * *` (cada minuto)
- **Concurrencia**: Forbid (no permite ejecuciones simultáneas)
- **Comando**: `php artisan schedule:run --verbose`
- **Recursos**: 128Mi-256Mi RAM, 50m-200m CPU
- **TTL**: Limpieza automática después de 5 minutos

**Ventajas:**
- Más confiable que un container siempre corriendo
- Gestión nativa de Kubernetes
- Historial de ejecuciones

### 3. HorizontalPodAutoscaler (`queue-worker-hpa.yaml`)

Escala automáticamente los queue workers basado en métricas.

**Configuración:**
- **Min Replicas**: 2
- **Max Replicas**: 10
- **CPU Target**: 70%
- **Memory Target**: 80%
- **Scale Up**: Rápido (duplica en 60s si es necesario)
- **Scale Down**: Lento (reduce 50% en 120s, evita oscilaciones)

### 4. Health Check Command

Comando de Artisan para verificar la salud del sistema de colas:

```bash
php artisan queue:health
```

Verifica:
- Conexión al driver de colas (Redis/Database)
- Disponibilidad del sistema de colas
- Retorna exit code 0 (success) o 1 (failure)

## Despliegue

### Paso 1: Verificar Prerequisites

Asegúrate de que estos recursos existen:
```bash
# Verificar secret con variables de entorno
kubectl get secret laravel-env

# Verificar PVC para storage
kubectl get pvc tavira-storage-pvc

# Verificar Redis deployment
kubectl get deployment redis
```

### Paso 2: Desplegar Queue Workers

```bash
# Aplicar deployment
kubectl apply -f k8s/deployed/queue-worker-deployment.yaml

# Verificar que los pods estén corriendo
kubectl get pods -l app=tavira-queue-worker

# Ver logs de los workers
kubectl logs -l app=tavira-queue-worker -f
```

### Paso 3: Desplegar Scheduler

```bash
# Aplicar CronJob
kubectl apply -f k8s/deployed/scheduler-cronjob.yaml

# Verificar CronJob
kubectl get cronjob tavira-scheduler

# Ver últimas ejecuciones
kubectl get jobs -l app=tavira-scheduler

# Ver logs de una ejecución específica
kubectl logs job/tavira-scheduler-<timestamp>
```

### Paso 4: Habilitar Auto-Scaling (Opcional)

```bash
# Aplicar HPA
kubectl apply -f k8s/deployed/queue-worker-hpa.yaml

# Verificar HPA
kubectl get hpa tavira-queue-worker-hpa

# Ver estado detallado
kubectl describe hpa tavira-queue-worker-hpa
```

## Monitoreo

### Ver Estado de Queue Workers

```bash
# Ver pods corriendo
kubectl get pods -l app=tavira-queue-worker

# Ver logs en tiempo real
kubectl logs -l app=tavira-queue-worker -f --tail=100

# Ver logs de un worker específico
kubectl logs <pod-name> -f

# Ejecutar comando dentro de un worker
kubectl exec -it <pod-name> -- php artisan queue:health
```

### Ver Estado del Scheduler

```bash
# Ver ejecuciones programadas
kubectl get cronjob tavira-scheduler

# Ver historial de jobs
kubectl get jobs -l app=tavira-scheduler

# Ver logs de la última ejecución
LAST_JOB=$(kubectl get jobs -l app=tavira-scheduler --sort-by=.metadata.creationTimestamp -o jsonpath='{.items[-1].metadata.name}')
kubectl logs job/$LAST_JOB

# Ver todas las ejecuciones recientes
kubectl logs -l app=tavira-scheduler --tail=50
```

### Monitorear Auto-Scaling

```bash
# Ver estado del HPA
kubectl get hpa tavira-queue-worker-hpa

# Ver métricas actuales
kubectl top pods -l app=tavira-queue-worker

# Ver eventos de escalado
kubectl get events --sort-by=.metadata.creationTimestamp | grep HorizontalPodAutoscaler
```

## Troubleshooting

### Workers no procesan jobs

1. Verificar que los workers estén corriendo:
```bash
kubectl get pods -l app=tavira-queue-worker
```

2. Ver logs para errores:
```bash
kubectl logs -l app=tavira-queue-worker --tail=50
```

3. Verificar conexión a Redis:
```bash
kubectl exec -it <worker-pod> -- php artisan queue:health
```

4. Verificar que hay jobs en la cola:
```bash
kubectl exec -it <worker-pod> -- php artisan queue:monitor
```

### Scheduler no ejecuta tasks

1. Verificar que el CronJob está activo:
```bash
kubectl get cronjob tavira-scheduler
```

2. Ver últimas ejecuciones:
```bash
kubectl get jobs -l app=tavira-scheduler
```

3. Ver logs de ejecuciones fallidas:
```bash
kubectl get jobs -l app=tavira-scheduler
kubectl logs job/<failed-job-name>
```

4. Verificar schedule programados en Laravel:
```bash
kubectl exec -it <scheduler-pod> -- php artisan schedule:list
```

### HPA no escala

1. Verificar que metrics-server está instalado:
```bash
kubectl top nodes
kubectl top pods
```

2. Ver estado del HPA:
```bash
kubectl describe hpa tavira-queue-worker-hpa
```

3. Ver métricas actuales:
```bash
kubectl get hpa tavira-queue-worker-hpa
```

### Reiniciar Workers

```bash
# Reiniciar todos los workers (rolling restart)
kubectl rollout restart deployment/tavira-queue-worker

# Verificar progreso
kubectl rollout status deployment/tavira-queue-worker

# Eliminar un worker específico (se recreará automáticamente)
kubectl delete pod <worker-pod-name>
```

## Escalado Manual

### Escalar Workers Manualmente

```bash
# Escalar a 5 workers
kubectl scale deployment tavira-queue-worker --replicas=5

# Verificar
kubectl get pods -l app=tavira-queue-worker
```

**Nota**: Si HPA está habilitado, éste puede overridear el escalado manual.

### Desactivar Auto-Scaling Temporalmente

```bash
# Eliminar HPA
kubectl delete hpa tavira-queue-worker-hpa

# Escalar manualmente
kubectl scale deployment tavira-queue-worker --replicas=3

# Re-aplicar HPA cuando sea necesario
kubectl apply -f k8s/deployed/queue-worker-hpa.yaml
```

## Configuración Avanzada

### Cambiar Frecuencia del Scheduler

Edita `scheduler-cronjob.yaml` y cambia el campo `schedule`:

```yaml
# Cada 5 minutos
schedule: "*/5 * * * *"

# Cada hora
schedule: "0 * * * *"

# A las 2 AM todos los días
schedule: "0 2 * * *"
```

### Configurar Colas Específicas

Edita `queue-worker-deployment.yaml` y cambia los args:

```yaml
args:
  - "artisan"
  - "queue:work"
  - "--sleep=3"
  - "--tries=3"
  - "--queue=high-priority,default,low-priority"  # Orden de prioridad
```

### Ajustar Recursos

Edita los manifiestos según tu carga:

```yaml
resources:
  requests:
    memory: "512Mi"    # Para workers más pesados
    cpu: "200m"
  limits:
    memory: "1Gi"
    cpu: "1000m"
```

## Mejores Prácticas

1. **Monitoreo**: Configura alertas para:
   - Workers que no procesan jobs
   - Scheduler que falla
   - Colas con muchos jobs pendientes

2. **Recursos**: Ajusta requests/limits basado en uso real:
   ```bash
   kubectl top pods -l app=tavira-queue-worker
   ```

3. **Logging**: Usa un sistema centralizado (ELK, Loki) para logs

4. **Testing**: Prueba el escalado:
   ```bash
   # Generar carga
   kubectl exec -it <app-pod> -- php artisan queue:test

   # Ver como escala
   watch kubectl get pods -l app=tavira-queue-worker
   ```

5. **Backup**: Guarda los manifiestos en control de versiones

6. **Updates**: Al actualizar la imagen Docker:
   ```bash
   kubectl set image deployment/tavira-queue-worker \
     queue-worker=ingmontoyav/tavira-app:v20251027-new-version

   kubectl rollout status deployment/tavira-queue-worker
   ```

## Multitenancy

Para Tavira con stancl/tenancy, los workers ya están configurados para procesar jobs de múltiples tenants:

- **Queue**: `tenant` - Para jobs específicos de tenants
- **Queue**: `default` - Para jobs globales

Los jobs mantendrán el contexto del tenant automáticamente gracias al `QueueTenancyBootstrapper`.

## Prometheus Metrics (Opcional)

Para integrar con Prometheus, considera usar:
- [Laravel Prometheus Exporter](https://github.com/trrtly/laravel-prometheus-exporter)
- Métricas de colas, jobs procesados, failures, etc.

```yaml
# Agregar annotations al deployment
annotations:
  prometheus.io/scrape: "true"
  prometheus.io/port: "9090"
  prometheus.io/path: "/metrics"
```

## Recursos Adicionales

- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Scheduler Documentation](https://laravel.com/docs/scheduling)
- [Kubernetes CronJob Documentation](https://kubernetes.io/docs/concepts/workloads/controllers/cron-jobs/)
- [Kubernetes HPA Documentation](https://kubernetes.io/docs/tasks/run-application/horizontal-pod-autoscale/)
