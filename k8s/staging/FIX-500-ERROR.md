# Solucionar Error 500 y SSL en Staging

## Problema 1: Error 500

### Paso 1: Ver logs de la aplicación

```bash
# Obtener el nombre del pod
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')

# Ver logs de PHP-FPM (errores de Laravel)
kubectl logs $POD -c php-fpm --tail=100

# Ver logs de Nginx
kubectl logs $POD -c nginx --tail=50
```

### Paso 2: Causas comunes del Error 500

#### A. APP_KEY no está configurada

```bash
# Verificar APP_KEY
kubectl get secret laravel-env-staging -o jsonpath='{.data.APP_KEY}' | base64 -d
echo ""

# Si está vacía o no existe, crear/actualizar el secret:
kubectl delete secret laravel-env-staging 2>/dev/null
kubectl create secret generic laravel-env-staging \
  --from-literal=APP_KEY="base64:$(openssl rand -base64 32)" \
  --from-literal=DB_DATABASE="tavira_staging" \
  --from-literal=DB_USERNAME="tavira_user_staging" \
  --from-literal=DB_PASSWORD="tu_password_aqui" \
  --from-literal=CACHE_DRIVER="redis" \
  --from-literal=REDIS_HOST="redis-staging" \
  --from-literal=REDIS_PORT="6379" \
  --from-literal=QUEUE_CONNECTION="redis" \
  --from-literal=SESSION_DRIVER="redis"

# Reiniciar el deployment
kubectl rollout restart deployment/tavira-app-staging
```

#### B. Base de datos no existe o no tiene tablas

```bash
# Conectarse a PostgreSQL
kubectl exec -it deployment/postgres-staging -- psql -U tavira_user_staging -d tavira_staging

# Dentro de psql, verificar tablas:
\dt

# Si no hay tablas, salir (\q) y ejecutar migraciones:
kubectl exec $POD -c php-fpm -- php artisan migrate --force
```

#### C. Error de permisos en storage

```bash
# Verificar y arreglar permisos
kubectl exec $POD -c php-fpm -- sh -c "
  chmod -R 775 /var/www/html/storage
  chmod -R 775 /var/www/html/bootstrap/cache
"

# Reiniciar el pod
kubectl delete pod $POD
```

#### D. Redis no está disponible

```bash
# Verificar que Redis está corriendo
kubectl get pods -l app=redis-staging

# Ver logs de Redis
kubectl logs deployment/redis-staging

# Si no está corriendo, aplicar:
kubectl apply -f k8s/staging/redis-deployment.yaml
kubectl apply -f k8s/staging/redis-service.yaml
```

### Paso 3: Verificar la configuración de Laravel

```bash
# Ver la configuración actual
kubectl exec $POD -c php-fpm -- php artisan config:show app.debug
kubectl exec $POD -c php-fpm -- php artisan config:show app.url
kubectl exec $POD -c php-fpm -- php artisan config:show database.default

# Probar conexión a la base de datos
kubectl exec $POD -c php-fpm -- php artisan db:show
```

### Paso 4: Limpiar cache y reiniciar

```bash
# Limpiar todos los caches
kubectl exec $POD -c php-fpm -- php artisan cache:clear
kubectl exec $POD -c php-fpm -- php artisan config:clear
kubectl exec $POD -c php-fpm -- php artisan view:clear

# Regenerar cache
kubectl exec $POD -c php-fpm -- php artisan config:cache

# Reiniciar el deployment
kubectl rollout restart deployment/tavira-app-staging
```

---

## Problema 2: Sin SSL

### Paso 1: Verificar cert-manager

```bash
# Verificar que cert-manager está instalado
kubectl get pods -n cert-manager

# Si no está instalado, instalarlo:
kubectl apply -f https://github.com/cert-manager/cert-manager/releases/download/v1.13.0/cert-manager.yaml

# Esperar a que esté listo
kubectl wait --for=condition=ready pod -l app.kubernetes.io/instance=cert-manager -n cert-manager --timeout=120s
```

### Paso 2: Verificar ClusterIssuer

```bash
# Verificar que existe el cluster issuer
kubectl get clusterissuer letsencrypt-prod

# Si no existe, crearlo:
cat <<EOF | kubectl apply -f -
apiVersion: cert-manager.io/v1
kind: ClusterIssuer
metadata:
  name: letsencrypt-prod
spec:
  acme:
    server: https://acme-v02.api.letsencrypt.org/directory
    email: tu-email@ejemplo.com  # CAMBIAR POR TU EMAIL
    privateKeySecretRef:
      name: letsencrypt-prod
    solvers:
    - http01:
        ingress:
          class: traefik
EOF
```

### Paso 3: Verificar el Ingress

```bash
# Ver el ingress
kubectl get ingress tavira-ingress-staging

# Ver detalles
kubectl describe ingress tavira-ingress-staging

# Si no existe, aplicarlo:
kubectl apply -f k8s/staging/ingress.yaml
```

### Paso 4: Verificar el certificado

```bash
# Ver certificados
kubectl get certificates

# Ver detalles del certificado
kubectl describe certificate tavira-tls-staging

# Ver challenges (si hay problemas)
kubectl get challenges

# Ver logs de cert-manager
kubectl logs -n cert-manager deployment/cert-manager --tail=100
```

### Paso 5: Forzar regeneración del certificado

Si el certificado no se genera automáticamente:

```bash
# Eliminar el certificado existente (si existe)
kubectl delete certificate tavira-tls-staging 2>/dev/null

# Eliminar el secret del certificado
kubectl delete secret tavira-tls-staging 2>/dev/null

# Eliminar y recrear el ingress
kubectl delete ingress tavira-ingress-staging
kubectl apply -f k8s/staging/ingress.yaml

# Esperar 2-3 minutos y verificar
kubectl get certificates -w
```

### Paso 6: Verificar DNS

```bash
# Verificar que el dominio apunta al cluster
nslookup staging.tavira.com.co

# Debe mostrar la IP del ingress
kubectl get ingress tavira-ingress-staging -o jsonpath='{.status.loadBalancer.ingress[0].ip}'
```

---

## Script de Diagnóstico Completo

Ejecuta este comando para un diagnóstico completo:

```bash
# Ver estado general
kubectl get all -l environment=staging

# Ver logs de PHP-FPM
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')
kubectl logs $POD -c php-fpm --tail=100

# Ver estado del ingress y certificados
kubectl get ingress,certificates

# Probar acceso interno
kubectl exec $POD -c php-fpm -- curl -I http://localhost
```

---

## Solución Rápida (Reset Completo)

Si nada funciona, reset completo:

```bash
# 1. Eliminar todo
kubectl delete -f k8s/staging/

# 2. Esperar 30 segundos
sleep 30

# 3. Recrear secret
kubectl create secret generic laravel-env-staging \
  --from-literal=APP_KEY="base64:$(openssl rand -base64 32)" \
  --from-literal=DB_DATABASE="tavira_staging" \
  --from-literal=DB_USERNAME="tavira_user_staging" \
  --from-literal=DB_PASSWORD="password_seguro_aqui" \
  --from-literal=CACHE_DRIVER="redis" \
  --from-literal=REDIS_HOST="redis-staging" \
  --from-literal=REDIS_PORT="6379" \
  --from-literal=QUEUE_CONNECTION="redis" \
  --from-literal=SESSION_DRIVER="redis"

# 4. Aplicar todo de nuevo
kubectl apply -f k8s/staging/

# 5. Esperar a que todo esté listo
kubectl get pods -l environment=staging -w

# 6. Ejecutar migraciones
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')
kubectl exec $POD -c php-fpm -- php artisan migrate --force
```

---

## Verificación Final

Una vez solucionado:

```bash
# 1. Probar health check
curl http://staging.tavira.com.co/health

# 2. Verificar SSL (puede tomar 2-5 minutos)
curl -I https://staging.tavira.com.co

# 3. Ver en el navegador
open https://staging.tavira.com.co
```

---

## Si Sigues Teniendo Problemas

Envía estos diagnósticos:

```bash
# Pods
kubectl get pods -l environment=staging

# Logs de la app
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')
kubectl logs $POD -c php-fpm --tail=50

# Ingress y certificados
kubectl describe ingress tavira-ingress-staging
kubectl describe certificate tavira-tls-staging

# Events
kubectl get events --sort-by='.lastTimestamp' | grep -i staging | head -20
```
