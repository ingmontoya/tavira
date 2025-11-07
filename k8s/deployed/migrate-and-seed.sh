#!/bin/bash

# =============================================================================
# Script de Migraciones y Seeders para Producción
# =============================================================================
# Este script ejecuta migraciones y seeders en el ambiente de producción
#
# Uso: ./migrate-and-seed.sh
#
# IMPORTANTE: Este script debe ejecutarse manualmente después del despliegue
# =============================================================================

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración
NAMESPACE="default"
DEPLOYMENT_NAME="tavira-app"

# Funciones de utilidad
print_header() {
    echo -e "${BLUE}=============================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}=============================================${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_info() {
    echo -e "${YELLOW}ℹ️  $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Función principal
run_migrations_and_seeders() {
    print_header "Ejecutando Migraciones y Seeders en Producción"

    # Confirmación de seguridad
    print_warning "Estás a punto de ejecutar migraciones y seeders en PRODUCCIÓN"
    read -p "¿Estás seguro de continuar? (escribe 'SI CONFIRMO' para proceder): " confirmation

    if [ "$confirmation" != "SI CONFIRMO" ]; then
        print_error "Operación cancelada"
        exit 1
    fi

    # Obtener el pod
    POD=$(kubectl get pods -l app=tavira -n "$NAMESPACE" -o jsonpath='{.items[0].metadata.name}')

    if [ -z "$POD" ]; then
        print_error "No se encontró ningún pod de la aplicación"
        exit 1
    fi

    print_info "Usando pod: $POD"
    echo ""

    # Limpiar y cachear configuración
    print_info "1/6 Limpiando cache de configuración..."
    kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php artisan config:clear
    print_success "Cache limpiado"
    echo ""

    print_info "2/6 Cacheando configuración..."
    kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php artisan config:cache
    print_success "Configuración cacheada"
    echo ""

    # Migraciones centrales
    print_info "3/6 Ejecutando migraciones centrales..."
    kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php artisan migrate --force
    print_success "Migraciones centrales completadas"
    echo ""

    # Seeders centrales
    print_info "4/6 Ejecutando seeders centrales..."
    kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php artisan db:seed --force
    print_success "Seeders centrales completados"
    echo ""

    # Migraciones de tenants
    print_info "5/6 Ejecutando migraciones de tenants..."
    kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php artisan tenants:migrate --force
    print_success "Migraciones de tenants completadas"
    echo ""

    # Seeders de tenants
    print_info "6/6 Ejecutando seeders de tenants..."
    kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php artisan tenants:seed --force
    print_success "Seeders de tenants completados"
    echo ""

    print_success "¡Proceso completado exitosamente!"
    echo ""
    print_info "Verificando estado de la aplicación..."
    kubectl get pods -l app=tavira -n "$NAMESPACE"
}

# Ejecutar función principal
run_migrations_and_seeders
