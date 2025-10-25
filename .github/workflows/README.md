# GitHub Actions Workflows

Este directorio contiene los workflows de CI/CD para Tavira.

## 🚀 Workflows Disponibles

### 1. `deploy.yml` - Deploy Automático a Producción

**Trigger**: Push a `main` o ejecución manual

**Proceso**:
1. Build de imágenes Docker (PHP-FPM y Nuxt)
2. Push a Docker Hub
3. Rolling update en Kubernetes
4. Ejecución de migraciones (central y tenants)
5. Limpieza de caches de Laravel

### 2. `tests.yml` - Tests Automáticos

**Trigger**: Push a cualquier rama

**Proceso**:
- Ejecuta tests de PHP (PHPUnit/Pest)

### 3. `lint.yml` - Linting de Código

**Trigger**: Push a cualquier rama

**Proceso**:
- Ejecuta linters de código para mantener calidad

## 🔐 Secrets Requeridos

Para que el workflow de deploy funcione, necesitas configurar los siguientes secrets en GitHub:

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

### `KUBECONFIG`
Tu archivo kubeconfig en formato base64

**Cómo obtenerlo:**
```bash
# En tu máquina local donde tienes acceso al cluster
cat ~/.kube/config | base64 -w 0

# En macOS:
cat ~/.kube/config | base64

# Copia el output completo (será una línea muy larga)
```

**Importante**:
- El kubeconfig debe tener permisos para:
  - `kubectl set image deployment/tavira-app`
  - `kubectl exec` en pods
  - `kubectl get pods/deployments`
  - `kubectl rollout status`

## 📋 Cómo Configurar el Deploy Automático

### Paso 1: Configurar Secrets

Sigue las instrucciones arriba para configurar los 3 secrets requeridos.

### Paso 2: Verificar Configuración

Edita `.github/workflows/deploy.yml` y verifica:

```yaml
env:
  DOCKER_IMAGE_PHP: ingmontoyav/tavira-app      # ✅ Tu usuario/imagen
  DOCKER_IMAGE_NUXT: ingmontoyav/tavira-nuxt    # ✅ Tu usuario/imagen
  DEPLOYMENT_NAME: tavira-app                   # ✅ Nombre del deployment en K8s
  K8S_NAMESPACE: default                        # ✅ Namespace correcto
```

### Paso 3: Hacer Push a Main

```bash
git add .
git commit -m "feat: Add auto-deployment workflow"
git push origin main
```

### Paso 4: Verificar Ejecución

1. Ve a tu repositorio en GitHub
2. Click en "Actions"
3. Verás el workflow "Deploy to Production" ejecutándose
4. Click en el workflow para ver el progreso en tiempo real

## 🔄 Workflow de Deploy

```
Push a main
    ↓
GitHub Actions detecta el push
    ↓
Build imagen Docker PHP (Dockerfile)
    ↓
Build imagen Docker Nuxt (Dockerfile.nuxt)
    ↓
Push imágenes a Docker Hub con tags:
  - latest
  - v20251025-a1b2c3d (version + commit sha)
    ↓
Conectar a Kubernetes cluster
    ↓
Rolling update del deployment
    ↓
Esperar que el rollout complete
    ↓
Ejecutar post-deploy tasks:
  - php artisan config:clear
  - php artisan config:cache
  - php artisan route:cache
  - php artisan view:cache
  - php artisan migrate --force
  - php artisan tenants:migrate --force
    ↓
Verificar deployment exitoso
    ↓
✅ Deploy completado
```

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
