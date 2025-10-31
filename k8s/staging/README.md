# Staging Environment - Kubernetes Manifests

Este directorio contiene los manifiestos de Kubernetes para el ambiente de **staging** de Tavira.

## 📋 Descripción General

El ambiente staging está optimizado para pruebas con recursos mínimos:

### Recursos por Servicio

| Servicio | Replicas | CPU Request | CPU Limit | Memory Request | Memory Limit |
|----------|----------|-------------|-----------|----------------|--------------|
| **App (PHP-FPM)** | 1 | 50m | 300m | 128Mi | 256Mi |
| **App (Nginx)** | 1 | 25m | 100m | 32Mi | 64Mi |
| **Queue Worker** | 1 | 50m | 300m | 128Mi | 256Mi |
| **PostgreSQL** | 1 | 100m | 500m | 128Mi | 256Mi |
| **Redis** | 1 | 50m | 150m | 64Mi | 128Mi |

### Almacenamiento

- **PostgreSQL**: 5Gi (reducido de 10Gi en producción)
- **App Storage**: 5Gi (reducido de 10Gi en producción)

## 🚀 Despliegue Inicial

### Pre-requisitos

1. Cluster de Kubernetes configurado
2. `kubectl` instalado y configurado
3. Dominio staging configurado: `staging.tavira.com.co` y `*.staging.tavira.com.co`
4. Secrets configurados (ver sección de Secrets)

### Pasos de Despliegue

#### 1. Crear Secrets

Primero, crea el secret con las credenciales:

```bash
# Generar una nueva APP_KEY para staging
php artisan key:generate --show

# Crear el secret
kubectl create secret generic laravel-env-staging \
  --from-literal=APP_KEY="base64:TU_APP_KEY_AQUI" \
  --from-literal=DB_DATABASE="tavira_staging" \
  --from-literal=DB_USERNAME="tavira_user_staging" \
  --from-literal=DB_PASSWORD="TU_PASSWORD_AQUI" \
  --from-literal=CACHE_DRIVER="redis" \
  --from-literal=REDIS_HOST="redis-staging" \
  --from-literal=REDIS_PORT="6379" \
  --from-literal=QUEUE_CONNECTION="redis" \
  --from-literal=SESSION_DRIVER="redis"
```

#### 2. Aplicar ConfigMaps

```bash
kubectl apply -f k8s/staging/postgres-config.yaml
kubectl apply -f k8s/staging/nginx-config.yaml
```

#### 3. Crear PVCs

```bash
kubectl apply -f k8s/staging/pvcs.yaml
```

#### 4. Desplegar Bases de Datos

```bash
# PostgreSQL
kubectl apply -f k8s/staging/postgres-deployment.yaml
kubectl apply -f k8s/staging/postgres-service.yaml

# Redis
kubectl apply -f k8s/staging/redis-deployment.yaml
kubectl apply -f k8s/staging/redis-service.yaml

# Esperar a que estén listos
kubectl wait --for=condition=ready pod -l app=postgres-staging --timeout=120s
kubectl wait --for=condition=ready pod -l app=redis-staging --timeout=120s
```

#### 5. Desplegar Aplicación

```bash
# App principal
kubectl apply -f k8s/staging/deployment.yaml
kubectl apply -f k8s/staging/service.yaml

# Queue worker
kubectl apply -f k8s/staging/queue-worker-deployment.yaml

# Esperar a que estén listos
kubectl wait --for=condition=ready pod -l app=tavira-staging --timeout=180s
```

#### 6. Configurar Ingress

```bash
kubectl apply -f k8s/staging/ingress.yaml
```

#### 7. Ejecutar Migraciones

```bash
# Obtener el nombre del pod
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')

# Ejecutar migraciones centrales
kubectl exec $POD -c php-fpm -- php artisan migrate --force

# Ejecutar migraciones de tenants
kubectl exec $POD -c php-fpm -- php artisan tenants:migrate --force

# (Opcional) Seed de datos de prueba
kubectl exec $POD -c php-fpm -- php artisan db:seed --force
```

## 🔄 Despliegue Automatizado con GitHub Actions

El pipeline de staging se ejecuta automáticamente cuando se hace push a la rama `develop`.

### Workflow

1. **Trigger**: Push a rama `develop`
2. **Build**: Construye imágenes Docker con tag `staging`
3. **Deploy**: Actualiza los deployments en Kubernetes
4. **Post-Deploy**: Ejecuta migraciones y cache de configuración

### Despliegue Manual

También puedes ejecutar el workflow manualmente desde GitHub Actions:

1. Ve a Actions → Deploy to Staging
2. Click en "Run workflow"
3. Selecciona la rama `develop`
4. Click en "Run workflow"

## 📊 Monitoreo y Verificación

### Ver Estado de Pods

```bash
# Ver todos los pods de staging
kubectl get pods -l environment=staging

# Ver logs de la aplicación
kubectl logs -f deployment/tavira-app-staging -c php-fpm

# Ver logs del queue worker
kubectl logs -f deployment/tavira-queue-worker-staging

# Ver logs de PostgreSQL
kubectl logs -f deployment/postgres-staging

# Ver logs de Redis
kubectl logs -f deployment/redis-staging
```

### Ver Estado de Deployments

```bash
kubectl get deployments -l environment=staging
```

### Ver Servicios

```bash
kubectl get services -l environment=staging
```

### Verificar Ingress

```bash
kubectl get ingress tavira-ingress-staging
kubectl describe ingress tavira-ingress-staging
```

## 🛠️ Comandos Útiles

### Reiniciar Deployments

```bash
# Reiniciar app principal
kubectl rollout restart deployment/tavira-app-staging

# Reiniciar queue worker
kubectl rollout restart deployment/tavira-queue-worker-staging
```

### Escalar Replicas (temporal)

```bash
# Si necesitas más recursos temporalmente
kubectl scale deployment/tavira-app-staging --replicas=2
kubectl scale deployment/tavira-queue-worker-staging --replicas=2

# Volver a la configuración normal
kubectl scale deployment/tavira-app-staging --replicas=1
kubectl scale deployment/tavira-queue-worker-staging --replicas=1
```

### Ejecutar Comandos Artisan

```bash
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')

# Cache de configuración
kubectl exec $POD -c php-fpm -- php artisan config:cache

# Limpiar cache
kubectl exec $POD -c php-fpm -- php artisan cache:clear

# Ver lista de tenants
kubectl exec $POD -c php-fpm -- php artisan tenants:list

# Ejecutar comando para un tenant específico
kubectl exec $POD -c php-fpm -- php artisan tenants:run <comando> --tenants=1
```

### Ver Logs en Tiempo Real

```bash
# Logs de la app
kubectl logs -f deployment/tavira-app-staging -c php-fpm --tail=100

# Logs de Nginx
kubectl logs -f deployment/tavira-app-staging -c nginx --tail=100

# Logs del queue worker
kubectl logs -f deployment/tavira-queue-worker-staging --tail=100
```

## 🗑️ Eliminar Ambiente Staging

Si necesitas eliminar completamente el ambiente staging:

```bash
# Eliminar deployments y services
kubectl delete -f k8s/staging/deployment.yaml
kubectl delete -f k8s/staging/queue-worker-deployment.yaml
kubectl delete -f k8s/staging/service.yaml
kubectl delete -f k8s/staging/postgres-deployment.yaml
kubectl delete -f k8s/staging/postgres-service.yaml
kubectl delete -f k8s/staging/redis-deployment.yaml
kubectl delete -f k8s/staging/redis-service.yaml
kubectl delete -f k8s/staging/ingress.yaml

# Eliminar ConfigMaps
kubectl delete configmap tavira-nginx-config-staging
kubectl delete configmap postgres-config-staging

# (Opcional) Eliminar PVCs (⚠️ esto borrará los datos)
kubectl delete -f k8s/staging/pvcs.yaml

# (Opcional) Eliminar Secrets
kubectl delete secret laravel-env-staging
```

## 🔐 Secrets Management

### Variables Requeridas en el Secret

El secret `laravel-env-staging` debe contener:

- `APP_KEY`: Clave de encriptación de Laravel
- `DB_DATABASE`: Nombre de la base de datos
- `DB_USERNAME`: Usuario de PostgreSQL
- `DB_PASSWORD`: Contraseña de PostgreSQL
- `CACHE_DRIVER`: Driver de cache (redis)
- `REDIS_HOST`: Host de Redis
- `REDIS_PORT`: Puerto de Redis
- `QUEUE_CONNECTION`: Conexión de cola (redis)
- `SESSION_DRIVER`: Driver de sesión (redis)

### Actualizar un Secret

```bash
# Ver el secret actual (base64 encoded)
kubectl get secret laravel-env-staging -o yaml

# Actualizar un valor específico
kubectl patch secret laravel-env-staging -p='{"stringData":{"DB_PASSWORD":"nuevo_password"}}'

# O eliminar y recrear
kubectl delete secret laravel-env-staging
kubectl create secret generic laravel-env-staging --from-literal=...
```

## 📝 Notas Importantes

1. **Dominio**: Asegúrate de que el dominio `staging.tavira.com.co` apunte a tu cluster
2. **Certificados SSL**: cert-manager generará automáticamente certificados Let's Encrypt
3. **Base de Datos**: La base de datos de staging es independiente de producción
4. **Recursos**: Staging usa recursos mínimos, no es apto para pruebas de carga
5. **Debug**: El modo debug está habilitado en staging (`APP_DEBUG=true`)
6. **Multitenancy**: Cada tenant tendrá su propia base de datos con prefijo `tenant{id}_staging`

## 🆚 Diferencias con Producción

| Aspecto | Producción | Staging |
|---------|-----------|---------|
| Replicas App | 2 | 1 |
| Replicas Worker | 2 | 1 |
| CPU Requests | 100m-250m | 50m-100m |
| Memory Requests | 256Mi-512Mi | 128Mi-256Mi |
| Storage | 10Gi | 5Gi |
| Debug Mode | false | true |
| Cache TTL | Largo | Corto |
| Logs | Normal | Verbose |

## 🔗 URLs

- **Staging**: https://staging.tavira.com.co
- **Tenant Example**: https://torres.staging.tavira.com.co
- **Producción**: https://tavira.com.co

## 📞 Soporte

Para problemas o preguntas sobre el ambiente staging, revisa:
1. Logs de los pods con `kubectl logs`
2. Estado de los deployments con `kubectl describe`
3. Eventos del cluster con `kubectl get events --sort-by='.lastTimestamp'`
