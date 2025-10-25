# GitHub Actions Workflows

Este directorio contiene los workflows de CI/CD para Tavira.

## 🚀 Workflows Disponibles

### 1. `deploy.yml` - Build Automático + Deploy Manual

**Trigger**: Push a `main` o ejecución manual

**Proceso**:
1. Build de imágenes Docker (PHP-FPM y Nuxt)
2. Push a Docker Hub con tags automáticos
3. Muestra instrucciones para deploy manual

**⚙️ Semi-automático**: Push hace build automático, luego ejecutas script para deploy.

### 2. `tests.yml` - Tests Automáticos

**Trigger**: Push a cualquier rama

**Proceso**:
- Ejecuta tests de PHP (PHPUnit/Pest)

### 3. `lint.yml` - Linting de Código

**Trigger**: Push a cualquier rama

**Proceso**:
- Ejecuta linters de código para mantener calidad

## 🔐 Secrets Requeridos

Para que el workflow funcione, necesitas configurar estos 2 secrets en GitHub:

### Configurar Secrets en GitHub

1. Ve a tu repositorio en GitHub
2. Settings → Secrets and variables → Actions
3. Click en "New repository secret"
4. Agrega los siguientes secrets:

### `DOCKER_USERNAME`
Tu username de Docker Hub

```
Ejemplo: ingmontoyav
```

### `DOCKER_PASSWORD`
Tu password o Personal Access Token de Docker Hub

**Cómo obtenerlo:**
1. Ve a https://hub.docker.com/settings/security
2. Click en "New Access Token"
3. Nombre: "GitHub Actions - Tavira"
4. Permisos: Read & Write
5. Copia el token generado

**✅ Ya configurado** - Estos secrets ya están en tu repositorio

## 📋 Cómo Usar el Workflow

### Paso 1: Hacer Cambios y Push

```bash
# Hacer cambios en el código
git add .
git commit -m "feat: nueva funcionalidad"
git push origin main
```

### Paso 2: Esperar el Build

1. Ve a https://github.com/ingmontoya/tavira/actions
2. El workflow automáticamente:
   - ✅ Build de imágenes Docker
   - ✅ Push a Docker Hub
   - ✅ Genera tag con versión (ej: v20251025-abc1234)

### Paso 3: Deploy a Kubernetes

Cuando el workflow termine, copia el tag de la versión y ejecuta:

```bash
# Opción 1 - Script automático (recomendado)
./scripts/deploy.sh v20251025-abc1234

# Opción 2 - Usar 'latest'
./scripts/deploy.sh latest
```

El script automáticamente:
- ✅ Deploy a Kubernetes
- ✅ Rolling update
- ✅ Ejecución de migraciones (central + tenants)
- ✅ Limpieza de caches
- ✅ Verificación del deployment

## 🔄 Workflow de Deploy (SEMI-AUTOMÁTICO)

```
Push a main
    ↓
GitHub Actions detecta el push
    ↓
Build imagen Docker PHP (Dockerfile.php)
    ↓
Build imagen Docker Nuxt (Dockerfile)
    ↓
Push imágenes a Docker Hub con tags:
  - latest
  - v20251025-abc1234 (version + commit sha)
    ↓
✅ Build completo - Muestra instrucciones
    ↓
[MANUAL] En tu máquina local:
    ./scripts/deploy.sh v20251025-abc1234
    ↓
Rolling update en Kubernetes
    ↓
Esperar que el rollout complete
    ↓
Post-deploy tasks automáticos:
  - php artisan config:clear
  - php artisan config:cache
  - php artisan route:cache
  - php artisan view:cache
  - php artisan migrate --force (central)
  - php artisan tenants:migrate --force (todos los tenants)
    ↓
✅ Deploy completado a producción
    ↓
App actualizada en https://tavira.com.co
```

**⏱️ Tiempo total**: ~2-3 min (build) + ~2-3 min (deploy manual)

## 🛠️ Troubleshooting

### Error: "permission denied"
**Causa**: KUBECONFIG no tiene permisos suficientes

**Solución**: Verifica que el usuario en kubeconfig tiene los permisos necesarios

### Error: "docker login failed"
**Causa**: DOCKER_USERNAME o DOCKER_PASSWORD incorrectos

**Solución**:
1. Verifica que los secrets estén configurados correctamente
2. Genera un nuevo Access Token en Docker Hub
3. Actualiza el secret DOCKER_PASSWORD

### Error: "context deadline exceeded"
**Causa**: El rollout está tardando más de 10 minutos

**Solución**:
1. Verifica logs del pod: `kubectl logs <pod-name> -c php-fpm`
2. Verifica que la imagen se construyó correctamente
3. Aumenta el timeout en deploy.yml si es necesario

### Error: "migration failed"
**Causa**: Error en alguna migración

**Solución**:
1. Revisa los logs del workflow
2. Conéctate al cluster y ejecuta manualmente:
   ```bash
   kubectl exec -it <pod-name> -c php-fpm -- php artisan migrate:status
   ```
3. Rollback si es necesario:
   ```bash
   kubectl rollout undo deployment/tavira-app
   ```

## 🎯 Deploy Manual

Si necesitas hacer deploy manual sin esperar a un push:

1. Ve a Actions → Deploy to Production
2. Click en "Run workflow"
3. Select branch: main
4. Click en "Run workflow"

## 📊 Monitoreo Post-Deploy

Después de cada deploy, verifica:

```bash
# Ver status del deployment
kubectl get deployment tavira-app

# Ver pods
kubectl get pods -l app=tavira-app

# Ver logs en tiempo real
kubectl logs -f <pod-name> -c php-fpm

# Verificar que la app responde
curl https://tavira.com.co/health
```

## 🔒 Seguridad

- ✅ Los secrets nunca se exponen en logs
- ✅ KUBECONFIG está encriptado en GitHub
- ✅ Las imágenes Docker se construyen desde source
- ✅ Rolling update asegura zero-downtime
- ✅ Rollback automático en caso de fallo

## 📚 Referencias

- [GitHub Actions Docs](https://docs.github.com/en/actions)
- [Docker Build Action](https://github.com/docker/build-push-action)
- [Kubernetes Deployments](https://kubernetes.io/docs/concepts/workloads/controllers/deployment/)
- [Laravel Deployment](https://laravel.com/docs/deployment)

---

**Última actualización**: 2025-10-25
