# Development Workflow

Este documento describe el flujo de trabajo completo para desarrollo local, staging y producción de Tavira.

## 🏗️ Arquitectura de Ambientes

```
┌─────────────────────────────────────────────────────────────────┐
│                    DESARROLLO LOCAL                              │
│  • docker-compose up -d                                          │
│  • Hot reload automático (Vite)                                  │
│  • Sin construcción de imágenes                                  │
│  • Base de datos PostgreSQL local                                │
│  • Redis local                                                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ git push origin develop
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         STAGING                                  │
│  • Kubernetes namespace: default (cluster staging)               │
│  • GitHub Actions construye imagen                               │
│  • Deploy automático con imagen: develop-<sha>                   │
│  • URL: https://staging.tavira.com.co                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ Pull Request develop → main
                              │ Review + Approve + Merge
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        PRODUCCIÓN                                │
│  • Kubernetes namespace: default (cluster producción)            │
│  • GitHub Actions construye imagen                               │
│  • Deploy manual o automático                                    │
│  • Imagen: v20251107-<sha>                                       │
│  • URL: https://tavira.com.co                                    │
└─────────────────────────────────────────────────────────────────┘
```

## 🖥️ Desarrollo Local

### Configuración Inicial

1. **Clonar el repositorio**:
   ```bash
   git clone https://github.com/your-org/tavira.git
   cd tavira
   ```

2. **Copiar archivo de configuración**:
   ```bash
   cp .env.example .env
   ```

3. **Configurar variables de entorno** (`.env`):
   ```env
   APP_NAME=Tavira
   APP_ENV=local
   APP_DEBUG=true
   APP_URL=http://localhost

   DB_CONNECTION=pgsql
   DB_HOST=postgres
   DB_PORT=5432
   DB_DATABASE=tavira_local
   DB_USERNAME=tavira
   DB_PASSWORD=tavira

   CACHE_DRIVER=redis
   REDIS_HOST=redis
   REDIS_PORT=6379

   QUEUE_CONNECTION=redis
   SESSION_DRIVER=redis
   ```

4. **Instalar dependencias**:
   ```bash
   composer install
   npm install
   ```

### Flujo de Trabajo Diario

#### Iniciar el entorno
```bash
# Levantar servicios (PostgreSQL, Redis, Mailpit)
docker-compose up -d

# Generar clave de aplicación (solo la primera vez)
php artisan key:generate

# Ejecutar migraciones
php artisan migrate

# Opcional: Seeders
php artisan db:seed

# Iniciar servidor de desarrollo Laravel + Vite
composer dev
```

**Nota**: `composer dev` ejecuta:
- Laravel development server (php artisan serve)
- Queue worker (php artisan queue:work)
- Log viewer (php artisan pail)
- Vite dev server (npm run dev)

#### Desarrollo de código

1. **Hacer cambios en el código**
   - Los cambios en archivos `.php`, `.vue`, `.ts`, `.css` se reflejan automáticamente
   - Vite HMR (Hot Module Replacement) recarga la página automáticamente

2. **Verificar cambios**:
   ```bash
   # Ejecutar tests
   composer test

   # Verificar formato de código
   ./vendor/bin/pint
   npm run lint

   # Type checking
   vue-tsc --noEmit
   ```

3. **Commit y push**:
   ```bash
   git add .
   git commit -m "feat: descripción del cambio"
   git push origin develop
   ```

#### Detener el entorno
```bash
# Detener servicios de Docker
docker-compose down

# Para eliminar también los volúmenes
docker-compose down -v
```

### Comandos Útiles

```bash
# Ver logs de Docker Compose
docker-compose logs -f

# Acceder a la base de datos
docker-compose exec postgres psql -U tavira -d tavira_local

# Acceder a Redis CLI
docker-compose exec redis redis-cli

# Reconstruir contenedores (solo si cambias docker-compose.yml)
docker-compose up -d --build

# Ver correos en desarrollo
# Acceder a http://localhost:8025 (Mailpit)
```

### Ventajas del Desarrollo Local

- ✅ **Sin construcción de imágenes**: Cambios instantáneos sin rebuild
- ✅ **Hot reload**: Vite actualiza automáticamente el navegador
- ✅ **Debug fácil**: Laravel Debug Bar, logs en tiempo real
- ✅ **Tests rápidos**: Ejecutar tests sin desplegar
- ✅ **Aislamiento**: Cada desarrollador tiene su propio ambiente

## 🧪 Staging (Develop Branch)

### Propósito
- Probar cambios antes de producción
- QA y testing de integración
- Validación de nuevas features

### Flujo de Despliegue

1. **Push a develop**:
   ```bash
   git push origin develop
   ```

2. **GitHub Actions (automático)**:
   - Construye la imagen Docker
   - Tag: `ingmontoyav/tavira-app:develop-<sha>`
   - Ejecuta tests
   - Despliega a Kubernetes staging

3. **Verificar deployment**:
   ```bash
   # Ver estado de pods en staging
   kubectl --context staging get pods -l app=tavira

   # Ver logs
   kubectl --context staging logs -l app=tavira -c php-fpm --tail=100
   ```

4. **Probar en staging**:
   - URL: https://staging.tavira.com.co
   - Validar funcionalidad
   - Ejecutar tests E2E si es necesario

### Comandos Staging

```bash
# Cambiar al contexto de staging
kubectl config use-context staging

# Ver estado del deployment
kubectl get deployment tavira-app

# Rollback si es necesario
kubectl rollout undo deployment/tavira-app

# Ejecutar comando en el pod
kubectl exec -it deployment/tavira-app -- php artisan tinker

# Ver variables de entorno
kubectl exec deployment/tavira-app -- env | grep APP
```

## 🚀 Producción (Main Branch)

### Flujo de Despliegue

1. **Crear Pull Request**:
   ```bash
   # Desde develop hacia main
   gh pr create --base main --head develop --title "Release vX.Y.Z"
   ```

2. **Code Review**:
   - Revisión de código por el equipo
   - Verificación en staging
   - Aprobación del PR

3. **Merge a main**:
   - Merge del PR aprobado
   - GitHub Actions construye imagen: `ingmontoyav/tavira-app:v20251107-<sha>`

4. **Deploy a producción**:

   **Opción A: Deploy Automático (recomendado)**
   - GitHub Actions despliega automáticamente al hacer merge a main

   **Opción B: Deploy Manual**
   ```bash
   # Cambiar al contexto de producción
   kubectl config use-context default

   # Actualizar la imagen
   kubectl set image deployment/tavira-app \
     php-fpm=ingmontoyav/tavira-app:v20251107-<sha> \
     copy-app=ingmontoyav/tavira-app:v20251107-<sha>

   # Esperar a que termine el rollout
   kubectl rollout status deployment/tavira-app

   # Verificar que los pods estén corriendo
   kubectl get pods -l app=tavira
   ```

5. **Post-deployment**:
   ```bash
   # Limpiar caché
   kubectl exec deployment/tavira-app -- php artisan config:clear
   kubectl exec deployment/tavira-app -- php artisan route:clear
   kubectl exec deployment/tavira-app -- php artisan view:clear

   # Ejecutar migraciones (si es necesario)
   kubectl exec deployment/tavira-app -- php artisan migrate --force

   # Migraciones de tenants
   kubectl exec deployment/tavira-app -- php artisan tenants:migrate --force

   # Ver logs
   kubectl logs -l app=tavira -c php-fpm --tail=100 -f
   ```

### Comandos Producción

```bash
# Cambiar al contexto de producción
kubectl config use-context default

# Ver estado completo
kubectl get all -l app=tavira

# Escalar replicas (si es necesario)
kubectl scale deployment/tavira-app --replicas=3

# Rollback a versión anterior
kubectl rollout undo deployment/tavira-app

# Ver historial de deployments
kubectl rollout history deployment/tavira-app

# Reiniciar deployment (sin cambiar imagen)
kubectl rollout restart deployment/tavira-app

# Ver recursos (CPU/Memoria)
kubectl top pod -l app=tavira
```

## 🔧 Troubleshooting

### Problema: Pods en ImagePullBackOff
```bash
# Verificar la imagen configurada
kubectl describe pod <pod-name> | grep Image:

# Verificar que la imagen existe en Docker Hub
docker pull ingmontoyav/tavira-app:v20251107-<sha>

# Corregir la imagen
kubectl set image deployment/tavira-app php-fpm=ingmontoyav/tavira-app:<correct-tag>
```

### Problema: Pods no están Ready
```bash
# Ver eventos del pod
kubectl describe pod <pod-name>

# Ver logs del contenedor que falla
kubectl logs <pod-name> -c php-fpm
kubectl logs <pod-name> -c nginx

# Verificar probes
kubectl describe deployment tavira-app | grep -A 5 Liveness
kubectl describe deployment tavira-app | grep -A 5 Readiness
```

### Problema: Deployment escalado a 0
```bash
# Verificar replicas deseadas
kubectl get deployment tavira-app

# Escalar a 2 replicas
kubectl scale deployment/tavira-app --replicas=2

# Verificar que los pods se están creando
kubectl get pods -l app=tavira -w
```

### Problema: Cambios en .env no se reflejan
```bash
# Actualizar el secret
kubectl patch secret laravel-env --type='json' -p='[{
  "op": "add",
  "path": "/data/NEW_VAR",
  "value": "'"$(echo -n 'value' | base64)"'"
}]'

# Reiniciar deployment
kubectl rollout restart deployment/tavira-app

# Verificar variables dentro del pod
kubectl exec deployment/tavira-app -- env | grep NEW_VAR
```

## 📋 Checklist de Deployment

### Antes de Merge a Main
- [ ] Código revisado por al menos 1 persona
- [ ] Tests pasando en CI/CD
- [ ] Probado en staging
- [ ] Migraciones revisadas
- [ ] Variables de entorno nuevas documentadas
- [ ] Changelog actualizado

### Durante Deployment
- [ ] Verificar que la imagen se construyó correctamente
- [ ] Deployment completó sin errores
- [ ] Pods están en estado Running (2/2)
- [ ] Health checks pasando

### Después de Deployment
- [ ] Verificar que la aplicación carga correctamente
- [ ] Probar funcionalidad crítica
- [ ] Revisar logs por errores
- [ ] Monitorear métricas (CPU, memoria)
- [ ] Notificar al equipo del deployment exitoso

## 🔑 Contextos de Kubernetes

```bash
# Listar contextos disponibles
kubectl config get-contexts

# Cambiar de contexto
kubectl config use-context <context-name>

# Ver contexto actual
kubectl config current-context

# Contextos típicos:
# - default: Producción
# - staging: Staging
# - orbstack: Local (si usas Orbstack)
```

## 📚 Referencias

- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Laravel Documentation](https://laravel.com/docs)
- [Kubernetes Documentation](https://kubernetes.io/docs/home/)
- [Vite Documentation](https://vitejs.dev/)

## 🆘 Soporte

Si encuentras problemas:
1. Revisa esta documentación
2. Consulta los logs: `kubectl logs -l app=tavira -c php-fpm`
3. Revisa el canal de Slack del equipo
4. Contacta al equipo de DevOps
