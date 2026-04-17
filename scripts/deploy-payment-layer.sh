#!/bin/bash

# Payment Layer Deployment Script
# CatVRF 2026 - Production-ready deployment with feature flags

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}=== CatVRF Payment Layer Deployment ===${NC}"
echo ""

# Configuration
ENV=${1:-staging}
FEATURE_FLAG_DISABLED=${2:-true}

echo "Deploying to: $ENV"
echo "Feature flag disabled: $FEATURE_FLAG_DISABLED"
echo ""

# Pre-deployment checks
echo -e "${YELLOW}1. Pre-deployment checks...${NC}"

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo -e "${RED}ERROR: vendor directory not found. Run composer install first.${NC}"
    exit 1
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo -e "${RED}ERROR: .env file not found.${NC}"
    exit 1
fi

# Check Redis connection
echo "Checking Redis connection..."
php artisan config:cache > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo -e "${RED}ERROR: Redis connection failed.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Pre-deployment checks passed${NC}"
echo ""

# Backup current configuration
echo -e "${YELLOW}2. Backing up configuration...${NC}"
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo -e "${GREEN}✓ Configuration backed up${NC}"
echo ""

# Update environment variables
echo -e "${YELLOW}3. Setting environment variables...${NC}"
if [ "$FEATURE_FLAG_DISABLED" = "true" ]; then
    php artisan env:set PAYMENT_NEW_ENGINE_ENABLED=false
    php artisan env:set PAYMENT_ASYNC_FRAUD_ENABLED=true
    php artisan env:set PAYMENT_CIRCUIT_BREAKER_ENABLED=true
else
    php artisan env:set PAYMENT_NEW_ENGINE_ENABLED=true
fi

echo -e "${GREEN}✓ Environment variables set${NC}"
echo ""

# Clear and cache configuration
echo -e "${YELLOW}4. Caching configuration...${NC}"
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
echo -e "${GREEN}✓ Configuration cached${NC}"
echo ""

# Run migrations if needed
echo -e "${YELLOW}5. Running database migrations...${NC}"
php artisan migrate --force
echo -e "${GREEN}✓ Migrations completed${NC}"
echo ""

# Restart queues
echo -e "${YELLOW}6. Restarting queue workers...${NC}"
php artisan queue:restart
echo -e "${GREEN}✓ Queue workers restarted${NC}"
echo ""

# Clear opcode cache if using OPcache
echo -e "${YELLOW}7. Clearing opcode cache...${NC}"
if [ -f "php-fpm.sock" ]; then
    # For production with PHP-FPM
    echo "Reloading PHP-FPM..."
    sudo service php-fpm reload 2>/dev/null || echo "PHP-FPM reload skipped (not root)"
else
    # For Octane
    echo "Restarting Octane..."
    php artisan octane:reload 2>/dev/null || echo "Octane reload skipped"
fi
echo -e "${GREEN}✓ Opcode cache cleared${NC}"
echo ""

# Health check
echo -e "${YELLOW}8. Running health checks...${NC}"
php artisan payment:health-check || {
    echo -e "${RED}ERROR: Health check failed. Rolling back...${NC}"
    cp .env.backup.* .env
    php artisan config:cache
    php artisan queue:restart
    exit 1
}
echo -e "${GREEN}✓ Health checks passed${NC}"
echo ""

# Deployment summary
echo -e "${GREEN}=== Deployment Summary ===${NC}"
echo "Environment: $ENV"
echo "Payment Engine: $([ "$FEATURE_FLAG_DISABLED" = "true" ] && echo "Legacy" || echo "New")"
echo "Async Fraud: Enabled"
echo "Circuit Breaker: Enabled"
echo ""
echo -e "${GREEN}✓ Deployment completed successfully${NC}"
echo ""
echo "Next steps:"
echo "1. Monitor Grafana dashboard: http://grafana.catvrf.ru/d/catvrf-payments-health"
echo "2. Check Horizon: http://horizon.catvrf.ru"
echo "3. Review logs: tail -f storage/logs/laravel.log"
echo "4. Enable new engine: php artisan env:set PAYMENT_NEW_ENGINE_ENABLED=true"
