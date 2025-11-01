# Guía de Inicio Rápido - Staging

Esta guía te ayudará a desplegar staging por primera vez en **menos de 5 minutos**.

## ⚡ Despliegue Rápido (Primera Vez)

### Paso 1: Crear Secrets

```bash
# Generar APP_KEY (si no tienes uno)
APP_KEY=$(php artisan key:generate --show)

# Crear secret de staging
kubectl create secret generic laravel-env-staging \
  --from-literal=APP_KEY="$APP_KEY" \
  --from-literal=DB_DATABASE="tavira_staging" \
  --from-literal=DB_USERNAME="tavira_user_staging" \
  --from-literal=DB_PASSWORD="CAMBIA_ESTE_PASSWORD_POR_UNO_SEGURO" \
  --from-literal=CACHE_DRIVER="redis" \
  --from-literal=REDIS_HOST="redis-staging" \
  --from-literal=REDIS_PORT="6379" \
  --from-literal=QUEUE_CONNECTION="redis" \
  --from-literal=SESSION_DRIVER="redis"
```

### Paso 2: Aplicar Manifiestos

```bash
# Desde la raíz del proyecto
kubectl apply -f k8s/staging/
```

Esto creará:
- ✅ ConfigMaps (Nginx y PostgreSQL)
- ✅ PVCs (almacenamiento persistente)
- ✅ Deployments (App, Queue Worker, PostgreSQL, Redis)
- ✅ Services
- ✅ Ingress

### Paso 3: Esperar a que todo esté listo

```bash
# Verificar que todos los pods estén corriendo
kubectl get pods -l environment=staging -w

# Presiona Ctrl+C cuando todos muestren 2/2 o 1/1 Running
```

### Paso 4: Ejecutar Migraciones

```bash
# Obtener el nombre del pod de la app
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')

# Ejecutar migraciones centrales
kubectl exec $POD -c php-fpm -- php artisan migrate --force

# Ejecutar migraciones de tenants (si ya tienes tenants)
kubectl exec $POD -c php-fpm -- php artisan tenants:migrate --force
```

### Paso 5: (Opcional) Seed de datos

```bash
# Seed de datos de prueba
kubectl exec $POD -c php-fpm -- php artisan db:seed --force
```

## ✅ Verificar que todo funciona

```bash
# Ver estado
kubectl get all -l environment=staging

# Ver logs de la app
kubectl logs -f deployment/tavira-app-staging -c php-fpm

# Probar acceso (si ya configuraste DNS)
curl -I https://staging.tavira.com.co
```

## 🔄 Despliegues Posteriores

Una vez que todo está configurado, los despliegues son **automáticos**:

```bash
git checkout develop
git add .
git commit -m "feat: mi nueva feature"
git push origin develop
```

→ Se despliega automáticamente a staging

## 🆘 Problemas Comunes

### Secret no existe

```bash
# Verificar secrets
kubectl get secret laravel-env-staging

# Si no existe, créalo (paso 1)
```

### PVC no se puede crear

```bash
# Verificar storage classes disponibles
kubectl get storageclass

# Si no hay ninguno, edita k8s/staging/pvcs.yaml
# y cambia 'storageClassName: local-path' por uno disponible
```

### PostgreSQL no inicia

```bash
# Ver logs
kubectl logs deployment/postgres-staging

# Verificar que el secret existe
kubectl get secret laravel-env-staging
```

### App no puede conectarse a la base de datos

```bash
# Verificar que PostgreSQL está corriendo
kubectl get pods -l app=postgres-staging

# Verificar secret
kubectl describe secret laravel-env-staging

# Ver logs de la app
kubectl logs deployment/tavira-app-staging -c php-fpm
```

## 📝 Configuración DNS (Opcional)

Si quieres acceder por dominio:

1. Obtén la IP del Ingress:
```bash
kubectl get ingress tavira-ingress-staging
```

2. Configura DNS:
```
staging.tavira.com.co    A    <IP_DEL_INGRESS>
*.staging.tavira.com.co  A    <IP_DEL_INGRESS>
```

## 🔧 Script Automático

Alternativamente, usa el script de deployment:

```bash
./k8s/staging/deploy.sh all
```

Este script te guiará paso a paso.

---

**¿Necesitas más ayuda?** Revisa el [README completo](README.md)
