# Actualizar Imagen de Docker con Nuevos Comandos

## Problema Resuelto

✅ El error `Command "queue:health" is not defined` se resolvió actualizando el liveness probe para usar un comando más simple (`ps aux`).

Los queue workers están ahora corriendo correctamente sin errores.

## Opciones para Usar el Comando `queue:health`

Si quieres usar el comando `queue:health` en el futuro (para health checks más sofisticados), tienes dos opciones:

### Opción 1: Reconstruir la Imagen de Docker (Recomendado)

Esta es la solución permanente que incluye los nuevos comandos en la imagen de Docker.

#### Paso 1: Verificar que el comando funciona localmente

```bash
cd /Users/mauricio/repos/tavira

# Regenerar autoloader
composer dump-autoload

# Probar el comando
php artisan queue:health
# Output esperado: "Queue system is healthy"
```

#### Paso 2: Reconstruir la Imagen de Docker

```bash
# Asegúrate de estar en el directorio raíz del proyecto
cd /Users/mauricio/repos/tavira

# Build la nueva imagen con un tag específico
docker build -t ingmontoyav/tavira-app:v$(date +%Y%m%d)-queue-commands .

# O usa el tag latest
docker build -t ingmontoyav/tavira-app:latest .
```

#### Paso 3: Push a Docker Registry

```bash
# Login a Docker Hub
docker login

# Push la imagen
docker push ingmontoyav/tavira-app:v$(date +%Y%m%d)-queue-commands

# O push latest
docker push ingmontoyav/tavira-app:latest
```

#### Paso 4: Actualizar el Deployment en Kubernetes

Si usaste un tag versionado:

```bash
cd /Users/mauricio/repos/tavira/k8s/deployed

# Actualizar la imagen
kubectl set image deployment/tavira-queue-worker \
  queue-worker=ingmontoyav/tavira-app:v$(date +%Y%m%d)-queue-commands

# Verificar el rollout
kubectl rollout status deployment/tavira-queue-worker
```

Si usaste `latest`:

```bash
# Forzar recreación de pods
kubectl rollout restart deployment/tavira-queue-worker
```

#### Paso 5: Actualizar el Liveness Probe (Opcional)

Si quieres usar el comando `queue:health` en el liveness probe:

```bash
cd /Users/mauricio/repos/tavira/k8s/deployed

# Editar el deployment
nano queue-worker-deployment.yaml
```

Cambiar:
```yaml
livenessProbe:
  exec:
    command:
    - sh
    - -c
    - "ps aux | grep 'artisan queue:work' | grep -v grep"
```

Por:
```yaml
livenessProbe:
  exec:
    command:
    - php
    - artisan
    - queue:health
```

Aplicar:
```bash
kubectl apply -f queue-worker-deployment.yaml
```

### Opción 2: Solo Usar el Health Check Simple (Actual)

La configuración actual ya funciona perfectamente. El liveness probe usa:

```yaml
livenessProbe:
  exec:
    command:
    - sh
    - -c
    - "ps aux | grep 'artisan queue:work' | grep -v grep"
```

Esto verifica que el proceso `artisan queue:work` esté corriendo, que es suficiente para la mayoría de los casos.

**Ventajas:**
- ✅ Funciona sin necesidad de reconstruir imagen
- ✅ Más simple y rápido
- ✅ No requiere cambios en Docker

**Desventajas:**
- ⚠️ No verifica conectividad a Redis/Database
- ⚠️ Solo verifica que el proceso existe

## Comandos Disponibles (Después de Rebuild)

Una vez reconstruida la imagen, estos comandos estarán disponibles:

### 1. `php artisan queue:health`

Health check para verificar:
- Conexión al sistema de colas (Redis/Database)
- Disponibilidad del driver de colas
- Estado general del sistema

```bash
# Ejecutar en un pod
kubectl exec -it deployment/tavira-queue-worker -- php artisan queue:health

# Output: "Queue system is healthy"
# Exit code: 0 (success) o 1 (failure)
```

### 2. `php artisan queue:metrics-server`

Servidor HTTP que expone métricas de Prometheus:

```bash
# Ejecutar (esto inicia un servidor en puerto 9090)
php artisan queue:metrics-server --port=9090

# Métricas expuestas:
# - tavira_queue_size{queue="default"}
# - tavira_queue_size{queue="tenant"}
# - tavira_queue_failed_jobs
# - tavira_queue_reserved_jobs
# - tavira_redis_connected_clients
# - tavira_redis_memory_bytes
# - tavira_tenants_total
```

Para usar con Prometheus, necesitarás agregar un sidecar container (ver `service-monitor.yaml`).

## Verificar Estado Actual

```bash
cd /Users/mauricio/repos/tavira/k8s/deployed

# Ver estado de los workers
./deploy-queue-scheduler.sh status

# Ver logs
kubectl logs -l app=tavira-queue-worker -f

# Verificar que no hay errores de liveness probe
kubectl describe pods -l app=tavira-queue-worker | grep -i "liveness"
```

## Rollback si Algo Sale Mal

Si después de actualizar la imagen algo falla:

```bash
# Ver historial de rollouts
kubectl rollout history deployment/tavira-queue-worker

# Rollback a la versión anterior
kubectl rollout undo deployment/tavira-queue-worker

# O rollback a una revisión específica
kubectl rollout undo deployment/tavira-queue-worker --to-revision=2
```

## CI/CD: Automatizar el Build

Si usas CI/CD (GitHub Actions, GitLab CI, etc.), puedes automatizar el build:

### GitHub Actions Example

```yaml
# .github/workflows/build-docker.yml
name: Build and Push Docker Image

on:
  push:
    branches: [ main ]
    paths:
      - 'app/**'
      - 'Dockerfile'

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2

    - name: Login to Docker Hub
      uses: docker/login-action@v2
      with:
        username: ${{ secrets.DOCKER_USERNAME }}
        password: ${{ secrets.DOCKER_PASSWORD }}

    - name: Build and push
      uses: docker/build-push-action@v4
      with:
        context: .
        push: true
        tags: |
          ingmontoyav/tavira-app:latest
          ingmontoyav/tavira-app:${{ github.sha }}

    - name: Deploy to Kubernetes
      run: |
        kubectl set image deployment/tavira-queue-worker \
          queue-worker=ingmontoyav/tavira-app:${{ github.sha }}
```

## Resumen del Estado Actual

✅ **Workers funcionando correctamente**
- 2 replicas corriendo
- Sin errores de liveness probe
- Procesando jobs exitosamente

✅ **Scheduler ejecutándose**
- CronJob corriendo cada minuto
- Jobs completándose exitosamente

✅ **Health check actual**
- Usando `ps aux` para verificar proceso
- Funciona correctamente
- No requiere comandos personalizados

## Próximos Pasos Recomendados

1. **Mantener configuración actual** - Ya funciona bien
2. **Reconstruir imagen cuando sea conveniente** - No es urgente
3. **Considerar CI/CD** - Para automatizar futuros builds
4. **Monitorear logs** - Asegurar que todo funciona correctamente

## Preguntas Frecuentes

### ¿Es necesario reconstruir la imagen ahora?

**No.** La configuración actual funciona perfectamente. Reconstruye la imagen solo si:
- Quieres usar health checks más sofisticados
- Quieres exponer métricas personalizadas de Prometheus
- Haces otros cambios al código que necesitas en producción

### ¿Cuándo debo actualizar la imagen?

Actualiza la imagen cuando:
- Hagas cambios al código PHP
- Agregues nuevos comandos de Artisan
- Actualices dependencias de Composer
- Cambies configuraciones que afecten la aplicación

### ¿Cómo sé si la nueva imagen tiene los comandos?

Después de hacer push de la nueva imagen:

```bash
# Verificar en un pod
kubectl run -it --rm debug --image=ingmontoyav/tavira-app:latest --restart=Never -- php artisan list | grep queue

# Deberías ver:
# queue:health
# queue:metrics-server
# queue:work
# etc.
```

## Soporte

Si tienes problemas:

1. Ver logs: `kubectl logs -l app=tavira-queue-worker --tail=100`
2. Ver eventos: `kubectl describe pods -l app=tavira-queue-worker`
3. Verificar deployment: `./deploy-queue-scheduler.sh status`
4. Hacer rollback si es necesario: `kubectl rollout undo deployment/tavira-queue-worker`
