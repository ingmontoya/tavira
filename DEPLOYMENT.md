# Guía de Despliegue - Tavira App

Esta guía explica cómo desplegar Tavira usando Docker y Kubernetes con arquitectura optimizada.

## 📋 Tabla de Contenidos

1. [Arquitectura](#arquitectura)
2. [Requisitos Previos](#requisitos-previos)
3. [Testing Local con Docker Compose](#testing-local-con-docker-compose)
4. [Build y Push de Imágenes](#build-y-push-de-imágenes)
5. [Despliegue en Kubernetes](#despliegue-en-kubernetes)
6. [Troubleshooting](#troubleshooting)
7. [Optimizaciones Aplicadas](#optimizaciones-aplicadas)

---

## 🏗️ Arquitectura

### Arquitectura Sidecar (K8s)

```
┌─────────────────────────────────────┐
│          Kubernetes Pod             │
│                                     │
│  ┌──────────┐      ┌──────────┐   │
│  │  NGINX   │◄────►│ PHP-FPM  │   │
│  │  :80     │      │  :9000   │   │
│  └────┬─────┘      └─────┬────┘   │
│       │                  │         │
│       └──────┬───────────┘         │
│              │                     │
│    ┌─────────▼────────┐            │
│    │ Shared Volume    │            │
│    │ /var/www/html    │            │
│    └──────────────────┘            │
└─────────────────────────────────────┘
```

### Beneficios

- ✅ NGINX sirve archivos estáticos directamente (CSS, JS, imágenes)
- ✅ PHP-FPM solo procesa requests PHP
- ✅ Comunicación via localhost (ultra rápida)
- ✅ Imágenes separadas y optimizadas (~250-350MB total)
- ✅ Escalamiento independiente de recursos

---

## 📦 Requisitos Previos

### Software Necesario

- Docker 24.0+
- Docker Compose 2.20+
- Kubernetes 1.28+ (para producción)
- kubectl configurado
- Acceso a Docker Hub o registry privado

### Verificar Instalación

```bash
docker --version
docker-compose --version
kubectl version --client
```

---

## 🧪 Testing Local con Docker Compose

### 1. Preparar Entorno

```bash
# Copiar variables de entorno
cp .env.example .env

# Editar .env con tus valores
nano .env
```

### 2. Configurar APP_KEY

```bash
# Generar APP_KEY
php artisan key:generate

# O manualmente
APP_KEY=base64:$(openssl rand -base64 32)
```

### 3. Levantar Servicios

```bash
# Build y start (primera vez)
docker-compose up -d --build

# Solo start (subsecuentes)
docker-compose up -d
```

### 4. Ejecutar Migraciones

```bash
# Migraciones central
docker-compose exec app php artisan migrate --force

# Migraciones tenant
docker-compose exec app php artisan tenants:migrate
```

### 5. Acceder a la Aplicación

```
http://localhost:8080
```

### 6. Ver Logs

```bash
# Todos los servicios
docker-compose logs -f

# Solo app
docker-compose logs -f app

# Solo nginx
docker-compose logs -f nginx
```

### 7. Detener Servicios

```bash
docker-compose down

# Eliminar volúmenes también
docker-compose down -v
```

---

## 🏗️ Build y Push de Imágenes

### 1. Build PHP-FPM Image

```bash
# Build
docker build -t ingmontoyav/tavira-app:latest .

# Build con tag específico
docker build -t ingmontoyav/tavira-app:v1.0.0 .

# Verificar tamaño
docker images | grep tavira-app
```

### 2. Build NGINX Image (Opcional - para K8s personalizado)

```bash
docker build -f Dockerfile.nginx -t ingmontoyav/tavira-nginx:latest .
```

### 3. Push a Docker Hub

```bash
# Login
docker login

# Push latest
docker push ingmontoyav/tavira-app:latest

# Push versión específica
docker push ingmontoyav/tavira-app:v1.0.0
```

### 4. Verificar en Docker Hub

```bash
docker pull ingmontoyav/tavira-app:latest
```

---

## ☸️ Despliegue en Kubernetes

### Arquitectura K8s

```
Internet
    ↓
Ingress (tavira.com.co)
    ↓
Service (tavira-service:80)
    ↓
Deployment (tavira-app)
    ├─ Pod 1: [nginx:80 + php-fpm:9000]
    └─ Pod 2: [nginx:80 + php-fpm:9000]
    ↓
PVC (tavira-storage-pvc)
```

### 1. Crear Namespace (Opcional)

```bash
kubectl create namespace tavira
kubectl config set-context --current --namespace=tavira
```

### 2. Crear Secrets

```bash
# Generar APP_KEY si no tienes uno
APP_KEY=$(php artisan key:generate --show)

# Crear secret
kubectl create secret generic laravel-env \
  --from-literal=APP_KEY="$APP_KEY" \
  --from-literal=DB_DATABASE="tavira_production" \
  --from-literal=DB_USERNAME="tavira_user" \
  --from-literal=DB_PASSWORD="your-secure-password"

# Verificar
kubectl get secrets
kubectl describe secret laravel-env
```

### 3. Aplicar ConfigMap (NGINX)

```bash
kubectl apply -f k8s/nginx-configmap.yaml

# Verificar
kubectl get configmap tavira-nginx-config
```

### 4. Aplicar Deployment

```bash
kubectl apply -f k8s/deployment-optimized.yaml

# Verificar
kubectl get deployments
kubectl get pods
kubectl describe pod <pod-name>
```

### 5. Aplicar Service

```bash
kubectl apply -f k8s/service-optimized.yaml

# Verificar
kubectl get services
kubectl describe service tavira-service
```

### 6. Aplicar Ingress

```bash
kubectl apply -f k8s/ingress.yaml

# Verificar
kubectl get ingress
kubectl describe ingress tavira-ingress
```

### 7. Verificar Despliegue

```bash
# Ver todos los recursos
kubectl get all

# Ver logs de un pod
kubectl logs <pod-name> -c php-fpm
kubectl logs <pod-name> -c nginx

# Ver logs en tiempo real
kubectl logs -f <pod-name> -c php-fpm

# Ejecutar comando en pod
kubectl exec -it <pod-name> -c php-fpm -- php artisan migrate:status
```

### 8. Ejecutar Migraciones

```bash
# Obtener nombre del pod
POD=$(kubectl get pods -l app=tavira -o jsonpath='{.items[0].metadata.name}')

# Ejecutar migraciones
kubectl exec -it $POD -c php-fpm -- php artisan migrate --force

# Ejecutar tenant migrations
kubectl exec -it $POD -c php-fpm -- php artisan tenants:migrate
```

### 9. Rolling Update

```bash
# Build nueva imagen
docker build -t ingmontoyav/tavira-app:v1.0.1 .
docker push ingmontoyav/tavira-app:v1.0.1

# Actualizar deployment
kubectl set image deployment/tavira-app \
  php-fpm=ingmontoyav/tavira-app:v1.0.1

# Ver progreso
kubectl rollout status deployment/tavira-app

# Rollback si es necesario
kubectl rollout undo deployment/tavira-app
```

---

## 🔧 Troubleshooting

### ERR_CONNECTION_REFUSED

**Causa**: Service apuntando al puerto incorrecto.

**Solución**:
```bash
# Verificar que el service apunta al puerto 80 (nginx)
kubectl get service tavira-service -o yaml | grep targetPort

# Debe mostrar:
# targetPort: http (80)
```

### Pods en CrashLoopBackOff

**Causa**: Error en la aplicación o falta de secrets.

**Diagnóstico**:
```bash
# Ver logs
kubectl logs <pod-name> -c php-fpm
kubectl logs <pod-name> -c nginx

# Ver eventos
kubectl describe pod <pod-name>

# Verificar secrets
kubectl get secrets
```

### NGINX 502 Bad Gateway

**Causa**: PHP-FPM no está respondiendo o configuración incorrecta.

**Diagnóstico**:
```bash
# Verificar que PHP-FPM está escuchando
kubectl exec -it <pod-name> -c php-fpm -- netstat -tuln | grep 9000

# Verificar logs de NGINX
kubectl logs <pod-name> -c nginx

# Verificar comunicación localhost
kubectl exec -it <pod-name> -c nginx -- wget -O- http://127.0.0.1:9000
```

### Imagen muy pesada

**Solución**:
```bash
# Verificar que .dockerignore está correcto
cat .dockerignore

# Rebuild con --no-cache
docker build --no-cache -t ingmontoyav/tavira-app:latest .

# Analizar capas
docker history ingmontoyav/tavira-app:latest
```

### PVC no se monta

**Causa**: StorageClass no disponible o mal configurado.

**Solución**:
```bash
# Listar storage classes
kubectl get storageclass

# Actualizar deployment-optimized.yaml con storageClassName correcto
# O usar hostPath para testing:

# En deployment-optimized.yaml, reemplazar:
volumes:
  - name: app-storage
    hostPath:
      path: /data/tavira
      type: DirectoryOrCreate
```

---

## 🚀 Optimizaciones Aplicadas

### Dockerfile

1. **Multi-stage build**: 3 etapas (vendor, frontend, production)
2. **Alpine Linux**: Imágenes base ~5MB vs ~200MB
3. **Layer caching**: Composer/npm install antes de COPY código
4. **Limpieza de caches**: `composer clear-cache`, `npm cache clean`
5. **Solo runtime deps**: No build tools en imagen final
6. **PostgreSQL en vez de MySQL**: Según configuración del deployment

### .dockerignore

1. Excluye ~40 patrones innecesarios
2. No copia tests, docs, .git (ahorro ~100MB)
3. Excluye node_modules y vendor (se regeneran)

### Kubernetes

1. **Sidecar pattern**: NGINX + PHP-FPM en el mismo pod
2. **Health checks**: Liveness y readiness probes
3. **Resource limits**: CPU y memoria controlados
4. **Security context**: No root, read-only cuando posible
5. **Init containers**: Setup de storage y permisos
6. **Rolling updates**: maxSurge: 1, maxUnavailable: 0

### Reducción de Tamaño

```
Antes:  ~1.2GB (imagen monolítica)
Después: ~350MB (imagen optimizada)
Ahorro:  ~70%
```

---

## 📊 Métricas de Performance

### Build Time

```bash
# Medir tiempo de build
time docker build -t ingmontoyav/tavira-app:latest .

# Esperado: ~3-5 minutos (primera vez)
# Esperado: ~30-60 segundos (con cache)
```

### Image Size

```bash
# Ver tamaño
docker images | grep tavira

# Esperado:
# tavira-app:latest    ~250-350MB
# tavira-nginx:latest  ~20-30MB
```

### Startup Time

```bash
# Medir tiempo de startup
kubectl get pods -w

# Esperado: ~30-40 segundos hasta READY
```

---

## 🔐 Security Checklist

- [ ] Secrets en Kubernetes (no hardcodeados)
- [ ] HTTPS habilitado (Let's Encrypt)
- [ ] Security headers configurados (NGINX)
- [ ] Non-root user en containers
- [ ] Read-only filesystem donde posible
- [ ] Network policies configuradas
- [ ] Resource limits aplicados
- [ ] Vulnerability scanning habilitado

---

## 📚 Referencias

- [Docker Multi-stage builds](https://docs.docker.com/build/building/multi-stage/)
- [Kubernetes Best Practices](https://kubernetes.io/docs/concepts/configuration/overview/)
- [Laravel Deployment](https://laravel.com/docs/deployment)
- [NGINX + PHP-FPM](https://www.nginx.com/resources/wiki/start/topics/examples/phpfcgi/)

---

## 🆘 Soporte

Si encuentras problemas:

1. Revisa la sección [Troubleshooting](#troubleshooting)
2. Verifica logs: `kubectl logs <pod-name> -c php-fpm`
3. Revisa eventos: `kubectl describe pod <pod-name>`
4. Contacta al equipo de DevOps

---

**Última actualización**: 2025-10-25
**Versión**: 1.0.0
