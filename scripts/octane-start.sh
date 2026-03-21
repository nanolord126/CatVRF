#!/bin/bash
#
# CatVRF Octane Startup Script
# Optimize performance with preloading, caching, and hot reload
#

set -e

echo "🚀 Starting CatVRF Octane Server (CANON 2026 Production)"
echo "=================================================="

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Configuration
ENVIRONMENT="${1:-production}"
PORT="${OCTANE_PORT:-8000}"
WORKERS="${OCTANE_SWOOLE_WORKERS:-auto}"
TASK_WORKERS="${OCTANE_SWOOLE_TASK_WORKERS:-auto}"
MODE="${OCTANE_SWOOLE_MODE:-process}"

# Step 1: Optimize application
echo -e "${YELLOW}[1/6]${NC} Optimizing application..."
php artisan optimize:clear
php artisan optimize

echo -e "${GREEN}✓${NC} Config, routes, and events cached"

# Step 2: Cache dependencies
echo -e "${YELLOW}[2/6]${NC} Caching dependencies..."
php artisan view:cache
php artisan route:cache
php artisan config:cache

echo -e "${GREEN}✓${NC} Views, routes, and config cached"

# Step 3: Compile assets
if [ "$ENVIRONMENT" == "production" ]; then
    echo -e "${YELLOW}[3/6]${NC} Building frontend assets..."
    npm ci
    npm run build
    echo -e "${GREEN}✓${NC} Assets compiled"
else
    echo -e "${YELLOW}[3/6]${NC} Skipping asset build (non-production)"
fi

# Step 4: Check migrations
echo -e "${YELLOW}[4/6]${NC} Verifying database migrations..."
php artisan migrate:status

# Step 5: Warm up Octane
echo -e "${YELLOW}[5/6]${NC} Warming up Octane cache..."
php artisan octane:install --with=swoole --force

# Step 6: Start Octane server
echo -e "${YELLOW}[6/6]${NC} Starting Octane server..."
echo ""

if [ "$ENVIRONMENT" == "production" ]; then
    # Production: Run in background with supervisor or systemd
    echo -e "${GREEN}Starting in PRODUCTION mode${NC}"
    php artisan octane:start \
        --server=swoole \
        --host=0.0.0.0 \
        --port=$PORT \
        --workers=$WORKERS \
        --task-workers=$TASK_WORKERS \
        --mode=$MODE
else
    # Development: Run in foreground with hot reload
    echo -e "${GREEN}Starting in DEVELOPMENT mode (hot reload enabled)${NC}"
    php artisan octane:start \
        --server=swoole \
        --host=127.0.0.1 \
        --port=$PORT \
        --workers=4 \
        --task-workers=4 \
        --mode=process \
        --watch
fi
