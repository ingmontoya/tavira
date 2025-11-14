# 🚀 Guía de Despliegue Local con Kubernetes/Docker - Tavira

**Guía completa paso a paso para desplegar la aplicación Tavira localmente usando Kubernetes (Minikube o Docker Desktop)**

---

## 📋 Tabla de Contenidos

1. [Requisitos Previos](#requisitos-previos)
2. [Preparación del Entorno](#preparación-del-entorno)
3. [Opción A: Despliegue con Docker Compose (Recomendado para Desarrollo)](#opción-a-despliegue-con-docker-compose)
4. [Opción B: Despliegue con Kubernetes Local](#opción-b-despliegue-con-kubernetes-local)
5. [Verificación del Despliegue](#verificación-del-despliegue)
6. [Comandos Útiles](#comandos-útiles)
7. [Troubleshooting](#troubleshooting)

---

## 📦 Requisitos Previos

### Software Necesario

Antes de comenzar, asegúrate de tener instalado:

#### **Opción A: Docker Compose (Más Simple)**
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (incluye Docker Engine y Docker Compose)
  - Mac: `brew install --cask docker`
  - Windows: Descargar instalador desde sitio oficial
  - Linux: `sudo apt install docker.io docker-compose`

#### **Opción B: Kubernetes Local (Más Realista)**
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) con Kubernetes habilitado, O
- [Minikube](https://minikube.sigs.k8s.io/docs/start/) + [kubectl](https://kubernetes.io/docs/tasks/tools/)
  - Mac: `brew install minikube kubectl`
  - Windows: `choco install minikube kubernetes-cli`
  - Linux: Ver instrucciones oficiales

#### Herramientas Adicionales
- `git` - Control de versiones
- `composer` - Gestor de dependencias PHP (para desarrollo local)
- `node` y `npm` - Para desarrollo frontend (opcional)

### Verificar Instalación

```bash
# Docker
docker --version
docker-compose --version

# Kubernetes (si aplica)
kubectl version --client
minikube version

# Verificar que Docker está corriendo
docker ps
```

### Recursos del Sistema Recomendados

- **CPU**: 4 cores mínimo (8 recomendado)
- **RAM**: 8GB mínimo (16GB recomendado)
- **Disco**: 20GB de espacio libre
- **OS**: macOS, Windows 10/11, Linux

---

## 🔧 Preparación del Entorno

### 1. Clonar el Repositorio

```bash
# Clonar el repositorio
git clone <repository-url> tavira
cd tavira

# Verificar que estás en la rama correcta
git branch
git status
```

### 2. Configurar Variables de Entorno

```bash
# Copiar el archivo de ejemplo (si existe)
cp .env.example .env

# Generar la clave de aplicación Laravel
php artisan key:generate
```

**Nota**: Si no tienes PHP instalado localmente, puedes usar Docker:

```bash
docker run --rm -v $(pwd):/app composer:2.7 bash -c "cd /app && php artisan key:generate"
```

### 3. Verificar Archivos Necesarios

```bash
# Verificar que existen los archivos de configuración
ls -la Dockerfile
ls -la Dockerfile.nginx
ls -la docker-compose.yml
ls -la docker-entrypoint.sh

# Verificar archivos de Kubernetes (para Opción B)
ls -la k8s/local/
```

---

## 🐳 Opción A: Despliegue con Docker Compose

**Recomendado para desarrollo local rápido y pruebas**

### Paso 1: Preparar la Configuración

```bash
# Asegurarte de estar en el directorio raíz del proyecto
cd /path/to/tavira

# Verificar el archivo docker-compose.yml
cat docker-compose.yml
```

### Paso 2: Construir las Imágenes

```bash
# Construir todas las imágenes necesarias
docker-compose build --no-cache

# Ver las imágenes creadas
docker images | grep tavira
```

**Salida esperada**:
```
tavira-app     latest    <image-id>   X minutes ago   XXX MB
tavira-nginx   latest    <image-id>   X minutes ago   XX MB
```

### Paso 3: Configurar el Archivo .env

Edita el archivo `.env` con las credenciales de la base de datos:

```env
APP_NAME=Tavira
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

# Genera esto con: php artisan key:generate
APP_KEY=base64:XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=tavira
DB_USERNAME=tavira_user
DB_PASSWORD=tavira_password

CACHE_DRIVER=redis
REDIS_HOST=redis
REDIS_PORT=6379

QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Tenancy Configuration
TENANCY_DATABASE_PREFIX=tenant
CENTRAL_DOMAINS=localhost,127.0.0.1
```

### Paso 4: Iniciar los Contenedores

```bash
# Iniciar todos los servicios en modo detached
docker-compose up -d

# Ver el estado de los contenedores
docker-compose ps
```

**Salida esperada**:
```
NAME               STATUS    PORTS
tavira-postgres    Up        0.0.0.0:5432->5432/tcp
tavira-redis       Up        0.0.0.0:6379->6379/tcp
tavira-app         Up        9000/tcp
tavira-nginx       Up        0.0.0.0:8081->80/tcp
tavira-queue       Up
```

**Nota**: Si el puerto 8080 está ocupado por otra aplicación, el puerto se ha cambiado a 8081.

### Paso 5: Ejecutar Migraciones

```bash
# Ejecutar migraciones de base de datos central
docker-compose exec app php artisan migrate --force

# Crear las tablas de cuentas contables
docker-compose exec app php artisan db:seed --class=ChartOfAccountsSeeder

# Si tienes tenants, ejecutar migraciones de tenants
docker-compose exec app php artisan tenants:migrate --force
```

### Paso 6: Crear Usuario Administrador (Opcional)

```bash
# Crear un usuario administrador
docker-compose exec app php artisan tinker

# Dentro de tinker, ejecutar:
# User::create([
#     'name' => 'Admin',
#     'email' => 'admin@tavira.com',
#     'password' => bcrypt('password123'),
# ]);
# exit
```

### Paso 7: Acceder a la Aplicación

Abre tu navegador y visita:

- **Aplicación**: http://localhost:8081
- **Health Check**: http://localhost:8081/health

**Nota**: El puerto por defecto es 8081 para evitar conflictos con otras aplicaciones.

### Paso 8: Ver Logs

```bash
# Ver logs de todos los contenedores
docker-compose logs -f

# Ver logs de un servicio específico
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f postgres
docker-compose logs -f queue
```

### Paso 9: Detener y Limpiar

```bash
# Detener todos los servicios
docker-compose down

# Detener y eliminar volúmenes (⚠️ ELIMINA DATOS)
docker-compose down -v

# Limpiar todo (imágenes, contenedores, volúmenes)
docker-compose down -v --rmi all
```

---

## ☸️ Opción B: Despliegue con Kubernetes Local

**Recomendado para simular un entorno de producción**

### Preparación: Elegir tu Entorno Kubernetes

#### Opción B.1: Docker Desktop con Kubernetes

```bash
# 1. Abrir Docker Desktop
# 2. Ir a Settings > Kubernetes
# 3. Marcar "Enable Kubernetes"
# 4. Click "Apply & Restart"

# Verificar que Kubernetes está funcionando
kubectl cluster-info
kubectl get nodes
```

#### Opción B.2: Minikube

```bash
# Iniciar Minikube con configuración personalizada
minikube start \
  --cpus=4 \
  --memory=8192 \
  --disk-size=20g \
  --driver=docker

# Verificar el estado
minikube status

# Configurar el contexto de kubectl
kubectl config use-context minikube

# Verificar
kubectl get nodes
```

### Paso 1: Construir las Imágenes Docker

```bash
# Si usas Minikube, configura Docker para usar el daemon de Minikube
eval $(minikube docker-env)

# Construir la imagen de la aplicación
docker build -t tavira-app:local -f Dockerfile .

# Construir la imagen de NGINX (opcional, se usa nginx:1.26-alpine oficial)
docker build -t tavira-nginx:local -f Dockerfile.nginx .

# Verificar las imágenes
docker images | grep tavira
```

**Salida esperada**:
```
tavira-app    local    <image-id>   X minutes ago   XXX MB
tavira-nginx  local    <image-id>   X minutes ago   XX MB
```

### Paso 2: Crear el Namespace

```bash
# Crear el namespace para desarrollo local
kubectl apply -f k8s/local/namespace.yaml

# Verificar
kubectl get namespaces
kubectl config set-context --current --namespace=local
```

### Paso 3: Configurar Secrets

```bash
# Generar la clave de aplicación Laravel (si no la tienes)
APP_KEY=$(docker run --rm tavira-app:local php artisan key:generate --show)
echo "APP_KEY=$APP_KEY"

# Codificar en base64 para Kubernetes
APP_KEY_BASE64=$(echo -n "$APP_KEY" | base64)

# Editar el archivo de secrets
cat > k8s/local/laravel-env-secret.yaml <<EOF
apiVersion: v1
kind: Secret
metadata:
  name: laravel-env-local
  namespace: local
type: Opaque
stringData:
  APP_KEY: "$APP_KEY"
  DB_DATABASE: tavira
  DB_USERNAME: tavira_user
  DB_PASSWORD: tavira_password
  CACHE_DRIVER: redis
  REDIS_HOST: redis-local
  REDIS_PORT: "6379"
  QUEUE_CONNECTION: redis
  SESSION_DRIVER: redis
EOF

# Aplicar el secret
kubectl apply -f k8s/local/laravel-env-secret.yaml

# Verificar
kubectl get secrets -n local
```

### Paso 4: Desplegar PostgreSQL

```bash
# Aplicar la configuración de PostgreSQL
kubectl apply -f k8s/local/postgres-config.yaml

# Aplicar el PVC para PostgreSQL
kubectl apply -f k8s/local/pvcs.yaml

# Desplegar PostgreSQL
kubectl apply -f k8s/local/postgres-deployment.yaml

# Aplicar el Service
kubectl apply -f k8s/local/postgres-service.yaml

# Verificar el estado
kubectl get pods -n local -l app=postgres-local
kubectl get pvc -n local

# Esperar a que esté listo (puede tomar 1-2 minutos)
kubectl wait --for=condition=ready pod -l app=postgres-local -n local --timeout=300s
```

### Paso 5: Desplegar Redis

```bash
# Desplegar Redis
kubectl apply -f k8s/local/redis-deployment.yaml

# Aplicar el Service
kubectl apply -f k8s/local/redis-service.yaml

# Verificar el estado
kubectl get pods -n local -l app=redis-local

# Esperar a que esté listo
kubectl wait --for=condition=ready pod -l app=redis-local -n local --timeout=120s
```

### Paso 6: Configurar NGINX

```bash
# Aplicar la configuración de NGINX
kubectl apply -f k8s/local/nginx-config.yaml

# Verificar
kubectl get configmap -n local
```

### Paso 7: Desplegar la Aplicación

```bash
# Aplicar el deployment de la aplicación (incluye PHP-FPM y NGINX)
kubectl apply -f k8s/local/deployment.yaml

# Aplicar el Service
kubectl apply -f k8s/local/service.yaml

# Verificar el estado
kubectl get pods -n local -l app=tavira-local
kubectl get svc -n local

# Esperar a que esté listo (puede tomar 2-3 minutos)
kubectl wait --for=condition=ready pod -l app=tavira-local -n local --timeout=300s
```

### Paso 8: Ejecutar Migraciones

```bash
# Obtener el nombre del pod
POD_NAME=$(kubectl get pod -n local -l app=tavira-local -o jsonpath="{.items[0].metadata.name}")

# Ejecutar migraciones de base de datos central
kubectl exec -n local $POD_NAME -c php-fpm -- php artisan migrate --force

# Crear las tablas de cuentas contables
kubectl exec -n local $POD_NAME -c php-fpm -- php artisan db:seed --class=ChartOfAccountsSeeder

# Si tienes tenants, ejecutar migraciones de tenants
kubectl exec -n local $POD_NAME -c php-fpm -- php artisan tenants:migrate --force
```

### Paso 9: Exponer la Aplicación

#### Opción 9a: Port Forward (Más Simple)

```bash
# Hacer port forward del servicio
kubectl port-forward -n local service/tavira-local 8080:80

# Mantener esta terminal abierta
# La aplicación estará disponible en: http://localhost:8080
```

#### Opción 9b: NodePort (Minikube)

```bash
# Si usas Minikube, obtener la URL del servicio
minikube service tavira-local -n local --url

# O usar el túnel de Minikube
minikube tunnel

# La aplicación estará disponible en el puerto especificado
```

#### Opción 9c: LoadBalancer (Docker Desktop)

El archivo `k8s/local/service.yaml` usa `type: NodePort`. Para acceder:

```bash
# Obtener el puerto asignado
kubectl get svc -n local tavira-local

# Acceder a través de:
# Docker Desktop: http://localhost:<NodePort>
# Minikube: minikube ip + <NodePort>
```

### Paso 10: Verificar el Despliegue

```bash
# Ver todos los recursos
kubectl get all -n local

# Ver el estado detallado de los pods
kubectl describe pod -n local -l app=tavira-local

# Ver los logs
kubectl logs -n local -l app=tavira-local -c php-fpm --tail=100 -f
kubectl logs -n local -l app=tavira-local -c nginx --tail=100 -f
```

### Paso 11: Crear Usuario Administrador

```bash
# Ejecutar tinker en el pod
kubectl exec -n local -it $POD_NAME -c php-fpm -- php artisan tinker

# Dentro de tinker:
# User::create([
#     'name' => 'Admin',
#     'email' => 'admin@tavira.com',
#     'password' => bcrypt('password123'),
# ]);
# exit
```

### Paso 12: Limpiar el Despliegue

```bash
# Eliminar todos los recursos del namespace
kubectl delete namespace local

# O eliminar recursos individualmente
kubectl delete -f k8s/local/deployment.yaml
kubectl delete -f k8s/local/service.yaml
kubectl delete -f k8s/local/postgres-deployment.yaml
kubectl delete -f k8s/local/postgres-service.yaml
kubectl delete -f k8s/local/redis-deployment.yaml
kubectl delete -f k8s/local/redis-service.yaml
kubectl delete -f k8s/local/pvcs.yaml
kubectl delete -f k8s/local/nginx-config.yaml
kubectl delete -f k8s/local/postgres-config.yaml
kubectl delete -f k8s/local/laravel-env-secret.yaml
kubectl delete -f k8s/local/namespace.yaml

# Si usas Minikube, detenerlo completamente
minikube stop
# O eliminarlo
minikube delete
```

---

## ✅ Verificación del Despliegue

### Verificaciones Básicas

```bash
# 1. Verificar que todos los pods están corriendo
kubectl get pods -n local

# Salida esperada (todos en Running):
# NAME                              READY   STATUS    RESTARTS
# postgres-local-xxxxx              1/1     Running   0
# redis-local-xxxxx                 1/1     Running   0
# tavira-app-local-xxxxx            2/2     Running   0

# 2. Verificar logs de la aplicación
kubectl logs -n local -l app=tavira-local -c php-fpm --tail=50

# 3. Verificar logs de NGINX
kubectl logs -n local -l app=tavira-local -c nginx --tail=50

# 4. Verificar conectividad a PostgreSQL
kubectl exec -n local $POD_NAME -c php-fpm -- php artisan db:show

# 5. Verificar conectividad a Redis
kubectl exec -n local $POD_NAME -c php-fpm -- php artisan tinker --execute="Redis::ping()"
```

### Health Checks

```bash
# Docker Compose
curl http://localhost:8080/health

# Kubernetes (con port-forward activo)
curl http://localhost:8080/health

# Salida esperada:
# {"status":"ok","timestamp":"2025-01-10T12:00:00Z"}
```

### Verificar Base de Datos

```bash
# Docker Compose
docker-compose exec postgres psql -U tavira_user -d tavira -c "\dt"

# Kubernetes
kubectl exec -n local -it $(kubectl get pod -n local -l app=postgres-local -o jsonpath="{.items[0].metadata.name}") -- psql -U tavira_user -d tavira -c "\dt"
```

### Verificar Redis

```bash
# Docker Compose
docker-compose exec redis redis-cli ping

# Kubernetes
kubectl exec -n local -it $(kubectl get pod -n local -l app=redis-local -o jsonpath="{.items[0].metadata.name}") -- redis-cli ping
```

---

## 🛠️ Comandos Útiles

### Docker Compose

```bash
# Reconstruir un servicio específico
docker-compose up -d --build app

# Reiniciar un servicio
docker-compose restart app

# Ver uso de recursos
docker stats

# Ejecutar comandos artisan
docker-compose exec app php artisan <command>

# Acceder al contenedor
docker-compose exec app sh
docker-compose exec postgres psql -U tavira_user -d tavira

# Limpiar logs
docker-compose logs --tail=0 -f
```

### Kubernetes

```bash
# Cambiar de contexto
kubectl config get-contexts
kubectl config use-context minikube

# Ver recursos
kubectl get all -n local
kubectl get events -n local --sort-by='.lastTimestamp'

# Describir un recurso
kubectl describe pod <pod-name> -n local
kubectl describe svc tavira-local -n local

# Ejecutar comandos en el pod
kubectl exec -n local $POD_NAME -c php-fpm -- php artisan <command>

# Copiar archivos desde/hacia el pod
kubectl cp local/$POD_NAME:/var/www/html/storage/logs/laravel.log ./laravel.log -c php-fpm
kubectl cp ./file.txt local/$POD_NAME:/tmp/file.txt -c php-fpm

# Escalar el deployment
kubectl scale deployment tavira-app-local -n local --replicas=2

# Ver uso de recursos
kubectl top pods -n local
kubectl top nodes

# Acceder al dashboard de Kubernetes (si está instalado)
minikube dashboard
```

---

## 🔧 Troubleshooting

### Problema 1: Imágenes no se pueden construir

**Síntoma**:
```
Error response from daemon: Cannot connect to the Docker daemon
```

**Solución**:
```bash
# Verificar que Docker está corriendo
docker ps

# Iniciar Docker Desktop (Mac/Windows)
open -a Docker

# Iniciar Docker daemon (Linux)
sudo systemctl start docker

# Verificar el estado
docker info
```

### Problema 2: Puerto ya en uso

**Síntoma**:
```
Error starting userland proxy: listen tcp 0.0.0.0:8080: bind: address already in use
```
O ves una aplicación diferente cuando accedes a `http://localhost:8080`

**Solución**:
```bash
# Encontrar el proceso usando el puerto
lsof -i :8080
ps aux | grep 8080

# Opción 1: Matar el proceso (si no lo necesitas)
kill -9 <PID>

# Opción 2: Cambiar el puerto en docker-compose.yml (RECOMENDADO)
# Editar la línea del servicio nginx:
ports:
  - "8081:80"  # Cambiar a 8081 u otro puerto disponible

# Reiniciar los contenedores
docker-compose down
docker-compose up -d
```

**IMPORTANTE**: El archivo `docker-compose.yml` ya está configurado con el puerto 8081 por defecto para evitar conflictos.

### Problema 3: Pod en estado CrashLoopBackOff

**Síntoma**:
```bash
kubectl get pods -n local
# NAME                              READY   STATUS             RESTARTS
# tavira-app-local-xxxxx            0/2     CrashLoopBackOff   5
```

**Solución**:
```bash
# Ver los logs del pod
kubectl logs -n local <pod-name> -c php-fpm
kubectl logs -n local <pod-name> -c nginx

# Ver eventos
kubectl describe pod -n local <pod-name>

# Causas comunes:
# 1. APP_KEY no configurado
# 2. Base de datos no accesible
# 3. Permisos de storage
# 4. Configuración incorrecta

# Verificar secrets
kubectl get secret laravel-env-local -n local -o yaml

# Verificar conectividad a base de datos
kubectl exec -n local <pod-name> -c php-fpm -- nc -zv postgres-local 5432
```

### Problema 4: Base de datos no accesible

**Síntoma**:
```
SQLSTATE[08006] [7] could not connect to server
```

**Solución**:
```bash
# Verificar que PostgreSQL está corriendo
kubectl get pods -n local -l app=postgres-local
docker-compose ps postgres

# Verificar el servicio
kubectl get svc -n local postgres-local
kubectl describe svc -n local postgres-local

# Probar conexión desde el pod de la aplicación
kubectl exec -n local $POD_NAME -c php-fpm -- nc -zv postgres-local 5432

# Verificar logs de PostgreSQL
kubectl logs -n local -l app=postgres-local
docker-compose logs postgres

# Verificar configuración de red
kubectl get endpoints -n local
```

### Problema 5: Migraciones fallan

**Síntoma**:
```
Migration table not found
```

**Solución**:
```bash
# Limpiar caché de configuración
kubectl exec -n local $POD_NAME -c php-fpm -- php artisan config:clear
kubectl exec -n local $POD_NAME -c php-fpm -- php artisan cache:clear

# Verificar conexión a base de datos
kubectl exec -n local $POD_NAME -c php-fpm -- php artisan db:show

# Ejecutar migraciones con verbose
kubectl exec -n local $POD_NAME -c php-fpm -- php artisan migrate --force -vvv

# Si es necesario, recrear la base de datos
kubectl exec -n local -it $(kubectl get pod -n local -l app=postgres-local -o jsonpath="{.items[0].metadata.name}") -- psql -U tavira_user -d postgres -c "DROP DATABASE IF EXISTS tavira;"
kubectl exec -n local -it $(kubectl get pod -n local -l app=postgres-local -o jsonpath="{.items[0].metadata.name}") -- psql -U tavira_user -d postgres -c "CREATE DATABASE tavira;"
```

### Problema 6: Permisos de storage

**Síntoma**:
```
The stream or file "/var/www/html/storage/logs/laravel.log" could not be opened
```

**Solución**:
```bash
# Kubernetes: Verificar el PVC
kubectl get pvc -n local
kubectl describe pvc tavira-storage-pvc-local -n local

# Ejecutar fix de permisos en el pod
kubectl exec -n local $POD_NAME -c php-fpm -- sh -c "chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache"

# Docker Compose: Fix de permisos local
docker-compose exec app chmod -R 775 storage bootstrap/cache
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
```

### Problema 7: Assets no se cargan (404)

**Síntoma**: CSS/JS no se cargan, errores 404 en `/build/assets/...`

**Solución**:
```bash
# Verificar que los assets se construyeron
docker-compose exec app ls -la /var/www/html/public/build
kubectl exec -n local $POD_NAME -c php-fpm -- ls -la /var/www/html/public/build

# Si no existen, reconstruir la imagen
docker-compose build --no-cache app

# Para Kubernetes
eval $(minikube docker-env)  # Solo si usas Minikube
docker build -t tavira-app:local --no-cache .
kubectl delete pod -n local -l app=tavira-local  # Recrear pods
```

### Problema 8: Memoria insuficiente

**Síntoma**:
```
OOMKilled
```

**Solución**:
```bash
# Docker Compose: Aumentar límites en docker-compose.yml
services:
  app:
    deploy:
      resources:
        limits:
          memory: 1G

# Kubernetes: Aumentar en k8s/local/deployment.yaml
resources:
  limits:
    memory: 1Gi

# Aplicar cambios
kubectl apply -f k8s/local/deployment.yaml

# Verificar uso actual
kubectl top pods -n local
docker stats
```

### Problema 9: Minikube no inicia

**Síntoma**:
```
Exiting due to DRV_DOCKER_NOT_RUNNING
```

**Solución**:
```bash
# Verificar Docker
docker ps

# Limpiar Minikube
minikube delete
rm -rf ~/.minikube

# Reiniciar con configuración adecuada
minikube start --cpus=4 --memory=8192 --driver=docker

# Si persiste, probar otro driver
minikube start --driver=virtualbox
minikube start --driver=hyperkit  # Mac
```

### Problema 10: Logs no aparecen

**Solución**:
```bash
# Docker Compose: Verificar que los contenedores están corriendo
docker-compose ps

# Kubernetes: Verificar pods y contenedores
kubectl get pods -n local
kubectl describe pod -n local <pod-name>

# Ver logs de todos los contenedores en un pod
kubectl logs -n local <pod-name> --all-containers=true

# Seguir logs en tiempo real
kubectl logs -n local -l app=tavira-local -c php-fpm -f --tail=100

# Ver logs anteriores (si el pod se reinició)
kubectl logs -n local <pod-name> -c php-fpm --previous
```

---

## 📚 Referencias Adicionales

### Documentación del Proyecto

- [DEVOPS-SUMMARY.md](./DEVOPS-SUMMARY.md) - Resumen completo de la infraestructura
- [DEVOPS-TROUBLESHOOTING.md](./DEVOPS-TROUBLESHOOTING.md) - Guía detallada de troubleshooting
- [DEVOPS-QUICK-REFERENCE.md](./DEVOPS-QUICK-REFERENCE.md) - Referencia rápida de comandos
- [DEVOPS-CHECKLIST.md](./DEVOPS-CHECKLIST.md) - Checklist de despliegue
- [CLAUDE.md](./CLAUDE.md) - Información del proyecto

### Documentación Externa

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [Kubernetes Documentation](https://kubernetes.io/docs/)
- [Minikube Documentation](https://minikube.sigs.k8s.io/docs/)
- [kubectl Cheat Sheet](https://kubernetes.io/docs/reference/kubectl/cheatsheet/)
- [Laravel Deployment](https://laravel.com/docs/deployment)

---

## 🎯 Próximos Pasos

Después de tener el entorno local funcionando:

1. **Explorar la aplicación**: Navega por las diferentes secciones
2. **Crear un tenant de prueba**: Simula un conjunto residencial
3. **Probar las funcionalidades**: Finance, accounting, residents, etc.
4. **Desarrollar nuevas features**: Usa hot-reload para desarrollo rápido
5. **Preparar para staging**: Revisa [DEVOPS-CHECKLIST.md](./DEVOPS-CHECKLIST.md)

---

## 💡 Tips y Best Practices

### Para Desarrollo Diario

1. **Usa Docker Compose** para desarrollo rápido
2. **Usa hot-reload** con `npm run dev` fuera del contenedor
3. **Mantén los volúmenes** para no perder datos entre reinicios
4. **Revisa los logs** regularmente para detectar problemas temprano

### Para Simular Producción

1. **Usa Kubernetes local** para probar configuraciones de producción
2. **Prueba los health checks** para asegurar que funcionan
3. **Simula fallos** eliminando pods y verificando que se recuperan
4. **Monitorea recursos** para entender el consumo

### Optimización de Recursos

```bash
# Limpiar imágenes no usadas
docker system prune -a

# Limpiar volúmenes huérfanos
docker volume prune

# Ver espacio usado
docker system df

# Limitar recursos de Docker Desktop
# Preferences > Resources > ajustar CPU/Memory
```

---

## 📞 Soporte

Si encuentras problemas no cubiertos en esta guía:

1. Revisa [DEVOPS-TROUBLESHOOTING.md](./DEVOPS-TROUBLESHOOTING.md)
2. Verifica los logs detallados
3. Consulta la documentación oficial
4. Contacta al equipo de DevOps

---

**Mantenido por**: DevOps Team
**Última actualización**: 2025-01-10
**Versión**: 1.0.0
**Estado**: ✅ Completo y Listo para Uso

---

**¡Feliz desarrollo! 🚀**
