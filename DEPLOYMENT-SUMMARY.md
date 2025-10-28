# Resumen del Despliegue - Queue Workers y Sistema de Alertas

## ✅ Estado Actual

### Queue Workers
- **Deployment**: `tavira-queue-worker` - 2/2 replicas corriendo
- **Pods**: 2 pods ejecutando `php artisan queue:work`
- **Colas**: Procesando colas `default` y `tenant`
- **HPA**: Auto-escalado configurado (2-10 replicas)

### Scheduler
- **CronJob**: `tavira-scheduler` - Ejecutándose cada minuto
- **Estado**: Activo y completando jobs exitosamente
- **Último job**: Completado hace menos de 1 minuto

### Sistema de Alertas
- **PrometheusRules**: 2 reglas instaladas
  - `tavira-queue-alerts` - Alertas básicas (18 reglas)
  - `tavira-queue-custom-alerts` - Alertas personalizadas (10 reglas)

## 📁 Archivos Creados

### Manifiestos de Kubernetes
```
k8s/deployed/
├── queue-worker-deployment.yaml      # Deployment de workers
├── queue-worker-hpa.yaml             # Auto-escalado
├── scheduler-cronjob.yaml            # Scheduler (cron)
├── prometheus-rules.yaml             # Alertas básicas
├── prometheus-custom-alerts.yaml     # Alertas personalizadas
├── service-monitor.yaml              # Métricas (opcional)
└── alertmanager-config-example.yaml  # Config AlertManager
```

### Código Laravel
```
app/Console/Commands/
├── QueueHealthCheck.php              # Health check: php artisan queue:health
└── QueueMetricsServer.php            # Metrics server: php artisan queue:metrics-server
```

### Scripts de Gestión
```
k8s/deployed/
├── deploy-queue-scheduler.sh         # Gestión de workers/scheduler
└── setup-alerts.sh                   # Gestión de alertas
```

### Documentación
```
k8s/deployed/
├── README-QUEUE-SCHEDULER.md         # Guía de queue workers
├── README-ALERTING.md                # Guía de alertas
└── DEPLOYMENT-SUMMARY.md             # Este archivo
```

## 🚀 Comandos Útiles

### Gestión de Queue Workers y Scheduler

```bash
cd /Users/mauricio/repos/tavira/k8s/deployed

# Ver estado
./deploy-queue-scheduler.sh status

# Ver logs
./deploy-queue-scheduler.sh logs

# Reiniciar workers
./deploy-queue-scheduler.sh restart

# Escalar manualmente
./deploy-queue-scheduler.sh scale 5

# Eliminar recursos
./deploy-queue-scheduler.sh delete
```

### Gestión de Alertas

```bash
cd /Users/mauricio/repos/tavira/k8s/deployed

# Ver estado
./setup-alerts.sh status

# Probar alertas
./setup-alerts.sh test

# Abrir UIs de monitoreo
./setup-alerts.sh ui

# Desinstalar alertas
./setup-alerts.sh uninstall
```

### Verificación Manual

```bash
# Ver queue workers
kubectl get pods -l app=tavira-queue-worker
kubectl logs -l app=tavira-queue-worker -f

# Ver scheduler
kubectl get cronjob tavira-scheduler
kubectl get jobs -l app=tavira-scheduler

# Ver HPA
kubectl get hpa tavira-queue-worker-hpa
kubectl describe hpa tavira-queue-worker-hpa

# Ver alertas
kubectl get prometheusrules
kubectl describe prometheusrule tavira-queue-alerts
```

## 📊 Monitoreo

### Acceder a Prometheus

```bash
# Port-forward (si Prometheus está instalado)
kubectl port-forward -n monitoring svc/prometheus-kube-prometheus-prometheus 9090:9090

# Abrir http://localhost:9090
# Ver alertas: http://localhost:9090/alerts
```

### Acceder a AlertManager

```bash
# Port-forward
kubectl port-forward -n monitoring svc/alertmanager-operated 9093:9093

# Abrir http://localhost:9093
```

### Métricas Disponibles

```promql
# Tamaño de las colas
tavira_queue_size{queue="default"}
tavira_queue_size{queue="tenant"}

# Jobs fallidos
tavira_queue_failed_jobs

# Workers disponibles
kube_deployment_status_replicas_available{deployment="tavira-queue-worker"}

# HPA
kube_horizontalpodautoscaler_status_current_replicas{horizontalpodautoscaler="tavira-queue-worker-hpa"}
```

## 🔔 Alertas Configuradas

### Críticas (Requieren Acción Inmediata)

| Alerta | Condición | Acción |
|--------|-----------|--------|
| QueueWorkersDown | No hay workers | Verificar deployment |
| QueueWorkersCrashLooping | Workers crasheando | Ver logs |
| SchedulerNotRunning | Scheduler inactivo >5min | Verificar CronJob |
| RedisDown | Redis caído | Verificar Redis |
| QueueCriticalBacklog | >5000 jobs en cola | Escalar workers |

### Warnings (Requieren Atención)

| Alerta | Condición | Acción |
|--------|-----------|--------|
| QueueWorkersLowReplicas | Menos replicas | Verificar HPA |
| QueueHighBacklog | >1000 jobs | Considerar escalar |
| QueueHighFailedJobs | >100 jobs fallidos | Revisar logs |
| HPAMaxedOut | HPA en máximo 15min | Aumentar límite |
| QueueGrowing | Cola creciendo | Optimizar workers |

## 🎯 Próximos Pasos

### 1. Configurar AlertManager (Opcional)

Si quieres recibir notificaciones:

```bash
# 1. Copia el ejemplo
cp alertmanager-config-example.yaml alertmanager-config.yaml

# 2. Edita y configura tus webhooks de Slack/Discord/Email
nano alertmanager-config.yaml

# 3. Aplica la configuración
kubectl create secret generic alertmanager-kube-prometheus-alertmanager \
  --from-file=alertmanager.yaml=alertmanager-config.yaml \
  -n monitoring --dry-run=client -o yaml | kubectl apply -f -
```

Edita estos valores en `alertmanager-config.yaml`:
- Slack webhook URLs
- Direcciones de email
- Canales de Slack
- Configuración SMTP

### 2. Activar Métricas Personalizadas de Laravel (Opcional)

Para exponer métricas como tamaño de colas:

```bash
# 1. Edita queue-worker-deployment.yaml
# 2. Agrega el sidecar container de metrics-server
# 3. Re-despliega
kubectl apply -f queue-worker-deployment.yaml

# 4. Aplica el ServiceMonitor
kubectl apply -f service-monitor.yaml
```

### 3. Monitorear en Producción

```bash
# Ver logs de workers
kubectl logs -l app=tavira-queue-worker -f --tail=100

# Ver scheduler
kubectl logs -l app=tavira-scheduler --tail=50

# Ver métricas de recursos
kubectl top pods -l app=tavira-queue-worker
```

### 4. Ajustar según Carga Real

Después de unos días en producción:

1. **Revisar alertas que se disparan frecuentemente**:
   ```bash
   # Ver alertas activas
   ./setup-alerts.sh ui
   # Seleccionar opción 1 (Prometheus)
   # Ir a http://localhost:9090/alerts
   ```

2. **Ajustar umbrales** en `prometheus-custom-alerts.yaml`:
   ```yaml
   # Ejemplo: Ajustar umbral de cola saturada
   - alert: QueueHighBacklog
     expr: tavira_queue_size > 2000  # Era 1000
   ```

3. **Ajustar recursos del HPA**:
   ```yaml
   # En queue-worker-hpa.yaml
   minReplicas: 3  # Era 2
   maxReplicas: 15 # Era 10
   ```

## 📈 Métricas de Éxito

Verifica que:
- ✅ Workers están procesando jobs (ver logs)
- ✅ Scheduler ejecuta cada minuto (ver jobs completados)
- ✅ HPA funciona (simula carga y verifica escalado)
- ✅ Alertas se disparan correctamente (prueba con `./setup-alerts.sh test`)
- ✅ No hay CrashLoopBackOff (ver pods)

## 🛠️ Troubleshooting Rápido

### Workers no procesan jobs

```bash
# 1. Ver logs
kubectl logs -l app=tavira-queue-worker --tail=50

# 2. Verificar conexión a Redis
kubectl exec -it deployment/tavira-queue-worker -- php artisan queue:health

# 3. Ver estado de Redis
kubectl get pods -l app=redis
```

### Scheduler no ejecuta

```bash
# 1. Ver estado del CronJob
kubectl get cronjob tavira-scheduler

# 2. Ver últimos jobs
kubectl get jobs -l app=tavira-scheduler

# 3. Ver logs del último job
LAST_JOB=$(kubectl get jobs -l app=tavira-scheduler --sort-by=.metadata.creationTimestamp -o jsonpath='{.items[-1].metadata.name}')
kubectl logs job/$LAST_JOB
```

### HPA no escala

```bash
# 1. Verificar métricas
kubectl get hpa tavira-queue-worker-hpa
kubectl top pods -l app=tavira-queue-worker

# 2. Verificar metrics-server
kubectl get deployment metrics-server -n kube-system

# 3. Ver detalles
kubectl describe hpa tavira-queue-worker-hpa
```

## 📚 Recursos

- [README-QUEUE-SCHEDULER.md](README-QUEUE-SCHEDULER.md) - Guía completa de queue workers
- [README-ALERTING.md](README-ALERTING.md) - Guía completa de alertas
- [Laravel Queue Docs](https://laravel.com/docs/queues)
- [Kubernetes CronJob](https://kubernetes.io/docs/concepts/workloads/controllers/cron-jobs/)
- [Prometheus Alerting](https://prometheus.io/docs/alerting/latest/overview/)

## 🎉 Todo Listo!

Tu sistema de queue workers y alertas está completamente configurado y funcionando. Los workers están procesando jobs, el scheduler se ejecuta cada minuto, y tienes 28 reglas de alertas monitoreando todo el sistema.

**Siguiente paso recomendado**: Configurar AlertManager para recibir notificaciones en Slack/Email.
