#!/bin/bash

# =============================================================================
# Tavira Local Kubernetes Deployment Script
# =============================================================================
# This script deploys the Tavira application to a local Kubernetes cluster
# (OrbStack, Minikube, Docker Desktop, etc.)
#
# Usage:
#   ./deploy.sh              # Full deployment
#   ./deploy.sh --destroy    # Tear down everything
#   ./deploy.sh --restart    # Restart app deployment only
# =============================================================================

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
NAMESPACE="local"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Helper functions
print_header() {
    echo -e "\n${BLUE}===================================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}===================================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Check if kubectl is available
check_kubectl() {
    if ! command -v kubectl &> /dev/null; then
        print_error "kubectl not found. Please install kubectl first."
        exit 1
    fi
    print_success "kubectl is available"
}

# Check if cluster is reachable
check_cluster() {
    if ! kubectl cluster-info &> /dev/null; then
        print_error "Cannot connect to Kubernetes cluster. Make sure OrbStack or another K8s is running."
        exit 1
    fi
    print_success "Kubernetes cluster is reachable"
}

# Destroy all resources
destroy() {
    print_header "Destroying Local Deployment"

    echo "Deleting all resources in namespace: $NAMESPACE"
    kubectl delete namespace $NAMESPACE --ignore-not-found=true

    print_success "All resources destroyed"
    exit 0
}

# Restart app deployment only
restart_app() {
    print_header "Restarting Application"

    kubectl rollout restart deployment/tavira-app-local -n $NAMESPACE
    kubectl rollout status deployment/tavira-app-local -n $NAMESPACE

    print_success "Application restarted"
    exit 0
}

# Main deployment function
deploy() {
    print_header "Deploying Tavira to Local Kubernetes"

    # Step 1: Create namespace
    print_header "Step 1: Creating Namespace"
    kubectl apply -f "$SCRIPT_DIR/namespace.yaml"
    print_success "Namespace created/updated"

    # Step 2: Create PVCs
    print_header "Step 2: Creating Persistent Volume Claims"
    kubectl apply -f "$SCRIPT_DIR/pvcs.yaml"
    print_success "PVCs created/updated"

    # Step 3: Create ConfigMaps
    print_header "Step 3: Creating ConfigMaps"
    kubectl apply -f "$SCRIPT_DIR/postgres-config.yaml"
    kubectl apply -f "$SCRIPT_DIR/nginx-config.yaml"
    print_success "ConfigMaps created/updated"

    # Step 4: Create Secrets
    print_header "Step 4: Creating Secrets"
    print_warning "Using default development secrets (not secure for production!)"
    kubectl apply -f "$SCRIPT_DIR/laravel-env-secret.yaml"
    print_success "Secrets created/updated"

    # Step 5: Deploy PostgreSQL
    print_header "Step 5: Deploying PostgreSQL"
    kubectl apply -f "$SCRIPT_DIR/postgres-deployment.yaml"
    kubectl apply -f "$SCRIPT_DIR/postgres-service.yaml"
    print_success "PostgreSQL deployed"

    # Step 6: Deploy Redis
    print_header "Step 6: Deploying Redis"
    kubectl apply -f "$SCRIPT_DIR/redis-deployment.yaml"
    kubectl apply -f "$SCRIPT_DIR/redis-service.yaml"
    print_success "Redis deployed"

    # Step 7: Wait for database to be ready
    print_header "Step 7: Waiting for Database"
    echo "Waiting for PostgreSQL to be ready..."
    kubectl wait --for=condition=ready pod -l app=postgres-local -n $NAMESPACE --timeout=120s
    print_success "PostgreSQL is ready"

    # Step 8: Deploy Application
    print_header "Step 8: Deploying Tavira Application"
    kubectl apply -f "$SCRIPT_DIR/deployment.yaml"
    kubectl apply -f "$SCRIPT_DIR/service.yaml"
    print_success "Application deployed"

    # Step 9: Wait for application to be ready
    print_header "Step 9: Waiting for Application"
    echo "Waiting for Tavira app to be ready..."
    kubectl wait --for=condition=ready pod -l app=tavira-local -n $NAMESPACE --timeout=180s
    print_success "Application is ready"

    # Step 10: Display status
    print_header "Deployment Complete!"
    echo ""
    echo "Your Tavira application is now running on Kubernetes!"
    echo ""
    echo -e "${GREEN}Access the application at:${NC}"
    echo -e "  ${BLUE}http://localhost:30080${NC}"
    echo ""
    echo -e "${GREEN}Useful commands:${NC}"
    echo "  View pods:        kubectl get pods -n $NAMESPACE"
    echo "  View services:    kubectl get svc -n $NAMESPACE"
    echo "  View logs (app):  kubectl logs -f deployment/tavira-app-local -n $NAMESPACE -c php-fpm"
    echo "  View logs (nginx): kubectl logs -f deployment/tavira-app-local -n $NAMESPACE -c nginx"
    echo "  View logs (db):   kubectl logs -f deployment/postgres-local -n $NAMESPACE"
    echo ""
    echo -e "${GREEN}Run migrations:${NC}"
    echo "  kubectl exec -n $NAMESPACE deployment/tavira-app-local -c php-fpm -- php artisan migrate --force"
    echo ""
    echo -e "${GREEN}Run seeders:${NC}"
    echo "  kubectl exec -n $NAMESPACE deployment/tavira-app-local -c php-fpm -- php artisan db:seed"
    echo ""
    echo -e "${GREEN}Connect to database:${NC}"
    echo "  kubectl exec -it deployment/postgres-local -n $NAMESPACE -- psql -U tavira_user_local -d tavira_local"
    echo ""
    echo -e "${GREEN}Shell into app container:${NC}"
    echo "  kubectl exec -it deployment/tavira-app-local -n $NAMESPACE -c php-fpm -- sh"
    echo ""
}

# Parse arguments
case "${1:-}" in
    --destroy)
        check_kubectl
        check_cluster
        destroy
        ;;
    --restart)
        check_kubectl
        check_cluster
        restart_app
        ;;
    --help|-h)
        echo "Usage: $0 [OPTION]"
        echo ""
        echo "Options:"
        echo "  (no args)     Full deployment"
        echo "  --destroy     Tear down everything"
        echo "  --restart     Restart app deployment only"
        echo "  --help        Show this help message"
        exit 0
        ;;
    "")
        check_kubectl
        check_cluster
        deploy
        ;;
    *)
        print_error "Unknown option: $1"
        echo "Use --help to see available options"
        exit 1
        ;;
esac
