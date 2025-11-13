# Desarrollo Local - Tavira

Esta guía describe cómo configurar y trabajar en el entorno de desarrollo local de Tavira sin necesidad de construir imágenes Docker con cada cambio.

## 🎯 Filosofía de Desarrollo Local

**NO construyas imágenes localmente**. El flujo de trabajo está diseñado para que:
- ✅ Cambios en código PHP/Vue/Blade se reflejan inmediatamente (hot reload)
- ✅ Solo necesitas `docker-compose up -d` una vez
- ✅ Vite HMR actualiza el navegador automáticamente
- ✅ Las imágenes solo se construyen en GitHub Actions al hacer push

## 🚀 Quick Start

```bash
# 1. Clonar el repositorio
git clone https://github.com/your-org/tavira.git
cd tavira

# 2. Copiar archivo de entorno
cp .env.example .env

# 3. Iniciar servicios (PostgreSQL, Redis, Nginx)
docker-compose up -d

# 4. Instalar dependencias (solo la primera vez o si cambian)
composer install
npm install

# 5. Generar clave de aplicación (solo la primera vez)
php artisan key:generate

# 6. Ejecutar migraciones
php artisan migrate

# 7. (Opcional) Seeders
php artisan db:seed

# 8. Iniciar servidor de desarrollo + Vite
composer dev
```

**¡Listo!** Abre tu navegador en http://localhost:8000

## 🏗️ Arquitectura del Entorno Local

```
┌──────────────────────────────────────────────────────────────┐
│                       Tu Máquina                              │
│                                                               │
│  ┌─────────────────┐    ┌──────────────────┐                │
│  │   Host (tú)     │    │  Docker Compose  │                │
│  │                 │    │                  │                │
│  │  - Editas       │    │  ┌────────────┐  │                │
│  │    código       │◄───┼──┤ PostgreSQL │  │                │
│  │  - Ejecutas     │    │  └────────────┘  │                │
│  │    Laravel      │    │                  │                │
│  │  - Vite dev     │    │  ┌────────────┐  │                │
│  │    server       │◄───┼──┤   Redis    │  │                │
│  │  - Tests        │    │  └────────────┘  │                │
│  │                 │    │                  │                │
│  │  localhost:8000 │    │  ┌────────────┐  │                │
│  │  localhost:5173 │◄───┼──┤   Nginx    │  │                │
│  └─────────────────┘    │  └────────────┘  │                │
│                         │                  │                │
│                         │  (Solo servicios,│                │
│                         │   NO la app PHP) │                │
│                         └──────────────────┘                │
└──────────────────────────────────────────────────────────────┘
```

**Diferencia clave con producción/staging**:
- En **local**: Laravel y Vite corren en tu máquina (host)
- En **producción/staging**: Todo corre en Kubernetes (contenedores)

## 📋 Configuración del `.env`

```env
# Application
APP_NAME=Tavira
APP_ENV=local
APP_DEBUG=true
APP_KEY=base64:VZA42TsHSSqP07JFDc6hAUWofHZoQYSeUeUzTqsiTDQ=
APP_URL=http://localhost:8000

# Database (se conecta al contenedor de PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=localhost  # O 127.0.0.1
DB_PORT=5432
DB_DATABASE=tavira
DB_USERNAME=tavira_user
DB_PASSWORD=tavira_password

# Cache & Queue (se conecta al contenedor de Redis)
CACHE_DRIVER=redis
REDIS_HOST=localhost
REDIS_PORT=6379

QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# Multitenancy
TENANCY_CENTRAL_DOMAINS=localhost,127.0.0.1,192.168.1.21

# Mail (Mailpit)
MAIL_MAILER=smtp
MAIL_HOST=localhost
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@tavira.local
MAIL_FROM_NAME="${APP_NAME}"

# Vite
VITE_APP_URL=http://localhost:8000
```

## 🛠️ Comandos de Desarrollo

### Iniciar Desarrollo

```bash
# Opción 1: Comando único (recomendado)
composer dev

# Opción 2: Manual (en terminales separadas)
php artisan serve          # Terminal 1: Laravel server
php artisan queue:work     # Terminal 2: Queue worker
php artisan pail           # Terminal 3: Logs
npm run dev                # Terminal 4: Vite dev server
```

### Servicios de Docker

```bash
# Iniciar servicios
docker-compose up -d

# Ver logs
docker-compose logs -f

# Detener servicios
docker-compose down

# Reiniciar servicios
docker-compose restart

# Ver estado
docker-compose ps

# Eliminar todo (incluyendo volúmenes)
docker-compose down -v
```

### Base de Datos

```bash
# Ejecutar migraciones
php artisan migrate

# Migración fresh (elimina y recrea)
php artisan migrate:fresh

# Migración fresh + seeds
php artisan migrate:fresh --seed

# Rollback
php artisan migrate:rollback

# Acceder a PostgreSQL
docker-compose exec postgres psql -U tavira_user -d tavira

# Backup de base de datos
docker-compose exec postgres pg_dump -U tavira_user tavira > backup.sql

# Restore de base de datos
docker-compose exec -T postgres psql -U tavira_user tavira < backup.sql
```

### Tenants (Multitenancy)

```bash
# Ejecutar migraciones de tenants
php artisan tenants:migrate

# Migración fresh de tenants
php artisan tenants:migrate-fresh

# Seed de tenants
php artisan tenants:seed

# Ejecutar comando para todos los tenants
php artisan tenants:run "cache:clear"

# Listar tenants
php artisan tenants:list
```

### Cache & Queue

```bash
# Limpiar caché
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Acceder a Redis CLI
docker-compose exec redis redis-cli

# Ver trabajos en la cola
php artisan queue:monitor

# Limpiar cola fallida
php artisan queue:flush
```

### Testing

```bash
# Tests PHP
composer test
./vendor/bin/pest

# Tests con cobertura
composer test:coverage

# Linting
./vendor/bin/pint         # PHP
npm run lint              # JavaScript/Vue

# Type checking
vue-tsc --noEmit

# Tests E2E (Playwright)
npm run test:e2e
npm run test:e2e:ui
```

## 🔥 Hot Reload

### ¿Qué se recarga automáticamente?

✅ **Archivos PHP**:
- Controladores
- Modelos
- Middleware
- Requests
- Services
- Config (después de `php artisan config:clear`)

✅ **Archivos Vue**:
- Componentes `.vue`
- TypeScript `.ts`
- CSS/Tailwind

✅ **Archivos Blade**:
- Vistas `.blade.php`
- Layouts

### ¿Qué NO se recarga automáticamente?

❌ **Dependencias**:
- `composer.json` → Ejecutar `composer install`
- `package.json` → Ejecutar `npm install`

❌ **Configuración**:
- `.env` → Reiniciar `php artisan serve`
- `config/*` → Ejecutar `php artisan config:clear`

❌ **Migraciones**:
- `database/migrations/*` → Ejecutar `php artisan migrate`

❌ **Routes**:
- `routes/*` → Ejecutar `php artisan route:clear` (o reiniciar servidor)

## 🐛 Troubleshooting

### Puerto 5432 (PostgreSQL) ya en uso
```bash
# Ver qué está usando el puerto
lsof -i :5432

# Detener PostgreSQL local si está corriendo
brew services stop postgresql
# O
sudo systemctl stop postgresql
```

### Puerto 6379 (Redis) ya en uso
```bash
# Ver qué está usando el puerto
lsof -i :6379

# Detener Redis local si está corriendo
brew services stop redis
# O
sudo systemctl stop redis
```

### Puerto 8000 (Laravel) ya en uso
```bash
# Usar otro puerto
php artisan serve --port=8001

# O detener el proceso que usa el puerto
lsof -ti:8000 | xargs kill -9
```

### Error: "SQLSTATE[08006] [7] connection to server"
```bash
# Verificar que PostgreSQL está corriendo
docker-compose ps postgres

# Verificar logs de PostgreSQL
docker-compose logs postgres

# Reiniciar PostgreSQL
docker-compose restart postgres
```

### Error: "Connection refused [tcp://localhost:6379]"
```bash
# Verificar que Redis está corriendo
docker-compose ps redis

# Verificar logs de Redis
docker-compose logs redis

# Reiniciar Redis
docker-compose restart redis
```

### Cambios en código no se reflejan

#### Para PHP:
```bash
# Limpiar caché
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Reiniciar servidor
# Ctrl+C y luego:
php artisan serve
```

#### Para Vue/Vite:
```bash
# Verificar que Vite está corriendo
# Deberías ver: VITE vX.X.X  ready in XXX ms

# Si no está corriendo:
npm run dev

# Limpiar caché de Vite
rm -rf node_modules/.vite
npm run dev
```

### Permisos de archivos (storage/logs)
```bash
# Dar permisos a storage y cache
chmod -R 775 storage bootstrap/cache

# Si usas SELinux
chcon -R -t httpd_sys_rw_content_t storage
```

### Error: "Class not found"
```bash
# Limpiar autoload
composer dump-autoload

# Reinstalar dependencias
rm -rf vendor
composer install
```

## 🔍 Herramientas de Desarrollo

### Laravel Debugbar
```bash
# Ya incluido en development
# Aparece automáticamente en el navegador
```

### Telescope (Opcional)
```bash
# Instalar
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate

# Acceder
http://localhost:8000/telescope
```

### Mailpit (Ver correos)
```bash
# Abrir en navegador
open http://localhost:8025

# O manualmente
http://localhost:8025
```

### Laravel Pail (Logs en tiempo real)
```bash
# Ver logs en tiempo real
php artisan pail

# Filtrar por nivel
php artisan pail --level=error
```

## 📊 Flujo de Trabajo Típico

1. **Mañana (primera vez)**:
   ```bash
   docker-compose up -d      # Iniciar servicios
   composer dev              # Iniciar Laravel + Vite
   ```

2. **Durante el día**:
   - Editas código en tu IDE favorito
   - Los cambios se reflejan automáticamente
   - Si cambias `.env`, reinicia `php artisan serve`
   - Si cambias dependencias, ejecuta `composer install` o `npm install`

3. **Noche (terminar)**:
   ```bash
   Ctrl+C                    # Detener composer dev
   docker-compose down       # Detener servicios (opcional)
   ```

4. **Commit y push**:
   ```bash
   ./vendor/bin/pint        # Formatear código
   npm run lint             # Lint JavaScript
   composer test            # Ejecutar tests
   git add .
   git commit -m "feat: nueva funcionalidad"
   git push origin develop  # Push a develop (staging)
   ```

## 🎨 Mejores Prácticas

1. **No construyas imágenes localmente**: Usa `composer dev` directamente
2. **Mantén servicios corriendo**: No necesitas `docker-compose down` después de cada sesión
3. **Usa Pint antes de commit**: Código formateado automáticamente
4. **Tests antes de push**: Evita romper staging
5. **Limpiar caché si algo falla**: `php artisan config:clear`
6. **Usa branches**: No trabajes directo en `develop`

## 🔗 Siguientes Pasos

- Ver [DEVELOPMENT-WORKFLOW.md](./DEVELOPMENT-WORKFLOW.md) para el flujo completo (local → staging → producción)
- Ver [k8s/deployed/README.md](./k8s/deployed/README.md) para deployment en producción
- Ver [k8s/staging/README.md](./k8s/staging/README.md) para deployment en staging
