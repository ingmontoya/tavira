# Docker Compose Hot Reload - Guía de Uso 🔥

## Problema Resuelto

**Antes**: Necesitabas hacer `docker-compose build` cada vez que cambias código
**Ahora**: Editas código → Refrescas navegador → Ves cambios inmediatamente

## ¿Qué se cambió?

Se agregó `docker-compose.override.yml` que:
1. Monta tu código local en los contenedores
2. Usa el target `orbstack` del Dockerfile (con dev dependencies)
3. Configura opcache para desarrollo (revalidación habilitada)

## Setup Inicial (Primera vez)

```bash
# 1. Rebuild con el nuevo target de desarrollo
docker-compose build

# 2. Levantar servicios
docker-compose up -d

# 3. Verificar que todo esté corriendo
docker-compose ps

# 4. Acceder a la app
open http://localhost:8081
```

## Uso Diario (Hot Reload)

```bash
# Si ya tienes los contenedores corriendo:
# 1. Edita cualquier archivo:
#    - app/Models/Budget.php
#    - resources/js/Pages/Dashboard.vue
#    - routes/web.php
#    - config/app.php
#    - etc.

# 2. Refresca el navegador
#    ¡Los cambios ya están ahí! ✅

# NO necesitas:
# ❌ docker-compose build
# ❌ docker-compose restart
# ❌ docker-compose up -d
```

## Cuándo SÍ Necesitas Rebuild

Solo necesitas rebuild cuando cambies **dependencias** o **configuración de build**:

```bash
# Casos que requieren rebuild:
# ✅ Modificaste composer.json (agregaste/actualizaste paquetes)
# ✅ Modificaste package.json (agregaste/actualizaste paquetes npm)
# ✅ Modificaste Dockerfile
# ✅ Necesitas regenerar assets (npm run build)

# Para rebuild:
docker-compose down
docker-compose build
docker-compose up -d
```

## Arquitectura

```
┌─────────────────────────────────────────────────┐
│  Tu Mac                                         │
│  /Users/mauricio/repos/tavira/                  │
│                                                 │
│  ┌────────────────────────────────────────────┐ │
│  │  Editor (VS Code, PhpStorm, etc)           │ │
│  │  Editas: app/Models/Budget.php             │ │
│  └──────────────────┬─────────────────────────┘ │
│                     │                           │
│                     │ Volume mount              │
│                     │ ./:/var/www/html          │
│                     ▼                           │
│  ┌────────────────────────────────────────────┐ │
│  │  Docker Container: tavira-app              │ │
│  │                                            │ │
│  │  /var/www/html ──► Tu código local        │ │
│  │  Opcache: validate_timestamps=1           │ │
│  │  Opcache: revalidate_freq=0               │ │
│  │  ➜ Detecta cambios en cada request        │ │
│  └────────────────────────────────────────────┘ │
│                     │                           │
│                     │ http://localhost:8081     │
│                     ▼                           │
│  ┌────────────────────────────────────────────┐ │
│  │  Browser - Refresh y ve cambios           │ │
│  └────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘
```

## Archivos Montados

### Con Hot Reload ✅
Estos archivos se leen directamente de tu filesystem:

- `app/` - Controllers, Models, Services, etc.
- `resources/` - Views, JS, CSS, Vue components
- `routes/` - web.php, api.php, tenant.php
- `config/` - Archivos de configuración
- `database/` - Migrations, Seeders, Factories
- `lang/` - Traducciones
- `public/` - Assets públicos (index.php, etc)

### Excluidos del Mount ⚠️
Estos usan las versiones del contenedor:

- `vendor/` - Dependencias de Composer (del build)
- `node_modules/` - Dependencias de NPM (del build)

**Razón**: Son pesados y pueden tener binarios compilados específicos del contenedor.

## Comandos Útiles

```bash
# Ver logs en tiempo real
docker-compose logs -f app

# Entrar al contenedor
docker-compose exec app bash

# Ejecutar comandos artisan
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed
docker-compose exec app php artisan cache:clear

# Reiniciar solo un servicio
docker-compose restart app

# Ver estado de servicios
docker-compose ps

# Detener todo
docker-compose down

# Detener y limpiar volúmenes
docker-compose down -v
```

## Opcache en Desarrollo

El `docker-compose.override.yml` configura opcache así:

```yaml
PHP_OPCACHE_VALIDATE_TIMESTAMPS: "1"  # Revisa cambios en archivos
PHP_OPCACHE_REVALIDATE_FREQ: "0"      # Revisa en cada request
PHP_OPCACHE_ENABLE: "1"               # Mantén cache activo (performance)
```

**Balance perfecto**: Performance + Hot Reload

### Si quieres máxima velocidad de reload (más lento en ejecución)

Edita `docker-compose.override.yml`:

```yaml
environment:
  PHP_OPCACHE_ENABLE: "0"  # Deshabilita opcache completamente
```

## Frontend Hot Module Replacement (HMR)

Para desarrollo frontend con Vite HMR:

```bash
# Opción 1: Vite en tu Mac (más simple)
npm run dev
# Accede a: http://localhost:5173

# Opción 2: Vite en container (opcional)
docker-compose --profile vite up -d
# Accede a: http://localhost:5173
```

Usa Vite para frontend, Docker Compose para backend.

## Comparación: Antes vs Ahora

| Operación | Antes | Ahora |
|-----------|-------|-------|
| **Cambio en PHP** | Build (2-3 min) + Restart | Refresco (< 1s) |
| **Cambio en Vue** | Build (2-3 min) + Restart | Refresco (< 1s) |
| **Cambio en Blade** | Build (2-3 min) + Restart | Refresco (< 1s) |
| **Cambio en Routes** | Build (2-3 min) + Restart | Refresco (< 1s) |
| **Cambio en Config** | Build + Restart | Refresco + `php artisan config:clear` |
| **composer.json** | Build (2-3 min) | Build (2-3 min) |
| **package.json** | Build (2-3 min) | Build (2-3 min) |

## Troubleshooting

### Cambios no se reflejan

```bash
# 1. Verificar que el volumen está montado
docker-compose exec app ls -la /var/www/html/app
# Debes ver tus archivos locales

# 2. Clear Laravel caches
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan view:clear

# 3. Verificar opcache
docker-compose exec app php -i | grep opcache.validate_timestamps
# Debe mostrar: opcache.validate_timestamps => On => On

# 4. Reiniciar contenedor
docker-compose restart app
```

### Permisos de archivos

```bash
# Si hay problemas con storage/logs
chmod -R 775 storage bootstrap/cache

# O desde el contenedor
docker-compose exec app chmod -R 775 storage bootstrap/cache
```

### Vendor/node_modules desactualizados

Si actualizaste dependencies y no funcionan:

```bash
# Rebuild completo
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## Diferencia con Producción

| Aspecto | Development (override) | Production (base) |
|---------|----------------------|-------------------|
| **Código** | Montado desde local | Copiado en build |
| **Dependencies** | Con --dev | Solo production |
| **Opcache** | Revalidate ON | Revalidate OFF |
| **Target** | orbstack | (default/production) |
| **Rebuild** | Solo para deps | Para cada cambio |

## Archivos Involucrados

1. **`docker-compose.yml`** - Configuración base (no modificado)
2. **`docker-compose.override.yml`** - Hot reload config (NUEVO)
3. **`Dockerfile`** - Agregado target `orbstack` (MODIFICADO)

## Próximos Pasos

1. ✅ Ya tienes `docker-compose.override.yml` creado
2. Ejecuta: `docker-compose down && docker-compose build && docker-compose up -d`
3. Edita un archivo PHP/Vue
4. Refresca el navegador
5. 🎉 Disfruta el hot reload!

## Comandos Rápidos

```bash
# Start con hot reload
docker-compose up -d

# Rebuild (solo cuando cambies dependencies)
docker-compose build

# Logs
docker-compose logs -f app

# Artisan
docker-compose exec app php artisan <command>

# Stop
docker-compose down
```

---

**¡Desarrolla sin esperas! 🚀🔥**
