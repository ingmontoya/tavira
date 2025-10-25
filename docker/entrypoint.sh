#!/bin/sh
set -e

# Asegurar que los directorios existen y tienen los permisos correctos
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Ajustar permisos (se ejecuta cada vez que el contenedor inicia)
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

# Ejecutar el comando especificado (php-fpm)
exec "$@"
