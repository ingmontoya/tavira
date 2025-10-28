#!/bin/bash

# Script para configurar el sistema completo de alertas
# Uso: ./setup-alerts.sh [install|uninstall|status|test]

set -e

NAMESPACE="default"
MONITORING_NAMESPACE="monitoring"

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

  # Verificar Prometheus CRDs
  if ! kubectl get crd prometheusrules.monitoring.coreos.com &> /dev/null; then
    print_error "Prometheus Operator no está instalado"
    echo ""
    echo -e "${YELLOW}Instala Prometheus Operator con:${NC}"
    echo "helm repo add prometheus-community https://prometheus-community.github.io/helm-charts"
    echo "helm install prometheus prometheus-community/kube-prometheus-stack -n monitoring --create-namespace"
    exit 1
  fi
  print_success "Prometheus Operator instalado"

  # Verificar namespace monitoring
  if ! kubectl get namespace $MONITORING_NAMESPACE &> /dev/null; then
    print_warning "Namespace '$MONITORING_NAMESPACE' no encontrado. Creando..."
    kubectl create namespace $MONITORING_NAMESPACE
  fi
  print_success "Namespace '$MONITORING_NAMESPACE' existe"

  echo ""
}

function install_prometheus_rules() {
  print_header "Instalando PrometheusRules"

  echo -e "${BLUE}Aplicando: prometheus-rules.yaml${NC}"
  if kubectl apply -f prometheus-rules.yaml -n $NAMESPACE; then
    print_success "prometheus-rules.yaml aplicado"
  else
    print_error "Error aplicando prometheus-rules.yaml"
    exit 1
  fi

  echo -e "${BLUE}Aplicando: prometheus-custom-alerts.yaml${NC}"
  if kubectl apply -f prometheus-custom-alerts.yaml -n $NAMESPACE; then
    print_success "prometheus-custom-alerts.yaml aplicado"
  else
    print_error "Error aplicando prometheus-custom-alerts.yaml"
    exit 1
  fi

  echo ""
  print_success "Reglas de alertas instaladas"
  echo ""
}

function install_service_monitor() {
  print_header "Instalando ServiceMonitor"

  echo -e "${YELLOW}Nota: Esto requiere que los queue workers tengan el sidecar de métricas${NC}"
  read -p "¿Deseas continuar? (y/n) " -n 1 -r
  echo ""

  if [[ $REPLY =~ ^[Yy]$ ]]; then
    if kubectl apply -f service-monitor.yaml -n $NAMESPACE; then
      print_success "ServiceMonitor instalado"
    else
      print_warning "Error instalando ServiceMonitor (puede ser opcional)"
    fi
  else
    print_warning "ServiceMonitor omitido"
  fi

  echo ""
}

function uninstall_alerts() {
  print_header "Desinstalando Sistema de Alertas"

  echo -e "${BLUE}Eliminando PrometheusRules...${NC}"
  kubectl delete -f prometheus-rules.yaml -n $NAMESPACE --ignore-not-found
  kubectl delete -f prometheus-custom-alerts.yaml -n $NAMESPACE --ignore-not-found
  print_success "PrometheusRules eliminados"

  echo -e "${BLUE}Eliminando ServiceMonitor...${NC}"
  kubectl delete -f service-monitor.yaml -n $NAMESPACE --ignore-not-found
  print_success "ServiceMonitor eliminado"

  echo ""
  print_success "Sistema de alertas desinstalado"
  echo ""
}

function show_status() {
  print_header "Estado del Sistema de Alertas"

  echo -e "${YELLOW}PrometheusRules:${NC}"
  kubectl get prometheusrules -n $NAMESPACE 2>/dev/null || print_warning "No hay PrometheusRules"
  echo ""

  echo -e "${YELLOW}ServiceMonitors:${NC}"
  kubectl get servicemonitors -n $NAMESPACE 2>/dev/null || print_warning "No hay ServiceMonitors"
  echo ""

  echo -e "${YELLOW}Prometheus Pods:${NC}"
  kubectl get pods -n $MONITORING_NAMESPACE -l app.kubernetes.io/name=prometheus 2>/dev/null || print_warning "Prometheus no encontrado"
  echo ""

  echo -e "${YELLOW}AlertManager Pods:${NC}"
  kubectl get pods -n $MONITORING_NAMESPACE -l app.kubernetes.io/name=alertmanager 2>/dev/null || print_warning "AlertManager no encontrado"
  echo ""
}

function test_alerts() {
  print_header "Probando Alertas"

  echo -e "${YELLOW}Opciones de prueba:${NC}"
  echo "1) Escalar workers a 0 (QueueWorkersDown)"
  echo "2) Suspender scheduler (SchedulerNotRunning)"
  echo "3) Ver alertas activas en Prometheus"
  echo "4) Volver atrás"
  echo ""

  read -p "Selecciona una opción: " option

  case $option in
    1)
      echo -e "${YELLOW}Escalando workers a 0...${NC}"
      kubectl scale deployment tavira-queue-worker --replicas=0 -n $NAMESPACE
      print_success "Workers escalados a 0"
      echo -e "${YELLOW}Espera 2 minutos y verifica la alerta 'QueueWorkersDown'${NC}"
      echo ""
      read -p "Presiona Enter para restaurar los workers..."
      kubectl scale deployment tavira-queue-worker --replicas=2 -n $NAMESPACE
      print_success "Workers restaurados"
      ;;
    2)
      echo -e "${YELLOW}Suspendiendo scheduler...${NC}"
      kubectl patch cronjob tavira-scheduler -p '{"spec":{"suspend":true}}' -n $NAMESPACE
      print_success "Scheduler suspendido"
      echo -e "${YELLOW}Espera 5 minutos y verifica la alerta 'SchedulerNotRunning'${NC}"
      echo ""
      read -p "Presiona Enter para restaurar el scheduler..."
      kubectl patch cronjob tavira-scheduler -p '{"spec":{"suspend":false}}' -n $NAMESPACE
      print_success "Scheduler restaurado"
      ;;
    3)
      echo -e "${YELLOW}Abriendo port-forward a Prometheus...${NC}"
      echo -e "${GREEN}Accede a: http://localhost:9090/alerts${NC}"
      echo -e "${YELLOW}Presiona Ctrl+C para salir${NC}"
      kubectl port-forward -n $MONITORING_NAMESPACE svc/prometheus-kube-prometheus-prometheus 9090:9090
      ;;
    4)
      return
      ;;
    *)
      print_error "Opción inválida"
      ;;
  esac

  echo ""
}

function open_uis() {
  print_header "Abriendo UIs de Monitoreo"

  echo -e "${YELLOW}¿Qué UI deseas abrir?${NC}"
  echo "1) Prometheus (puerto 9090)"
  echo "2) AlertManager (puerto 9093)"
  echo "3) Grafana (puerto 3000)"
  echo "4) Queue Metrics (puerto 9090)"
  echo "5) Volver atrás"
  echo ""

  read -p "Selecciona una opción: " option

  case $option in
    1)
      echo -e "${GREEN}Accede a: http://localhost:9090${NC}"
      echo -e "${YELLOW}Presiona Ctrl+C para salir${NC}"
      kubectl port-forward -n $MONITORING_NAMESPACE svc/prometheus-kube-prometheus-prometheus 9090:9090
      ;;
    2)
      echo -e "${GREEN}Accede a: http://localhost:9093${NC}"
      echo -e "${YELLOW}Presiona Ctrl+C para salir${NC}"
      kubectl port-forward -n $MONITORING_NAMESPACE svc/alertmanager-operated 9093:9093
      ;;
    3)
      echo -e "${GREEN}Accede a: http://localhost:3000${NC}"
      echo -e "${GREEN}Usuario por defecto: admin / prom-operator${NC}"
      echo -e "${YELLOW}Presiona Ctrl+C para salir${NC}"
      kubectl port-forward -n $MONITORING_NAMESPACE svc/prometheus-grafana 3000:80
      ;;
    4)
      echo -e "${GREEN}Accede a: http://localhost:9090/metrics${NC}"
      echo -e "${YELLOW}Presiona Ctrl+C para salir${NC}"
      kubectl port-forward -n $NAMESPACE svc/tavira-queue-worker-metrics 9090:9090
      ;;
    5)
      return
      ;;
    *)
      print_error "Opción inválida"
      ;;
  esac

  echo ""
}

function show_help() {
  cat << EOF
${BLUE}Sistema de Alertas - Script de Gestión${NC}

${YELLOW}Uso:${NC}
  $0 <comando>

${YELLOW}Comandos:${NC}
  ${GREEN}install${NC}      Instalar sistema completo de alertas
  ${GREEN}uninstall${NC}    Desinstalar sistema de alertas
  ${GREEN}status${NC}       Mostrar estado del sistema
  ${GREEN}test${NC}         Probar alertas
  ${GREEN}ui${NC}           Abrir UIs de monitoreo
  ${GREEN}help${NC}         Mostrar esta ayuda

${YELLOW}Ejemplos:${NC}
  $0 install        # Instalar todas las alertas
  $0 status         # Ver estado
  $0 test           # Probar alertas
  $0 ui             # Abrir UIs

${YELLOW}Prerequisitos:${NC}
  - kubectl instalado y configurado
  - Prometheus Operator instalado
  - AlertManager instalado

${YELLOW}Archivos requeridos:${NC}
  - prometheus-rules.yaml
  - prometheus-custom-alerts.yaml
  - service-monitor.yaml (opcional)

${YELLOW}Más información:${NC}
  Ver README-ALERTING.md para documentación completa

EOF
}

# Main
case "$1" in
  install)
    check_prerequisites
    install_prometheus_rules
    install_service_monitor
    show_status
    echo -e "${GREEN}Sistema de alertas instalado correctamente${NC}"
    echo -e "${YELLOW}Siguiente paso: Configurar AlertManager (ver README-ALERTING.md)${NC}"
    ;;
  uninstall)
    uninstall_alerts
    ;;
  status)
    show_status
    ;;
  test)
    test_alerts
    ;;
  ui)
    open_uis
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
