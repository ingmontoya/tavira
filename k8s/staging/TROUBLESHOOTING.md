# Troubleshooting - Staging Deployment

## Error: "Staging deployment failed!" pero el pod está Running

### Síntoma

El workflow de GitHub Actions muestra:
```
❌ Staging deployment failed!
```

Pero al verificar, el pod está corriendo:
```
tavira-app-staging-76b5559c5b-xkm4q   2/2     Running   0          26s
```

### Causa

El deployment fue exitoso, pero el paso de **post-deployment tasks** falló. Esto puede ocurrir por:

1. **Primera vez desplegando**: Los recursos de infraestructura (PostgreSQL, Redis, ConfigMaps) no existen
2. **Base de datos no lista**: PostgreSQL está iniciando pero aún no acepta conexiones
3. **Secret faltante**: El secret `laravel-env-staging` no existe

### Solución

#### Opción 1: Setup inicial completo (Recomendado para primera vez)

```bash
# 1. Crear el secret
kubectl create secret generic laravel-env-staging \
  --from-literal=APP_KEY="base64:$(openssl rand -base64 32)" \
  --from-literal=DB_DATABASE="tavira_staging" \
  --from-literal=DB_USERNAME="tavira_user_staging" \
  --from-literal=DB_PASSWORD="cambiar_password_seguro" \
  --from-literal=CACHE_DRIVER="redis" \
  --from-literal=REDIS_HOST="redis-staging" \
  --from-literal=REDIS_PORT="6379" \
  --from-literal=QUEUE_CONNECTION="redis" \
  --from-literal=SESSION_DRIVER="redis"

# 2. Aplicar toda la infraestructura
kubectl apply -f k8s/staging/

# 3. Esperar a que PostgreSQL esté listo (2-3 minutos)
kubectl wait --for=condition=ready pod -l app=postgres-staging --timeout=180s

# 4. Esperar a que Redis esté listo
kubectl wait --for=condition=ready pod -l app=redis-staging --timeout=120s

# 5. Esperar a que la app esté lista
kubectl wait --for=condition=ready pod -l app=tavira-staging --timeout=180s

# 6. Ejecutar migraciones manualmente
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')
kubectl exec $POD -c php-fpm -- php artisan migrate --force
kubectl exec $POD -c php-fpm -- php artisan tenants:migrate --force
```

#### Opción 2: Usar el script automático

```bash
./k8s/staging/deploy.sh all
```

#### Opción 3: Fix rápido (si ya está corriendo)

Si el pod ya está corriendo pero las migraciones fallaron:

```bash
# Obtener el nombre del pod
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')

# Verificar que el pod está corriendo
kubectl get pod $POD

# Ejecutar migraciones manualmente
kubectl exec $POD -c php-fpm -- php artisan migrate --force
kubectl exec $POD -c php-fpm -- php artisan tenants:migrate --force

# Cachear configuración
kubectl exec $POD -c php-fpm -- php artisan config:cache
kubectl exec $POD -c php-fpm -- php artisan view:cache
```

### Verificación

1. **Verificar que todos los recursos existen**:
```bash
# Secrets
kubectl get secret laravel-env-staging

# ConfigMaps
kubectl get configmap -l environment=staging

# PVCs
kubectl get pvc -l environment=staging

# Deployments
kubectl get deployments -l environment=staging

# Services
kubectl get services -l environment=staging

# Pods
kubectl get pods -l environment=staging
```

2. **Ver logs de los pods**:
```bash
# Logs de la app
kubectl logs -f deployment/tavira-app-staging -c php-fpm

# Logs de PostgreSQL
kubectl logs deployment/postgres-staging

# Logs de Redis
kubectl logs deployment/redis-staging
```

3. **Probar conectividad a la base de datos**:
```bash
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')
kubectl exec $POD -c php-fpm -- php artisan db:show
```

### Prevención

Para evitar este error en futuros deployments:

1. **Primera vez**: Sigue la [guía QUICKSTART.md](QUICKSTART.md) antes de hacer push a `develop`

2. **Deployments posteriores**: El pipeline corregido ahora:
   - Tiene `continue-on-error: true` en post-deployment tasks
   - Verifica la conexión a la base de datos antes de ejecutar migraciones
   - No falla si las migraciones fallan (solo advierte)
   - Incluye un paso final de verificación de salud

---

## Error: "View path not found" durante view:cache

### Síntoma

El workflow muestra:
```
→ Caching views...
View path not found.
command terminated with exit code 1
```

### Causa

El comando `php artisan view:cache` intenta compilar las vistas de Blade, pero:
1. El directorio `resources/views` aún no se copió completamente
2. El init container está ejecutándose o acaba de terminar
3. Los archivos aún no están disponibles en el volumen compartido

### Solución

**Este error NO es crítico** - el workflow ahora continúa aunque falle. Las vistas se compilarán just-in-time cuando se acceda a ellas.

Si quieres cachear las vistas manualmente después:

```bash
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')

# Verificar que los archivos existen
kubectl exec $POD -c php-fpm -- ls -la /var/www/html/resources/views

# Cachear vistas
kubectl exec $POD -c php-fpm -- php artisan view:cache
```

### Prevención

El workflow actualizado ahora:
- Tiene mejor manejo de errores (`|| true`)
- Suprime stderr para comandos no críticos (`2>/dev/null`)
- Muestra advertencias en lugar de fallar
- Verifica que los archivos de la aplicación existan antes de continuar

---

## Otros Problemas Comunes

### Pod en estado CrashLoopBackOff

```bash
# Ver logs
kubectl logs deployment/tavira-app-staging -c php-fpm

# Causas comunes:
# 1. APP_KEY inválida o faltante
# 2. No puede conectarse a PostgreSQL
# 3. Error en el código de la aplicación
```

**Solución**:
```bash
# Verificar secret
kubectl describe secret laravel-env-staging

# Verificar que PostgreSQL está corriendo
kubectl get pods -l app=postgres-staging

# Ver eventos del pod
kubectl describe pod <pod-name>
```

### ImagePullBackOff

```bash
# Ver estado
kubectl get pods -l app=tavira-staging
```

**Solución**:
```bash
# Verificar que la imagen existe en Docker Hub
# https://hub.docker.com/r/ingmontoyav/tavira-app/tags

# El pipeline crea automáticamente tags 'staging' y 'staging-vYYYYMMDD-sha'
```

### Ingress no funciona / 502 Bad Gateway

**Síntomas**: `curl https://staging.tavira.com.co` devuelve error

**Solución**:
```bash
# 1. Verificar que el ingress existe
kubectl get ingress tavira-ingress-staging

# 2. Verificar que cert-manager generó el certificado
kubectl get certificates

# 3. Ver logs de traefik/ingress controller
kubectl logs -n kube-system deployment/traefik

# 4. Verificar que el service apunta correctamente
kubectl describe service tavira-service-staging

# 5. Probar acceso directo al service
kubectl port-forward service/tavira-service-staging 8080:80
# Luego: curl http://localhost:8080
```

### PVC queda en estado Pending

```bash
# Ver estado
kubectl get pvc -l environment=staging
```

**Causas**:
- No hay StorageClass disponible
- No hay espacio en disco

**Solución**:
```bash
# Verificar storage classes
kubectl get storageclass

# Si no hay ninguno disponible, edita k8s/staging/pvcs.yaml
# y cambia 'storageClassName: local-path' por uno disponible

# O usa hostPath para testing (NO recomendado para producción)
```

### Base de datos vacía después del deployment

```bash
# Ejecutar migraciones manualmente
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')
kubectl exec $POD -c php-fpm -- php artisan migrate --force

# Verificar tablas
kubectl exec deployment/postgres-staging -- psql -U tavira_user_staging -d tavira_staging -c "\dt"
```

---

## Comandos Útiles de Debug

```bash
# Ver todos los recursos de staging
kubectl get all -l environment=staging

# Ver eventos recientes
kubectl get events --sort-by='.lastTimestamp' | head -20

# Ver logs de todos los containers de un pod
kubectl logs <pod-name> --all-containers

# Ejecutar shell en el pod
kubectl exec -it <pod-name> -c php-fpm -- sh

# Ver uso de recursos
kubectl top pods -l environment=staging

# Eliminar y recrear un deployment
kubectl delete deployment tavira-app-staging
kubectl apply -f k8s/staging/deployment.yaml
```

---

## Obtener Ayuda

Si ninguna de estas soluciones funciona:

1. Revisa los logs completos del workflow en GitHub Actions
2. Ejecuta `kubectl describe pod <pod-name>` y revisa los eventos
3. Verifica que tienes los permisos correctos en el cluster
4. Consulta la documentación completa en [README.md](README.md)
