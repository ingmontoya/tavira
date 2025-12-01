# Grafana Monitoring - Tavira Production

## 🎯 Acceso a Grafana

### Opción 1: Dominio (Recomendado)

**URL**: https://grafana.tavira.com.co

**Credenciales:**
- Usuario: `admin`
- Contraseña: `prom-operator`

**Nota**: Necesitas configurar el DNS para que `grafana.tavira.com.co` apunte a tu cluster. Si no tienes DNS configurado, usa la Opción 2.

### Opción 2: Port-forward (Temporal)

```bash
kubectl port-forward -n monitoring svc/prometheus-grafana 3000:80
```

Luego accede a: http://localhost:3000

## 📊 Dashboards Configurados

### 1. Tavira Production - Kubernetes Overview
- **UID**: `tavira-k8s-prod`
- **Métricas**:
  - CPU y Memoria por pod
  - Restarts de pods
  - Health status de los pods
- **Uso**: Monitoreo general de la infraestructura

### 2. Tavira Production - PostgreSQL
- **UID**: `tavira-postgres-prod`
- **Métricas**:
  - CPU y Memoria de PostgreSQL
  - Network I/O
  - Health status
  - Uso de memoria %
- **Uso**: Monitoreo de la base de datos

### 3. Tavira Production - Redis Cache
- **UID**: `tavira-redis-prod`
- **Métricas**:
  - CPU y Memoria de Redis
  - Network I/O
  - Health status
- **Uso**: Monitoreo del sistema de caché

### 4. Tavira Production - Laravel Application
- **UID**: `tavira-laravel-prod`
- **Métricas**:
  - HTTP Requests per second (por código de estado)
  - Response Time (Latency) - p50, p95, p99
  - Error Rate (5xx) %
  - Queue Worker Restarts
  - Request/Response Size
  - Traffic actual
- **Uso**: Monitoreo de rendimiento de la aplicación

## 🚨 Alertas Configuradas

### Críticas (Critical)
1. **TaviraPodCrashLoop**: Pod reiniciando frecuentemente
2. **TaviraHighErrorRate**: Tasa de error > 5% (errores 5xx)
3. **TaviraPostgreSQLDown**: Base de datos caída
4. **TaviraRedisDown**: Redis caído

### Advertencias (Warning)
1. **TaviraHighMemoryUsage**: Uso de memoria > 80%
2. **TaviraHighCPUUsage**: Uso de CPU > 80%
3. **TaviraSlowResponseTime**: Tiempo de respuesta p95 > 2 segundos
4. **TaviraQueueWorkerRestarting**: Queue worker reiniciando frecuentemente

## 📈 Cómo Usar los Dashboards

### Ver el rendimiento actual
1. Accede a Grafana
2. Click en "Dashboards" en el menú lateral
3. Busca "Tavira Production - Laravel Application"
4. Observa las métricas en tiempo real

### Investigar un problema
1. Ve al dashboard correspondiente (Laravel, PostgreSQL, Redis)
2. Ajusta el rango de tiempo en la parte superior derecha
3. Observa correlaciones entre métricas (ej: CPU sube cuando hay más requests)

### Ver alertas activas
1. Click en "Alerting" en el menú lateral
2. Click en "Alert rules"
3. Verás todas las alertas y su estado actual

## 🔧 Configuración de Prometheus

**Configuración actual:**
- **Retention**: 3 días (datos históricos guardados)
- **Scrape Interval**: 60 segundos (frecuencia de recolección)
- **Evaluation Interval**: 60 segundos (frecuencia de evaluación de alertas)

## 📝 Métricas Clave a Monitorear

### Rendimiento de la Aplicación
- **RPS (Requests/sec)**: Tráfico de la aplicación
- **Latency p95**: El 95% de las requests son más rápidas que este valor
- **Error Rate**: Porcentaje de errores 5xx

### Infraestructura
- **CPU Usage**: Debería estar < 70% en promedio
- **Memory Usage**: Debería estar < 80% para evitar OOM
- **Pod Restarts**: Debería ser 0 o muy bajo

### Base de Datos
- **PostgreSQL Memory**: Monitorear para evitar OOM
- **PostgreSQL CPU**: Picos pueden indicar queries lentas
- **Network I/O**: Tráfico entre app y DB

## 🎨 Personalización

### Agregar un nuevo dashboard
1. Crea el JSON del dashboard en `k8s/monitoring/dashboards/`
2. Crea el ConfigMap:
   ```bash
   kubectl create configmap grafana-dashboard-NOMBRE -n monitoring \
     --from-file=k8s/monitoring/dashboards/TU-DASHBOARD.json
   kubectl label configmap grafana-dashboard-NOMBRE -n monitoring grafana_dashboard="1"
   ```
3. Reinicia Grafana:
   ```bash
   kubectl rollout restart deployment prometheus-grafana -n monitoring
   ```

### Modificar alertas
Edita `k8s/monitoring/prometheus-rules.yaml` y aplica:
```bash
kubectl apply -f k8s/monitoring/prometheus-rules.yaml
```

## 🔍 Troubleshooting

### Los dashboards no aparecen
```bash
# Verifica que los ConfigMaps estén creados
kubectl get configmap -n monitoring | grep grafana-dashboard

# Verifica que tengan el label correcto
kubectl get configmap -n monitoring -l grafana_dashboard=1

# Reinicia Grafana
kubectl rollout restart deployment prometheus-grafana -n monitoring
```

### Las métricas no se muestran
```bash
# Verifica que Prometheus esté corriendo
kubectl get pods -n monitoring | grep prometheus

# Verifica las métricas de Prometheus
kubectl port-forward -n monitoring svc/prometheus-kube-prometheus-prometheus 9090:9090
# Luego accede a http://localhost:9090
```

### No puedo acceder a grafana.tavira.com.co
1. Verifica el Ingress:
   ```bash
   kubectl get ingress -n monitoring
   ```
2. Verifica el certificado SSL:
   ```bash
   kubectl get certificate -n monitoring
   ```
3. Usa port-forward como alternativa temporal

## 📚 Recursos Adicionales

- [Documentación de Grafana](https://grafana.com/docs/)
- [PromQL (Prometheus Query Language)](https://prometheus.io/docs/prometheus/latest/querying/basics/)
- [Mejores prácticas de monitoreo](https://prometheus.io/docs/practices/naming/)

## 🔐 Seguridad

**IMPORTANTE**: La contraseña por defecto es `prom-operator`. Para cambiarla:

```bash
# Generar nueva contraseña
NEW_PASSWORD="tu-nueva-contraseña-segura"

# Actualizar el secret
kubectl patch secret prometheus-grafana -n monitoring \
  -p "{\"data\":{\"admin-password\":\"$(echo -n $NEW_PASSWORD | base64)\"}}"

# Reiniciar Grafana
kubectl rollout restart deployment prometheus-grafana -n monitoring
```
