#!/bin/bash

# Tavira Rollback Script
# Quickly rollback to previous deployment in case of issues

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

DEPLOYMENT_NAME="tavira-app"
NAMESPACE="default"

echo -e "${YELLOW}⚠️  Tavira Deployment Rollback${NC}"
echo ""

# Show rollout history
echo -e "${BLUE}Rollout history:${NC}"
kubectl rollout history deployment/${DEPLOYMENT_NAME} -n ${NAMESPACE}

echo ""
echo -e "${RED}This will rollback to the previous deployment.${NC}"
read -p "Are you sure? (yes/no): " -r
echo

if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo -e "${YELLOW}Rollback cancelled${NC}"
    exit 0
fi

# Perform rollback
echo -e "${YELLOW}🔄 Rolling back deployment...${NC}"
kubectl rollout undo deployment/${DEPLOYMENT_NAME} -n ${NAMESPACE}

# Wait for rollout
echo -e "${YELLOW}⏳ Waiting for rollback to complete...${NC}"
kubectl rollout status deployment/${DEPLOYMENT_NAME} -n ${NAMESPACE} --timeout=5m

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Rollback completed successfully${NC}"
    echo ""
    echo -e "${BLUE}Current pods:${NC}"
    kubectl get pods -n ${NAMESPACE} -l app=tavira
else
    echo -e "${RED}❌ Rollback failed${NC}"
    exit 1
fi
