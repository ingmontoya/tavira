#!/bin/bash

# =============================================================================
# Script de Despliegue para Ambiente Staging
# =============================================================================
# Este script despliega la aplicación Tavira en el ambiente de staging
#
# Uso: ./deploy.sh [comando]
#
# Comandos:
#   all       - Despliega todo el ambiente staging (por defecto)
#   secrets   - Solo crea/actualiza los secrets
#   infra     - Solo despliega infraestructura (DB, Redis)
#   app       - Solo despliega la aplicación
#   ingress   - Solo configura el ingress
#   clean     - Elimina todo el ambiente staging
#   status    - Muestra el estado del ambiente
#   logs      - Muestra los logs de la aplicación
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
STAGING_DIR="k8s/staging"

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

wait_for_pod() {
    local label=$1
    local timeout=${2:-120}
    echo -e "${YELLOW}⏳ Esperando a que el pod esté listo...${NC}"
    kubectl wait --for=condition=ready pod -l "$label" -n "$NAMESPACE" --timeout="${timeout}s" || {
        print_error "Timeout esperando el pod con label $label"
        return 1
    }
    print_success "Pod listo"
}

# Función para crear secrets
create_secrets() {
    print_header "Creando Secrets para Staging"

    # Verificar si el secret ya existe
    if kubectl get secret laravel-env-staging -n "$NAMESPACE" &> /dev/null; then
        print_info "El secret 'laravel-env-staging' ya existe"
        read -p "¿Deseas recrearlo? (s/n): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Ss]$ ]]; then
            kubectl delete secret laravel-env-staging -n "$NAMESPACE"
            print_success "Secret anterior eliminado"
        else
            print_info "Manteniendo secret existente"
            return 0
        fi
    fi

    print_info "Por favor ingresa los valores para el secret:"

    read -sp "APP_KEY (presiona enter para generar uno nuevo): " APP_KEY
    echo
    if [ -z "$APP_KEY" ]; then
        APP_KEY=$(php artisan key:generate --show)
        print_info "APP_KEY generada: $APP_KEY"
    fi

    read -p "DB_PASSWORD: " -s DB_PASSWORD
    echo

    # Crear el secret
    kubectl create secret generic laravel-env-staging \
        --from-literal=APP_KEY="$APP_KEY" \
        --from-literal=DB_DATABASE="tavira_staging" \
        --from-literal=DB_USERNAME="tavira_user_staging" \
        --from-literal=DB_PASSWORD="$DB_PASSWORD" \
        --from-literal=CACHE_DRIVER="redis" \
        --from-literal=REDIS_HOST="redis-staging" \
        --from-literal=REDIS_PORT="6379" \
        --from-literal=QUEUE_CONNECTION="redis" \
        --from-literal=SESSION_DRIVER="redis" \
        -n "$NAMESPACE"

    print_success "Secret creado exitosamente"
}

# Función para desplegar infraestructura
deploy_infra() {
    print_header "Desplegando Infraestructura (PostgreSQL + Redis)"

    # ConfigMaps
    print_info "Aplicando ConfigMaps..."
    kubectl apply -f "$STAGING_DIR/postgres-config.yaml"
    kubectl apply -f "$STAGING_DIR/nginx-config.yaml"

    # PVCs
    print_info "Creando PVCs..."
    kubectl apply -f "$STAGING_DIR/pvcs.yaml"

    # PostgreSQL
    print_info "Desplegando PostgreSQL..."
    kubectl apply -f "$STAGING_DIR/postgres-deployment.yaml"
    kubectl apply -f "$STAGING_DIR/postgres-service.yaml"
    wait_for_pod "app=postgres-staging"

    # Redis
    print_info "Desplegando Redis..."
    kubectl apply -f "$STAGING_DIR/redis-deployment.yaml"
    kubectl apply -f "$STAGING_DIR/redis-service.yaml"
    wait_for_pod "app=redis-staging"

    print_success "Infraestructura desplegada"
}

# Función para desplegar aplicación
deploy_app() {
    print_header "Desplegando Aplicación"

    # App principal
    print_info "Desplegando aplicación principal..."
    kubectl apply -f "$STAGING_DIR/deployment.yaml"
    kubectl apply -f "$STAGING_DIR/service.yaml"
    wait_for_pod "app=tavira-staging" 180

    # Queue worker
    print_info "Desplegando queue worker..."
    kubectl apply -f "$STAGING_DIR/queue-worker-deployment.yaml"
    wait_for_pod "app=tavira-queue-worker-staging" 180

    print_success "Aplicación desplegada"
}

# Función para configurar ingress
deploy_ingress() {
    print_header "Configurando Ingress"

    kubectl apply -f "$STAGING_DIR/ingress.yaml"

    print_success "Ingress configurado"
    print_info "Esperando a que se genere el certificado SSL (puede tomar unos minutos)..."
}

# Función para ejecutar migraciones
run_migrations() {
    print_header "Ejecutando Migraciones"

    POD=$(kubectl get pods -l app=tavira-staging -n "$NAMESPACE" -o jsonpath='{.items[0].metadata.name}')

    if [ -z "$POD" ]; then
        print_error "No se encontró ningún pod de la aplicación"
        return 1
    fi

    print_info "Usando pod: $POD"

    # Limpiar y cachear configuración
    print_info "Limpiando cache de configuración..."
    kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php artisan config:clear

    print_info "Cacheando configuración..."
    kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php artisan config:cache

    # Migraciones centrales
    print_info "Ejecutando migraciones centrales..."
    kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php artisan migrate --force

    # Migraciones de tenants
    print_info "Ejecutando migraciones de tenants..."
    kubectl exec "$POD" -c php-fpm -n "$NAMESPACE" -- php artisan tenants:migrate --force

    print_success "Migraciones completadas"
}

# Función para mostrar estado
show_status() {
    print_header "Estado del Ambiente Staging"

    echo -e "\n${YELLOW}📊 Deployments:${NC}"
    kubectl get deployments -l environment=staging -n "$NAMESPACE"

    echo -e "\n${YELLOW}📋 Pods:${NC}"
    kubectl get pods -l environment=staging -n "$NAMESPACE"

    echo -e "\n${YELLOW}🔌 Services:${NC}"
    kubectl get services -l environment=staging -n "$NAMESPACE"

    echo -e "\n${YELLOW}🌐 Ingress:${NC}"
    kubectl get ingress tavira-ingress-staging -n "$NAMESPACE"

    echo -e "\n${YELLOW}💾 PVCs:${NC}"
    kubectl get pvc -l environment=staging -n "$NAMESPACE"
}

# Función para mostrar logs
show_logs() {
    print_header "Logs de la Aplicación Staging"

    POD=$(kubectl get pods -l app=tavira-staging -n "$NAMESPACE" -o jsonpath='{.items[0].metadata.name}')

    if [ -z "$POD" ]; then
        print_error "No se encontró ningún pod de la aplicación"
        return 1
    fi

    echo -e "${YELLOW}Mostrando logs del pod: $POD${NC}"
    echo -e "${YELLOW}Presiona Ctrl+C para salir${NC}\n"

    kubectl logs -f "$POD" -c php-fpm -n "$NAMESPACE" --tail=100
}

# Función para limpiar ambiente
clean_staging() {
    print_header "Eliminando Ambiente Staging"

    read -p "⚠️  ¿Estás seguro de que deseas eliminar TODO el ambiente staging? (s/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Ss]$ ]]; then
        print_info "Operación cancelada"
        return 0
    fi

    print_info "Eliminando deployments y services..."
    kubectl delete -f "$STAGING_DIR/deployment.yaml" --ignore-not-found=true
    kubectl delete -f "$STAGING_DIR/queue-worker-deployment.yaml" --ignore-not-found=true
    kubectl delete -f "$STAGING_DIR/service.yaml" --ignore-not-found=true
    kubectl delete -f "$STAGING_DIR/postgres-deployment.yaml" --ignore-not-found=true
    kubectl delete -f "$STAGING_DIR/postgres-service.yaml" --ignore-not-found=true
    kubectl delete -f "$STAGING_DIR/redis-deployment.yaml" --ignore-not-found=true
    kubectl delete -f "$STAGING_DIR/redis-service.yaml" --ignore-not-found=true
    kubectl delete -f "$STAGING_DIR/ingress.yaml" --ignore-not-found=true

    print_info "Eliminando ConfigMaps..."
    kubectl delete configmap tavira-nginx-config-staging -n "$NAMESPACE" --ignore-not-found=true
    kubectl delete configmap postgres-config-staging -n "$NAMESPACE" --ignore-not-found=true

    read -p "¿Deseas eliminar también los PVCs? (⚠️  esto borrará los datos) (s/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        kubectl delete -f "$STAGING_DIR/pvcs.yaml" --ignore-not-found=true
        print_success "PVCs eliminados"
    fi

    read -p "¿Deseas eliminar el secret? (s/n): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Ss]$ ]]; then
        kubectl delete secret laravel-env-staging -n "$NAMESPACE" --ignore-not-found=true
        print_success "Secret eliminado"
    fi

    print_success "Ambiente staging eliminado"
}

# Función principal
deploy_all() {
    print_header "Desplegando Ambiente Staging Completo"

    create_secrets
    deploy_infra
    deploy_app
    deploy_ingress

    sleep 5

    run_migrations

    print_success "¡Despliegue completo!"
    echo -e "\n${GREEN}🌐 Staging disponible en: https://staging.tavira.com.co${NC}\n"

    show_status
}

# Procesar comando
COMMAND=${1:-all}

case $COMMAND in
    all)
        deploy_all
        ;;
    secrets)
        create_secrets
        ;;
    infra)
        deploy_infra
        ;;
    app)
        deploy_app
        ;;
    ingress)
        deploy_ingress
        ;;
    migrate)
        run_migrations
        ;;
    status)
        show_status
        ;;
    logs)
        show_logs
        ;;
    clean)
        clean_staging
        ;;
    *)
        echo "Comando desconocido: $COMMAND"
        echo ""
        echo "Uso: $0 [comando]"
        echo ""
        echo "Comandos disponibles:"
        echo "  all       - Despliega todo el ambiente staging (por defecto)"
        echo "  secrets   - Solo crea/actualiza los secrets"
        echo "  infra     - Solo despliega infraestructura (DB, Redis)"
        echo "  app       - Solo despliega la aplicación"
        echo "  ingress   - Solo configura el ingress"
        echo "  migrate   - Solo ejecuta migraciones"
        echo "  status    - Muestra el estado del ambiente"
        echo "  logs      - Muestra los logs de la aplicación"
        echo "  clean     - Elimina todo el ambiente staging"
        exit 1
        ;;
esac
