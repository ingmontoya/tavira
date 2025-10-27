# Configuración de Prometheus para OpenLens

## ✅ Prometheus Instalado

Prometheus ha sido instalado exitosamente en el namespace `monitoring` usando kube-prometheus-stack.

### Servicios disponibles:
- **Prometheus**: `prometheus-kube-prometheus-prometheus.monitoring.svc:9090`
- **Grafana**: `prometheus-grafana.monitoring.svc:80`
- **Alertmanager**: `prometheus-kube-prometheus-alertmanager.monitoring.svc:9093`

## Configuración en OpenLens

### Método 1: Configurar Prometheus en OpenLens (Recomendado)

1. **Abrir OpenLens** y seleccionar tu cluster

2. **Ir a Settings (Configuración)**:
   - Click en el nombre del cluster en la barra lateral
   - Selecciona "Settings" o presiona `Cmd+,` (Mac) / `Ctrl+,` (Windows/Linux)

3. **Configurar Prometheus**:
   - En la sección **"Metrics"** o **"Prometheus"**
   - Habilitar "Prometheus"
   - Configurar el endpoint:

   ```
   Prometheus Service Address:
   http://prometheus-kube-prometheus-prometheus.monitoring.svc:9090
   ```

   O si prefieres usar port-forward local:
   ```
   http://localhost:9090
   ```

4. **Guardar** la configuración

5. **Verificar**: Las métricas deberían aparecer en los pods, nodes, y deployments

### Método 2: Port-Forward Local (Alternativa)

Si prefieres acceder a Prometheus localmente:

```bash
# Port-forward Prometheus
kubectl port-forward -n monitoring svc/prometheus-kube-prometheus-prometheus 9090:9090
```

Luego en OpenLens, configura:
```
http://localhost:9090
```

**Nota**: El port-forward debe estar corriendo mientras uses OpenLens.

## Acceso a Grafana (Bonus)

Para acceder a Grafana y ver dashboards:

1. **Obtener la contraseña de admin**:
```bash
kubectl get secret -n monitoring prometheus-grafana -o jsonpath="{.data.admin-password}" | base64 -d ; echo
```

2. **Port-forward Grafana**:
```bash
kubectl port-forward -n monitoring svc/prometheus-grafana 3000:80
```

3. **Acceder**: http://localhost:3000
   - Usuario: `admin`
   - Contraseña: (la obtenida en el paso 1)

## Verificar que Prometheus está funcionando

```bash
# Ver pods de monitoring
kubectl get pods -n monitoring

# Verificar que Prometheus tiene targets
kubectl port-forward -n monitoring svc/prometheus-kube-prometheus-prometheus 9090:9090

# Abrir en navegador: http://localhost:9090/targets
```

## Troubleshooting

### OpenLens no muestra métricas

1. Verificar que Prometheus está corriendo:
```bash
kubectl get pods -n monitoring
```

2. Verificar que el servicio está accesible:
```bash
kubectl get svc -n monitoring prometheus-kube-prometheus-prometheus
```

3. Probar conectividad:
```bash
kubectl run -it --rm debug --image=curlimages/curl --restart=Never -- \
  curl http://prometheus-kube-prometheus-prometheus.monitoring.svc:9090/api/v1/query?query=up
```

4. Revisar logs de Prometheus:
```bash
kubectl logs -n monitoring prometheus-prometheus-kube-prometheus-prometheus-0
```

### Configuración alternativa en OpenLens

Si el método anterior no funciona, puedes editar directamente el archivo de configuración de OpenLens:

**macOS**: `~/Library/Application Support/OpenLens/lens-cluster-store.json`
**Linux**: `~/.config/OpenLens/lens-cluster-store.json`
**Windows**: `%APPDATA%/OpenLens/lens-cluster-store.json`

Busca tu cluster y agrega:
```json
{
  "prometheus": {
    "prometheusPath": "/api/v1/namespaces/monitoring/services/prometheus-kube-prometheus-prometheus:9090/proxy"
  }
}
```

## Componentes Instalados

- ✅ **Prometheus Operator**: Gestión de Prometheus
- ✅ **Prometheus**: Recolección de métricas
- ✅ **Grafana**: Visualización de dashboards
- ✅ **Alertmanager**: Gestión de alertas
- ✅ **Node Exporter**: Métricas de nodos
- ✅ **Kube State Metrics**: Métricas de objetos de Kubernetes

## Métricas Disponibles

Ahora OpenLens puede mostrar:
- ✅ CPU usage por pod/node
- ✅ Memory usage por pod/node
- ✅ Network I/O
- ✅ Disk I/O
- ✅ Pod restarts
- ✅ Resource requests/limits

---

**Instalado**: 2025-10-25
**Namespace**: monitoring
**Chart**: prometheus-community/kube-prometheus-stack
