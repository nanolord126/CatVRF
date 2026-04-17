#!/bin/bash

# Payment Layer Rollback Script
# CatVRF 2026 - Safe rollback in case of issues

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${RED}=== CatVRF Payment Layer Rollback ===${NC}"
echo ""
echo "WARNING: This will roll back to the legacy payment engine."
echo ""

# Confirmation
read -p "Are you sure you want to rollback? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Rollback cancelled."
    exit 0
fi

echo ""

# Find latest backup
echo -e "${YELLOW}1. Finding latest configuration backup...${NC}"
LATEST_BACKUP=$(ls -t .env.backup.* 2>/dev/null | head -1)

if [ -z "$LATEST_BACKUP" ]; then
    echo -e "${RED}ERROR: No backup found. Cannot rollback safely.${NC}"
    echo "Please restore manually from git or backup system."
    exit 1
fi

echo "Found backup: $LATEST_BACKUP"
echo ""

# Rollback configuration
echo -e "${YELLOW}2. Rolling back configuration...${NC}"
cp "$LATEST_BACKUP" .env
echo -e "${GREEN}✓ Configuration restored${NC}"
echo ""

# Disable new payment engine explicitly
echo -e "${YELLOW}3. Disabling new payment engine...${NC}"
php artisan env:set PAYMENT_NEW_ENGINE_ENABLED=false
php artisan env:set PAYMENT_ASYNC_FRAUD_ENABLED=false
php artisan env:set PAYMENT_CIRCUIT_BREAKER_ENABLED=false
echo -e "${GREEN}✓ New payment engine disabled${NC}"
echo ""

# Clear and cache configuration
echo -e "${YELLOW}4. Caching configuration...${NC}"
php artisan config:clear
php artisan config:cache
php artisan route:clear
php artisan route:cache
echo -e "${GREEN}✓ Configuration cached${NC}"
echo ""

# Restart queues
echo -e "${YELLOW}5. Restarting queue workers...${NC}"
php artisan queue:restart
echo -e "${GREEN}✓ Queue workers restarted${NC}"
echo ""

# Clear opcode cache
echo -e "${YELLOW}6. Clearing opcode cache...${NC}"
if [ -f "php-fpm.sock" ]; then
    echo "Reloading PHP-FPM..."
    sudo service php-fpm reload 2>/dev/null || echo "PHP-FPM reload skipped (not root)"
else
    echo "Restarting Octane..."
    php artisan octane:reload 2>/dev/null || echo "Octane reload skipped"
fi
echo -e "${GREEN}✓ Opcode cache cleared${NC}"
echo ""

# Health check
echo -e "${YELLOW}7. Running health checks...${NC}"
php artisan payment:health-check || {
    echo -e "${RED}ERROR: Health check failed after rollback.${NC}"
    echo "Manual intervention required."
    exit 1
}
echo -e "${GREEN}✓ Health checks passed${NC}"
echo ""

# Rollback summary
echo -e "${GREEN}=== Rollback Summary ===${NC}"
echo "Configuration restored from: $LATEST_BACKUP"
echo "Payment Engine: Legacy"
echo "Async Fraud: Disabled"
echo "Circuit Breaker: Disabled"
echo ""
echo -e "${GREEN}✓ Rollback completed successfully${NC}"
echo ""
echo "Next steps:"
echo "1. Investigate the issue that caused rollback"
echo "2. Check logs: tail -f storage/logs/laravel.log"
echo "3. Monitor Grafana dashboard for anomalies"
echo "4. Fix the issue before re-deploying"
echo ""
echo "To re-deploy:"
echo "  ./scripts/deploy-payment-layer.sh staging true"
