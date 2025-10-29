#!/bin/bash

# Tavira Deployment Script
# Usage: ./scripts/deploy.sh [image-tag]
# Example: ./scripts/deploy.sh v20251025-a1b2c3d

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
DEPLOYMENT_NAME="tavira-app"
NAMESPACE="default"
DOCKER_IMAGE_PHP="ingmontoyav/tavira-app"

# Get image tag from argument or use 'latest'
IMAGE_TAG="${1:-latest}"

echo -e "${GREEN}🚀 Deploying Tavira to Kubernetes${NC}"
echo -e "${YELLOW}Image: $DOCKER_IMAGE_PHP:$IMAGE_TAG${NC}"
echo ""

# Check if kubectl is available
if ! command -v kubectl &> /dev/null; then
    echo -e "${RED}❌ kubectl not found. Please install kubectl first.${NC}"
    exit 1
fi

# Check cluster connection
echo "🔍 Checking cluster connection..."
if ! kubectl cluster-info &> /dev/null; then
    echo -e "${RED}❌ Cannot connect to Kubernetes cluster${NC}"
    echo "Please check your kubeconfig configuration"
    exit 1
fi
echo -e "${GREEN}✅ Connected to cluster${NC}"
echo ""

# Pull latest image to verify it exists
echo "📥 Verifying Docker image exists..."
if ! docker pull "$DOCKER_IMAGE_PHP:$IMAGE_TAG" &> /dev/null; then
    echo -e "${YELLOW}⚠️  Could not pull image locally (this is OK if running remotely)${NC}"
fi
echo ""

# Update deployment
echo "🔄 Updating deployment..."
kubectl set image deployment/$DEPLOYMENT_NAME \
    php-fpm=$DOCKER_IMAGE_PHP:$IMAGE_TAG \
    -n $NAMESPACE \
    --record

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to update deployment${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Deployment updated${NC}"
echo ""

# Wait for rollout
echo "⏳ Waiting for rollout to complete (timeout: 10 minutes)..."
kubectl rollout status deployment/$DEPLOYMENT_NAME -n $NAMESPACE --timeout=10m

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Rollout failed or timed out${NC}"
    echo ""
    echo "To rollback, run:"
    echo "  kubectl rollout undo deployment/$DEPLOYMENT_NAME -n $NAMESPACE"
    exit 1
fi
echo -e "${GREEN}✅ Rollout completed successfully${NC}"
echo ""

# Get pod name (using correct label 'app=tavira')
echo "🔍 Getting pod information..."
POD=$(kubectl get pods -n $NAMESPACE -l app=tavira -o jsonpath='{.items[0].metadata.name}')

if [ -z "$POD" ]; then
    echo -e "${RED}❌ Could not find pod${NC}"
    echo "Available pods:"
    kubectl get pods -n $NAMESPACE
    exit 1
fi
echo -e "${GREEN}✅ Pod: $POD${NC}"
echo ""

# Run post-deployment tasks
echo "🛠️  Running post-deployment tasks..."

echo "  → Clearing config cache..."
kubectl exec -n $NAMESPACE $POD -c php-fpm -- php artisan config:clear

echo "  → Caching config..."
kubectl exec -n $NAMESPACE $POD -c php-fpm -- php artisan config:cache

# Note: route:cache is skipped for multitenancy apps to avoid route conflicts
echo "  → Skipping route cache (multitenancy app)"

echo "  → Caching views..."
kubectl exec -n $NAMESPACE $POD -c php-fpm -- php artisan view:cache

echo "  → Running central migrations..."
kubectl exec -n $NAMESPACE $POD -c php-fpm -- php artisan migrate --force

echo "  → Running tenant migrations..."
kubectl exec -n $NAMESPACE $POD -c php-fpm -- php artisan tenants:migrate --force

echo -e "${GREEN}✅ Post-deployment tasks completed${NC}"
echo ""

# Show deployment status
echo "📊 Current deployment status:"
kubectl get deployment $DEPLOYMENT_NAME -n $NAMESPACE
echo ""

echo "📋 Running pods:"
kubectl get pods -n $NAMESPACE -l app=tavira
echo ""

echo -e "${GREEN}✅ Deployment completed successfully!${NC}"
echo ""
echo "🌐 Your application should now be available at: https://tavira.com.co"
echo ""
echo "📝 Useful commands:"
echo "  - View logs: kubectl logs -f $POD -c php-fpm -n $NAMESPACE"
echo "  - Rollback: kubectl rollout undo deployment/$DEPLOYMENT_NAME -n $NAMESPACE"
echo "  - Restart: kubectl rollout restart deployment/$DEPLOYMENT_NAME -n $NAMESPACE"
