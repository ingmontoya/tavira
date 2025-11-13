# Guía de Ambientes - Tavira

Esta guía rápida te ayudará a entender los diferentes ambientes y cómo trabajar con ellos.

## 🎯 Resumen Rápido

```
┌─────────────────────────────────────────────────────────────────┐
│  DESARROLLO LOCAL (Tu Computadora)                              │
│  • docker-compose up -d                                          │
│  • composer dev                                                  │
│  • Hot reload automático ✅                                      │
│  • NO construir imágenes ✅                                      │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ git push origin develop
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  STAGING (Kubernetes)                                            │
│  • Deploy automático con GitHub Actions                          │
│  • Testing antes de producción                                   │
│  • URL: https://staging.tavira.com.co                            │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ Pull Request → Review → Merge
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  PRODUCCIÓN (Kubernetes)                                         │
│  • Deploy manual después de merge                                │
│  • Alta disponibilidad (2+ replicas)                             │
│  • URL: https://tavira.com.co                                    │
└─────────────────────────────────────────────────────────────────┘
```

## 🖥️ Desarrollo Local

### Setup Inicial (Una sola vez)

```bash
# 1. Clonar
git clone https://github.com/your-org/tavira.git
cd tavira

# 2. Configurar
cp .env.example .env

# 3. Iniciar servicios
docker-compose up -d

# 4. Instalar dependencias
composer install
npm install

# 5. Setup de Laravel
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### Trabajo Diario

```bash
# 1. Levantar servicios (si no están corriendo)
docker-compose up -d

# 2. Iniciar desarrollo
composer dev

# 3. Trabajar en el código
# Los cambios se reflejan automáticamente ✅

# 4. Cuando termines
Ctrl+C  # Detener composer dev
# docker-compose down  # Opcional: detener servicios
```

### URLs Locales

- **Aplicación**: http://localhost:8000
- **Vite HMR**: http://localhost:5173
- **Mailpit**: http://localhost:8025
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379

### ¿Cuándo NO construir imágenes?

**NUNCA construyas imágenes localmente** para desarrollo diario:

❌ **NO hagas**:
```bash
docker build -t tavira .           # NO
docker-compose build               # NO
docker-compose up --build          # NO
```

✅ **SÍ haz**:
```bash
docker-compose up -d               # Solo servicios
composer dev                       # Laravel + Vite en host
```

**Razón**: El código está montado en volúmenes. Cambios en `.php`, `.vue`, `.ts` se ven inmediatamente sin rebuild.

### ¿Cuándo SÍ reconstruir?

Solo cuando cambies:
- `composer.json` → `composer install`
- `package.json` → `npm install`
- `Dockerfile` → `docker-compose build` (raro)
- `docker-compose.yml` → `docker-compose up -d`

## 🧪 Staging

### ¿Qué es?

Ambiente de testing idéntico a producción pero con recursos mínimos.

### Deploy Automático

```bash
# 1. Hacer cambios en local
# ... editar código ...

# 2. Commit y push a develop
git add .
git commit -m "feat: nueva funcionalidad"
git push origin develop

# 3. GitHub Actions despliega automáticamente
# Ver en: https://github.com/your-org/tavira/actions
```

### Verificar Staging

```bash
# Ver estado de pods
kubectl config use-context staging
kubectl get pods -l app=tavira-staging

# Ver logs
kubectl logs -l app=tavira-staging -c php-fpm -f

# Ejecutar comandos
POD=$(kubectl get pods -l app=tavira-staging -o jsonpath='{.items[0].metadata.name}')
kubectl exec $POD -c php-fpm -- php artisan tinker
```

### Testing en Staging

1. **Abrir URL**: https://staging.tavira.com.co
2. **Probar funcionalidad nueva**
3. **Revisar logs** por errores
4. **Ejecutar tests E2E** (opcional)

Si todo funciona → Crear PR a `main`

## 🚀 Producción

### Deploy Manual

```bash
# 1. Crear PR de develop a main
gh pr create --base main --head develop --title "Release vX.Y.Z"

# 2. Code Review
# - Revisión por el equipo
# - Aprobación

# 3. Merge PR
# GitHub Actions construye imagen automáticamente

# 4. Deploy manual
kubectl config use-context default  # Contexto de producción

# Actualizar imagen
kubectl set image deployment/tavira-app \
  php-fpm=ingmontoyav/tavira-app:v20251107-<sha> \
  copy-app=ingmontoyav/tavira-app:v20251107-<sha>

# Esperar rollout
kubectl rollout status deployment/tavira-app

# Verificar pods
kubectl get pods -l app=tavira
```

### Post-Deploy

```bash
# 1. Limpiar caché
kubectl exec deployment/tavira-app -- php artisan config:clear
kubectl exec deployment/tavira-app -- php artisan route:clear

# 2. Verificar aplicación
curl -I https://tavira.com.co

# 3. Monitorear logs
kubectl logs -l app=tavira -c php-fpm -f

# 4. Verificar métricas
kubectl top pod -l app=tavira
```

### Rollback (si es necesario)

```bash
# Ver historial
kubectl rollout history deployment/tavira-app

# Rollback a versión anterior
kubectl rollout undo deployment/tavira-app

# O a versión específica
kubectl rollout undo deployment/tavira-app --to-revision=2
```

## 📊 Comparación de Ambientes

| | Local | Staging | Producción |
|---|---|---|---|
| **Herramienta** | docker-compose | Kubernetes | Kubernetes |
| **Inicio** | `composer dev` | `git push develop` | Merge PR + deploy |
| **Hot Reload** | ✅ Sí | ❌ No | ❌ No |
| **Build Imagen** | ❌ No | ✅ GitHub Actions | ✅ GitHub Actions |
| **Replicas** | 1 | 1 | 2+ |
| **Debug** | true | true | false |
| **Base de Datos** | Local | K8s staging | K8s producción |
| **URL** | localhost:8000 | staging.tavira.com.co | tavira.com.co |

## 🔑 Comandos Esenciales

### Local

```bash
# Iniciar
docker-compose up -d && composer dev

# Detener
Ctrl+C && docker-compose down

# Logs
docker-compose logs -f

# Base de datos
docker-compose exec postgres psql -U tavira_user -d tavira
```

### Staging

```bash
# Ver estado
kubectl get pods -l app=tavira-staging

# Logs
kubectl logs -l app=tavira-staging -c php-fpm -f

# Ejecutar comando
kubectl exec deployment/tavira-app-staging -- php artisan migrate
```

### Producción

```bash
# Ver estado
kubectl get pods -l app=tavira

# Logs
kubectl logs -l app=tavira -c php-fpm -f

# Ejecutar comando
kubectl exec deployment/tavira-app -- php artisan migrate --force
```

## 🎓 Flujo de Trabajo Típico

### Día 1: Nueva Feature

```bash
# Local
git checkout -b feature/nueva-funcionalidad
# ... desarrollar ...
docker-compose up -d
composer dev
# ... probar cambios ...
git commit -am "feat: nueva funcionalidad"
git push origin feature/nueva-funcionalidad
```

### Día 2: Testing en Staging

```bash
# Merge feature a develop
git checkout develop
git merge feature/nueva-funcionalidad
git push origin develop

# GitHub Actions despliega automáticamente a staging
# Probar en: https://staging.tavira.com.co
```

### Día 3: Deploy a Producción

```bash
# Crear PR
gh pr create --base main --head develop

# Después de aprobación y merge
kubectl config use-context default
kubectl set image deployment/tavira-app php-fpm=ingmontoyav/tavira-app:v20251107-<sha>
kubectl rollout status deployment/tavira-app

# Verificar
curl -I https://tavira.com.co
kubectl logs -l app=tavira -c php-fpm --tail=50
```

## 🐛 Problemas Comunes

### "Puerto 5432 ya en uso"

```bash
# Detener PostgreSQL local
brew services stop postgresql
# O
sudo systemctl stop postgresql
```

### "Changes not reflecting"

**Local**:
```bash
# Limpiar caché
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

**Staging/Producción**:
```bash
kubectl exec deployment/tavira-app -- php artisan config:clear
kubectl rollout restart deployment/tavira-app
```

### "ImagePullBackOff" en k8s

```bash
# Ver imagen configurada
kubectl describe pod <pod-name> | grep Image:

# Verificar imagen
docker pull ingmontoyav/tavira-app:<tag>

# Corregir
kubectl set image deployment/tavira-app php-fpm=ingmontoyav/tavira-app:<correct-tag>
```

### Deployment escalado a 0

```bash
# Verificar
kubectl get deployment tavira-app

# Escalar
kubectl scale deployment/tavira-app --replicas=2
```

## 📚 Documentación Completa

Para más detalles, consulta:

- **[LOCAL-DEVELOPMENT.md](./LOCAL-DEVELOPMENT.md)** - Guía completa de desarrollo local
- **[DEVELOPMENT-WORKFLOW.md](./DEVELOPMENT-WORKFLOW.md)** - Flujo de trabajo detallado
- **[k8s/README.md](./k8s/README.md)** - Documentación de Kubernetes
- **[k8s/staging/README.md](./k8s/staging/README.md)** - Staging específico
- **[k8s/deployed/README.md](./k8s/deployed/README.md)** - Producción específica

## ✅ Checklist de Deploy

### Antes de Merge a Main

- [ ] Probado en local
- [ ] Tests pasando
- [ ] Probado en staging
- [ ] Code review aprobado
- [ ] Migraciones revisadas
- [ ] Changelog actualizado

### Durante Deploy

- [ ] Imagen construida correctamente
- [ ] Deployment completó sin errores
- [ ] Pods en estado Running
- [ ] Health checks pasando

### Después de Deploy

- [ ] Aplicación carga correctamente
- [ ] Funcionalidad crítica funciona
- [ ] Logs sin errores críticos
- [ ] Métricas normales

---

**¿Dudas?** Revisa la documentación detallada o contacta al equipo.
