#!/bin/bash

# Tavira Deployment Script
# This script automates the deployment process to Kubernetes

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
IMAGE_NAME="ingmontoyav/tavira-app"
IMAGE_TAG="${1:-latest}"  # Use first argument as tag, default to 'latest'
DEPLOYMENT_NAME="tavira-app"
NAMESPACE="default"

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}  Tavira Kubernetes Deployment${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Step 1: Build Docker image
echo -e "${YELLOW}📦 Step 1: Building Docker image...${NC}"
echo -e "   Image: ${IMAGE_NAME}:${IMAGE_TAG}"
docker build -t ${IMAGE_NAME}:${IMAGE_TAG} .

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Image built successfully${NC}"
else
    echo -e "${RED}❌ Failed to build image${NC}"
    exit 1
fi

echo ""

# Step 2: Push to Docker Hub
echo -e "${YELLOW}📤 Step 2: Pushing image to Docker Hub...${NC}"
docker push ${IMAGE_NAME}:${IMAGE_TAG}

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Image pushed successfully${NC}"
else
    echo -e "${RED}❌ Failed to push image${NC}"
    exit 1
fi

echo ""

# Step 3: Verify Kubernetes connection
echo -e "${YELLOW}🔍 Step 3: Verifying Kubernetes connection...${NC}"
kubectl cluster-info > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Connected to Kubernetes cluster${NC}"
else
    echo -e "${RED}❌ Cannot connect to Kubernetes cluster${NC}"
    exit 1
fi

echo ""

# Step 4: Update deployment
echo -e "${YELLOW}🚀 Step 4: Deploying to Kubernetes...${NC}"

# Option A: If using 'latest' tag, force rollout restart
if [ "${IMAGE_TAG}" = "latest" ]; then
    echo -e "   Using rollout restart (image tag: latest)..."
    kubectl rollout restart deployment/${DEPLOYMENT_NAME} -n ${NAMESPACE}
else
    # Option B: If using versioned tag, update image
    echo -e "   Updating deployment image to ${IMAGE_TAG}..."
    kubectl set image deployment/${DEPLOYMENT_NAME} \
        php-fpm=${IMAGE_NAME}:${IMAGE_TAG} \
        -n ${NAMESPACE}
fi

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Deployment updated${NC}"
else
    echo -e "${RED}❌ Failed to update deployment${NC}"
    exit 1
fi

echo ""

# Step 5: Wait for rollout to complete
echo -e "${YELLOW}⏳ Step 5: Waiting for rollout to complete...${NC}"
kubectl rollout status deployment/${DEPLOYMENT_NAME} -n ${NAMESPACE} --timeout=5m

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Rollout completed successfully${NC}"
else
    echo -e "${RED}❌ Rollout failed or timed out${NC}"
    echo ""
    echo -e "${YELLOW}Recent pod events:${NC}"
    kubectl get events -n ${NAMESPACE} --sort-by='.lastTimestamp' | tail -10
    exit 1
fi

echo ""

# Step 6: Verify pods are running
echo -e "${YELLOW}🔍 Step 6: Verifying pod status...${NC}"
kubectl get pods -n ${NAMESPACE} -l app=tavira

echo ""

# Step 7: Run database migrations and seeders
echo -e "${YELLOW}🗄️  Step 7: Running database migrations and seeders...${NC}"
POD_NAME=$(kubectl get pods -n ${NAMESPACE} -l app=tavira -o jsonpath='{.items[0].metadata.name}' 2>/dev/null)

if [ -n "$POD_NAME" ]; then
    echo -e "   Using pod: ${POD_NAME}"

    # Run central migrations
    echo -e "   • Running central migrations..."
    kubectl exec ${POD_NAME} -c php-fpm -n ${NAMESPACE} -- php artisan migrate --force

    # Run central seeders (idempotent - safe to run multiple times)
    echo -e "   • Running central seeders..."
    kubectl exec ${POD_NAME} -c php-fpm -n ${NAMESPACE} -- php artisan db:seed --force

    # Run tenant migrations
    echo -e "   • Running tenant migrations..."
    kubectl exec ${POD_NAME} -c php-fpm -n ${NAMESPACE} -- php artisan tenants:migrate --force

    # Run tenant seeders
    echo -e "   • Running tenant seeders..."
    kubectl exec ${POD_NAME} -c php-fpm -n ${NAMESPACE} -- php artisan tenants:seed --force

    echo -e "${GREEN}✅ Database migrations and seeders completed${NC}"
else
    echo -e "${RED}⚠️  No pods found - skipping migrations${NC}"
fi

echo ""

# Step 8: Show recent logs
echo -e "${YELLOW}📋 Step 8: Recent application logs:${NC}"
if [ -n "$POD_NAME" ]; then
    kubectl logs -n ${NAMESPACE} ${POD_NAME} -c php-fpm --tail=20
else
    echo -e "${RED}⚠️  No pods found${NC}"
fi

echo ""
echo -e "${GREEN}========================================${NC}"
echo -e "${GREEN}  Deployment Complete! 🎉${NC}"
echo -e "${GREEN}========================================${NC}"
echo ""
echo -e "${BLUE}Useful commands:${NC}"
echo -e "  • Watch pods:         kubectl get pods -l app=tavira -w"
echo -e "  • View logs:          kubectl logs -f deployment/${DEPLOYMENT_NAME} -c php-fpm"
echo -e "  • Describe pod:       kubectl describe pod <pod-name>"
echo -e "  • Rollback:           kubectl rollout undo deployment/${DEPLOYMENT_NAME}"
echo ""
