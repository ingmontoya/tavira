# Sistema de Alertas para Queue Workers y Scheduler

Esta guía explica cómo configurar y gestionar el sistema completo de alertas de Prometheus/AlertManager para los queue workers y scheduler de Tavira.

## Componentes del Sistema de Alertas

### 1. PrometheusRule - Alertas Básicas
**Archivo**: `prometheus-rules.yaml`

Contiene alertas basadas en métricas de Kubernetes:
- **Queue Workers**:
  - Workers caídos (crítico)
  - Replicas bajas (warning)
  - Restarts frecuentes (warning)
  - Alto uso de CPU/memoria (warning)
  - CrashLoopBackOff (crítico)

- **Scheduler**:
  - Jobs fallidos (warning)
  - No se ha ejecutado recientemente (crítico)
  - Scheduler suspendido (warning)

- **Redis**:
  - Redis caído (crítico)
  - Alto uso de memoria (warning)

- **HPA**:
  - HPA en máximo de replicas (warning)
  - HPA no puede escalar (warning)

### 2. PrometheusRule - Alertas Personalizadas
**Archivo**: `prometheus-custom-alerts.yaml`

Alertas basadas en métricas de Laravel:
- Cola saturada (>1000 jobs) - Warning
- Cola crítica (>5000 jobs) - Critical
- Muchos jobs fallidos - Warning
- Jobs fallidos aumentando - Warning
- Cola creciendo sin procesarse - Warning
- Jobs atascados - Warning
- Sin tenants en base de datos - Critical

### 3. ServiceMonitor
**Archivo**: `service-monitor.yaml`

Configura Prometheus para recolectar métricas de los queue workers.

### 4. Queue Metrics Server
**Archivo**: `app/Console/Commands/QueueMetricsServer.php`

Servidor HTTP que expone métricas de Laravel en formato Prometheus:
- Tamaño de colas (default, tenant)
- Jobs fallidos
- Jobs reservados (en proceso)
- Conexiones Redis
- Uso de memoria Redis
- Número de tenants

## Prerequisitos

### 1. Prometheus Operator

Verifica que el Prometheus Operator esté instalado:
```bash
kubectl get crd prometheusrules.monitoring.coreos.com
kubectl get crd servicemonitors.monitoring.coreos.com
```

Si no está instalado:
```bash
# Usando Helm
helm repo add prometheus-community https://prometheus-community.github.io/helm-charts
helm repo update

helm install prometheus prometheus-community/kube-prometheus-stack \
  --namespace monitoring \
  --create-namespace
```

### 2. AlertManager

Verifica que AlertManager esté corriendo:
```bash
kubectl get pods -n monitoring -l app.kubernetes.io/name=alertmanager
```

## Instalación

### Paso 1: Aplicar PrometheusRules

```bash
cd k8s/deployed

# Aplicar reglas básicas
kubectl apply -f prometheus-rules.yaml

# Aplicar reglas personalizadas
kubectl apply -f prometheus-custom-alerts.yaml

# Verificar que se crearon
kubectl get prometheusrules
```

### Paso 2: Configurar Queue Workers con Métricas (Opcional)

Si quieres exponer métricas personalizadas de Laravel:

1. Edita `queue-worker-deployment.yaml` para agregar un sidecar container:

```yaml
# Agregar después del container queue-worker
- name: metrics-server
  image: ingmontoyav/tavira-app:latest
  command: ["php", "artisan", "queue:metrics-server"]
  ports:
  - containerPort: 9090
    name: metrics
    protocol: TCP
  resources:
    requests:
      memory: "64Mi"
      cpu: "50m"
    limits:
      memory: "128Mi"
      cpu: "100m"
```

2. Aplicar el ServiceMonitor:
```bash
kubectl apply -f service-monitor.yaml
```

3. Re-desplegar los workers:
```bash
kubectl rollout restart deployment/tavira-queue-worker
```

### Paso 3: Configurar AlertManager

Crea o edita el ConfigMap de AlertManager:

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: alertmanager-config
  namespace: monitoring
data:
  alertmanager.yml: |
    global:
      resolve_timeout: 5m

    route:
      group_by: ['alertname', 'component']
      group_wait: 10s
      group_interval: 10s
      repeat_interval: 12h
      receiver: 'default'
      routes:
      # Alertas críticas
      - match:
          severity: critical
        receiver: 'slack-critical'
        continue: true

      # Alertas de warning
      - match:
          severity: warning
        receiver: 'slack-warnings'

    receivers:
    - name: 'default'
      webhook_configs:
      - url: 'http://localhost:5001/'

    # Slack para alertas críticas
    - name: 'slack-critical'
      slack_configs:
      - api_url: 'YOUR_SLACK_WEBHOOK_URL'
        channel: '#tavira-alerts-critical'
        title: '🚨 {{ .GroupLabels.alertname }}'
        text: '{{ range .Alerts }}{{ .Annotations.summary }}\n{{ .Annotations.description }}\n{{ end }}'
        send_resolved: true

    # Slack para warnings
    - name: 'slack-warnings'
      slack_configs:
      - api_url: 'YOUR_SLACK_WEBHOOK_URL'
        channel: '#tavira-alerts'
        title: '⚠️ {{ .GroupLabels.alertname }}'
        text: '{{ range .Alerts }}{{ .Annotations.summary }}\n{{ .Annotations.description }}\n{{ end }}'
        send_resolved: true

    # Email
    - name: 'email'
      email_configs:
      - to: 'ops@tavira.com.co'
        from: 'alertmanager@tavira.com.co'
        smarthost: 'smtp.gmail.com:587'
        auth_username: 'your-email@gmail.com'
        auth_password: 'your-app-password'
        headers:
          Subject: '{{ .GroupLabels.alertname }} - Tavira Alert'
```

Aplica la configuración:
```bash
kubectl apply -f alertmanager-config.yaml -n monitoring
```

## Verificación

### Ver Reglas de Alertas

```bash
# Listar todas las reglas
kubectl get prometheusrules

# Ver detalles de una regla
kubectl describe prometheusrule tavira-queue-alerts

# Ver las reglas en la UI de Prometheus
kubectl port-forward -n monitoring svc/prometheus-kube-prometheus-prometheus 9090:9090

# Abrir http://localhost:9090/alerts
```

### Ver Alertas Activas

```bash
# Port-forward a Prometheus
kubectl port-forward -n monitoring svc/prometheus-kube-prometheus-prometheus 9090:9090

# Ir a: http://localhost:9090/alerts
```

### Ver AlertManager

```bash
# Port-forward a AlertManager
kubectl port-forward -n monitoring svc/alertmanager-operated 9093:9093

# Ir a: http://localhost:9093
```

### Probar Métricas Personalizadas

```bash
# Port-forward al servicio de métricas
kubectl port-forward svc/tavira-queue-worker-metrics 9090:9090

# Consultar métricas
curl http://localhost:9090/metrics
```

## Alertas Disponibles

### Críticas (Requieren Acción Inmediata)

| Alerta | Condición | Acción |
|--------|-----------|--------|
| `QueueWorkersDown` | No hay workers corriendo | Verificar deployment, ver logs |
| `QueueWorkersCrashLooping` | Workers en CrashLoopBackOff | Ver logs, verificar configuración |
| `SchedulerNotRunning` | Scheduler no ejecuta >5min | Verificar CronJob, ver logs |
| `RedisDown` | Redis no disponible | Verificar Redis deployment |
| `QueueCriticalBacklog` | >5000 jobs en cola | Escalar workers, investigar |

### Warning (Requieren Atención)

| Alerta | Condición | Acción |
|--------|-----------|--------|
| `QueueWorkersLowReplicas` | Menos replicas de las esperadas | Verificar HPA, recursos |
| `QueueWorkersHighRestarts` | Restarts frecuentes | Revisar logs, memoria |
| `QueueHighBacklog` | >1000 jobs en cola | Considerar escalar |
| `QueueHighFailedJobs` | >100 jobs fallidos | Revisar logs de errores |
| `HPAMaxedOut` | HPA en máximo 15min | Aumentar límite o optimizar |

## Pruebas de Alertas

### 1. Probar Alerta de Workers Down

```bash
# Escalar workers a 0
kubectl scale deployment tavira-queue-worker --replicas=0

# Esperar 2 minutos y verificar alerta
# Restaurar
kubectl scale deployment tavira-queue-worker --replicas=2
```

### 2. Probar Alerta de Cola Saturada

```bash
# Generar muchos jobs (ejemplo)
kubectl exec -it deployment/tavira-app -- php artisan tinker
# >>> for($i=0; $i<2000; $i++) { dispatch(new App\Jobs\TestJob()); }

# Verificar que se dispare QueueHighBacklog
```

### 3. Probar Alerta de Scheduler

```bash
# Suspender el scheduler
kubectl patch cronjob tavira-scheduler -p '{"spec":{"suspend":true}}'

# Esperar 5 minutos, verificar alerta
# Restaurar
kubectl patch cronjob tavira-scheduler -p '{"spec":{"suspend":false}}'
```

## Integraciones

### Slack

1. Crea un Incoming Webhook en Slack:
   - Ir a: https://api.slack.com/messaging/webhooks
   - Crear webhook para tu workspace
   - Copiar la URL

2. Actualiza `alertmanager-config.yaml` con tu webhook URL

3. Crear canales:
   - `#tavira-alerts-critical` - Alertas críticas
   - `#tavira-alerts` - Warnings y otras alertas

### Email

1. Configura SMTP en `alertmanager-config.yaml`
2. Usa un App Password si usas Gmail
3. Ajusta las direcciones de email

### PagerDuty

```yaml
receivers:
- name: 'pagerduty'
  pagerduty_configs:
  - service_key: 'YOUR_PAGERDUTY_SERVICE_KEY'
    description: '{{ .GroupLabels.alertname }}'
```

### Discord

```yaml
receivers:
- name: 'discord'
  webhook_configs:
  - url: 'YOUR_DISCORD_WEBHOOK_URL'
    send_resolved: true
```

## Troubleshooting

### Las alertas no se disparan

1. Verificar que Prometheus está scrapeando las métricas:
```bash
# Port-forward a Prometheus
kubectl port-forward -n monitoring svc/prometheus-kube-prometheus-prometheus 9090:9090

# Ir a: http://localhost:9090/targets
# Verificar que todos los targets estén UP
```

2. Verificar las reglas:
```bash
# Ver si las reglas se cargaron
kubectl get prometheusrules

# Ver logs de Prometheus
kubectl logs -n monitoring prometheus-kube-prometheus-prometheus-0
```

3. Verificar expresiones PromQL:
```bash
# En la UI de Prometheus (http://localhost:9090)
# Ejecutar la expresión de la alerta
# Ejemplo: kube_deployment_status_replicas_available{deployment="tavira-queue-worker"}
```

### AlertManager no envía notificaciones

1. Verificar configuración:
```bash
kubectl get configmap alertmanager-config -n monitoring -o yaml
```

2. Ver logs:
```bash
kubectl logs -n monitoring alertmanager-kube-prometheus-alertmanager-0
```

3. Probar manualmente:
```bash
# Silenciar alerta temporalmente y revisar
kubectl port-forward -n monitoring svc/alertmanager-operated 9093:9093
# Ir a http://localhost:9093
```

### Métricas personalizadas no aparecen

1. Verificar que el metrics server está corriendo:
```bash
kubectl logs -l app=tavira-queue-worker -c metrics-server
```

2. Verificar ServiceMonitor:
```bash
kubectl get servicemonitor tavira-queue-worker-metrics -o yaml
```

3. Probar endpoint manualmente:
```bash
kubectl port-forward svc/tavira-queue-worker-metrics 9090:9090
curl http://localhost:9090/metrics
```

## Mejores Prácticas

1. **Revisa las alertas regularmente**: Ajusta umbrales según tu carga real

2. **Evita alertas ruidosas**: Si una alerta se dispara muy frecuentemente, ajusta el umbral o el `for` duration

3. **Documenta runbooks**: Cada alerta crítica debería tener un runbook que explique cómo resolverla

4. **Agrupa alertas**: Usa `group_by` en AlertManager para evitar spam

5. **Silencia durante mantenimiento**:
```bash
# Desde la UI de AlertManager
# Crear un silencio temporal durante deploys
```

6. **Monitorea las alertas**:
```bash
# Ver alertas que se disparan frecuentemente
# Considerar ajustar o eliminar
```

7. **Testing**: Prueba tus alertas periódicamente para asegurar que funcionan

## Dashboard de Grafana (Opcional)

Si tienes Grafana instalado, puedes importar dashboards:

```bash
# Port-forward a Grafana
kubectl port-forward -n monitoring svc/prometheus-grafana 3000:80

# Ir a http://localhost:3000
# Importar dashboard: https://grafana.com/grafana/dashboards/
# IDs recomendados:
# - 315 (Kubernetes Cluster Monitoring)
# - 7362 (Kubernetes Deployment)
```

## Métricas Disponibles

### Métricas de Kubernetes (kube-state-metrics)

```promql
# Replicas disponibles
kube_deployment_status_replicas_available{deployment="tavira-queue-worker"}

# Restarts
rate(kube_pod_container_status_restarts_total{container="queue-worker"}[15m])

# CPU
rate(container_cpu_usage_seconds_total{pod=~"tavira-queue-worker.*"}[5m])

# Memoria
container_memory_usage_bytes{pod=~"tavira-queue-worker.*"}
```

### Métricas Personalizadas de Tavira

```promql
# Tamaño de colas
tavira_queue_size{queue="default"}
tavira_queue_size{queue="tenant"}

# Jobs fallidos
tavira_queue_failed_jobs

# Jobs reservados
tavira_queue_reserved_jobs

# Redis
tavira_redis_connected_clients
tavira_redis_memory_bytes

# Tenants
tavira_tenants_total
```

## Recursos Adicionales

- [Prometheus Querying](https://prometheus.io/docs/prometheus/latest/querying/basics/)
- [AlertManager Configuration](https://prometheus.io/docs/alerting/latest/configuration/)
- [Prometheus Operator](https://github.com/prometheus-operator/prometheus-operator)
- [Best Practices for Alerting](https://prometheus.io/docs/practices/alerting/)
