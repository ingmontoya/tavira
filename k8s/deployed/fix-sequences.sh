#!/bin/bash

# Quick fix for PostgreSQL sequence sync issues in Kubernetes
# Run this if you get "duplicate key value violates unique constraint" errors

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

NAMESPACE="default"

echo -e "${YELLOW}🔧 PostgreSQL Sequence Reset Tool${NC}"
echo ""

# Get a running pod
POD_NAME=$(kubectl get pods -n ${NAMESPACE} -l app=tavira -o jsonpath='{.items[0].metadata.name}' 2>/dev/null)

if [ -z "$POD_NAME" ]; then
    echo -e "${RED}❌ No running pods found${NC}"
    exit 1
fi

echo -e "${BLUE}Using pod: ${POD_NAME}${NC}"
echo ""

# Ask for confirmation
echo -e "${YELLOW}This will reset PostgreSQL sequences for ALL tenants.${NC}"
echo -e "${YELLOW}This is safe and will fix 'duplicate key' errors.${NC}"
echo ""
read -p "Continue? (yes/no): " -r
echo

if [[ ! $REPLY =~ ^[Yy][Ee][Ss]$ ]]; then
    echo -e "${YELLOW}Operation cancelled${NC}"
    exit 0
fi

# Reset sequences
echo -e "${BLUE}Resetting sequences...${NC}"
kubectl exec -it ${POD_NAME} -c php-fpm -n ${NAMESPACE} -- php artisan db:reset-sequences --tenant

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ Sequences reset successfully!${NC}"
    echo ""
    echo -e "${BLUE}You can now try creating records again.${NC}"
else
    echo ""
    echo -e "${RED}❌ Failed to reset sequences${NC}"
    echo ""
    echo -e "${YELLOW}Try manually:${NC}"
    echo "kubectl exec -it ${POD_NAME} -c php-fpm -- php artisan db:reset-sequences --tenant"
    exit 1
fi
