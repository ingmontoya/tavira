#!/bin/bash

# Script para desplegar Queue Workers y Scheduler en Kubernetes
# Uso: ./deploy-queue-scheduler.sh [apply|delete|status]

set -e

NAMESPACE="default"
MANIFESTS=(
  "queue-worker-deployment.yaml"
  "queue-worker-hpa.yaml"
  "scheduler-cronjob.yaml"
)

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

function print_header() {
  echo -e "${BLUE}======================================${NC}"
  echo -e "${BLUE}$1${NC}"
  echo -e "${BLUE}======================================${NC}"
}

function print_success() {
  echo -e "${GREEN}✓ $1${NC}"
}

function print_error() {
  echo -e "${RED}✗ $1${NC}"
}

function print_warning() {
  echo -e "${YELLOW}⚠ $1${NC}"
}

function check_prerequisites() {
  print_header "Verificando Prerequisites"

  # Verificar kubectl
  if ! command -v kubectl &> /dev/null; then
    print_error "kubectl no está instalado"
    exit 1
  fi
  print_success "kubectl disponible"

  # Verificar conexión al cluster
  if ! kubectl cluster-info &> /dev/null; then
    print_error "No se puede conectar al cluster de Kubernetes"
    exit 1
  fi
  print_success "Conectado al cluster"

  # Verificar secret
  if ! kubectl get secret laravel-env -n $NAMESPACE &> /dev/null; then
    print_error "Secret 'laravel-env' no encontrado"
    exit 1
  fi
  print_success "Secret 'laravel-env' encontrado"

  # Verificar PVC
  if ! kubectl get pvc tavira-storage-pvc -n $NAMESPACE &> /dev/null; then
    print_warning "PVC 'tavira-storage-pvc' no encontrado (puede ser normal si usas emptyDir)"
  else
    print_success "PVC 'tavira-storage-pvc' encontrado"
  fi

  # Verificar Redis
  if ! kubectl get deployment redis -n $NAMESPACE &> /dev/null; then
    print_warning "Redis deployment no encontrado (asegúrate de que el sistema de colas funcione)"
  else
    print_success "Redis deployment encontrado"
  fi

  echo ""
}

function apply_manifests() {
  print_header "Aplicando Manifiestos"

  for manifest in "${MANIFESTS[@]}"; do
    echo -e "${BLUE}Aplicando: $manifest${NC}"
    if kubectl apply -f "$manifest" -n $NAMESPACE; then
      print_success "$manifest aplicado"
    else
      print_error "Error aplicando $manifest"
      exit 1
    fi
    echo ""
  done

  print_success "Todos los manifiestos aplicados correctamente"
  echo ""
}

function delete_manifests() {
  print_header "Eliminando Recursos"

  for manifest in "${MANIFESTS[@]}"; do
    echo -e "${BLUE}Eliminando: $manifest${NC}"
    if kubectl delete -f "$manifest" -n $NAMESPACE --ignore-not-found; then
      print_success "$manifest eliminado"
    else
      print_warning "Error eliminando $manifest (puede no existir)"
    fi
    echo ""
  done

  print_success "Recursos eliminados"
  echo ""
}

function show_status() {
  print_header "Estado de Queue Workers"

  echo -e "${YELLOW}Deployments:${NC}"
  kubectl get deployment tavira-queue-worker -n $NAMESPACE 2>/dev/null || print_warning "Queue worker deployment no encontrado"
  echo ""

  echo -e "${YELLOW}Pods:${NC}"
  kubectl get pods -l app=tavira-queue-worker -n $NAMESPACE 2>/dev/null || print_warning "No hay pods de queue workers"
  echo ""

  echo -e "${YELLOW}HPA:${NC}"
  kubectl get hpa tavira-queue-worker-hpa -n $NAMESPACE 2>/dev/null || print_warning "HPA no encontrado"
  echo ""

  print_header "Estado del Scheduler"

  echo -e "${YELLOW}CronJob:${NC}"
  kubectl get cronjob tavira-scheduler -n $NAMESPACE 2>/dev/null || print_warning "Scheduler cronjob no encontrado"
  echo ""

  echo -e "${YELLOW}Últimos Jobs:${NC}"
  kubectl get jobs -l app=tavira-scheduler -n $NAMESPACE --sort-by=.metadata.creationTimestamp 2>/dev/null || print_warning "No hay jobs del scheduler"
  echo ""
}

function show_logs() {
  print_header "Logs de Queue Workers"

  echo -e "${YELLOW}Últimos 50 líneas:${NC}"
  kubectl logs -l app=tavira-queue-worker -n $NAMESPACE --tail=50 2>/dev/null || print_warning "No hay logs disponibles"
  echo ""

  print_header "Logs del Scheduler"

  LAST_JOB=$(kubectl get jobs -l app=tavira-scheduler -n $NAMESPACE --sort-by=.metadata.creationTimestamp -o jsonpath='{.items[-1].metadata.name}' 2>/dev/null)

  if [ -n "$LAST_JOB" ]; then
    echo -e "${YELLOW}Logs del último job ($LAST_JOB):${NC}"
    kubectl logs job/$LAST_JOB -n $NAMESPACE 2>/dev/null || print_warning "No hay logs para el último job"
  else
    print_warning "No hay jobs del scheduler ejecutados"
  fi
  echo ""
}

function restart_workers() {
  print_header "Reiniciando Queue Workers"

  kubectl rollout restart deployment/tavira-queue-worker -n $NAMESPACE
  print_success "Reinicio iniciado"

  echo -e "${YELLOW}Esperando a que el rollout complete...${NC}"
  kubectl rollout status deployment/tavira-queue-worker -n $NAMESPACE

  print_success "Workers reiniciados correctamente"
  echo ""
}

function scale_workers() {
  if [ -z "$2" ]; then
    print_error "Uso: $0 scale <num-replicas>"
    exit 1
  fi

  REPLICAS=$2
  print_header "Escalando Queue Workers a $REPLICAS replicas"

  kubectl scale deployment tavira-queue-worker --replicas=$REPLICAS -n $NAMESPACE
  print_success "Escalado a $REPLICAS replicas"

  echo ""
  kubectl get pods -l app=tavira-queue-worker -n $NAMESPACE
  echo ""
}

function show_help() {
  cat << EOF
${BLUE}Queue Workers y Scheduler - Script de Gestión${NC}

${YELLOW}Uso:${NC}
  $0 <comando> [opciones]

${YELLOW}Comandos:${NC}
  ${GREEN}apply${NC}       Aplicar todos los manifiestos (deploy)
  ${GREEN}delete${NC}      Eliminar todos los recursos
  ${GREEN}status${NC}      Mostrar estado de workers y scheduler
  ${GREEN}logs${NC}        Mostrar logs de workers y scheduler
  ${GREEN}restart${NC}     Reiniciar queue workers (rolling restart)
  ${GREEN}scale${NC}       Escalar workers manualmente
              Uso: $0 scale <num-replicas>
  ${GREEN}help${NC}        Mostrar esta ayuda

${YELLOW}Ejemplos:${NC}
  $0 apply              # Desplegar todos los recursos
  $0 status             # Ver estado actual
  $0 logs               # Ver logs
  $0 restart            # Reiniciar workers
  $0 scale 5            # Escalar a 5 workers
  $0 delete             # Eliminar recursos

${YELLOW}Prerequisitos:${NC}
  - kubectl instalado y configurado
  - Secret 'laravel-env' creado en namespace $NAMESPACE
  - PVC 'tavira-storage-pvc' (opcional)
  - Redis deployment (recomendado)

${YELLOW}Archivos requeridos:${NC}
  - queue-worker-deployment.yaml
  - queue-worker-hpa.yaml
  - scheduler-cronjob.yaml

${YELLOW}Más información:${NC}
  Ver README-QUEUE-SCHEDULER.md para documentación completa

EOF
}

# Main
case "$1" in
  apply)
    check_prerequisites
    apply_manifests
    show_status
    ;;
  delete)
    delete_manifests
    ;;
  status)
    show_status
    ;;
  logs)
    show_logs
    ;;
  restart)
    restart_workers
    ;;
  scale)
    scale_workers "$@"
    ;;
  help|--help|-h)
    show_help
    ;;
  *)
    print_error "Comando no reconocido: $1"
    echo ""
    show_help
    exit 1
    ;;
esac
