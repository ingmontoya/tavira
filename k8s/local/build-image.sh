#!/bin/bash

# =============================================================================
# Build Docker Image for Local Kubernetes
# =============================================================================
# This script builds the Tavira Docker image for use with local K8s on ARM64
#
# Usage:
#   ./build-image.sh              # Build image
#   ./build-image.sh --rebuild    # Clean build (no cache)
# =============================================================================

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

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

# Navigate to project root
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
cd "$PROJECT_ROOT"

print_header "Building Tavira Docker Image for Local K8s"

# Check for --rebuild flag
if [ "$1" == "--rebuild" ]; then
    print_warning "Building with --no-cache (clean build)"
    docker build --no-cache -t tavira-app:local -f Dockerfile .
else
    docker build -t tavira-app:local -f Dockerfile .
fi

print_success "Image built successfully: tavira-app:local"
print_success "Architecture: $(docker inspect tavira-app:local | grep Architecture | head -1 | awk '{print $2}' | tr -d ',"')"

echo ""
echo "Next steps:"
echo "  1. Deploy to local K8s: cd k8s/local && ./deploy.sh"
echo "  2. Or restart existing deployment: ./deploy.sh --restart"
echo ""
