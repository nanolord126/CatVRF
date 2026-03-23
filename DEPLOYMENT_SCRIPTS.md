# 🚀 DEPLOYMENT SCRIPTS FOR BLOGGERS MODULE

**Date:** March 23, 2026  
**Environment:** Production Ready  

---

## 📋 TABLE OF CONTENTS

1. [Pre-Deployment](#pre-deployment)
2. [Staging Deployment](#staging-deployment)
3. [Production Deployment](#production-deployment)
4. [Verification Scripts](#verification-scripts)
5. [Rollback Scripts](#rollback-scripts)

---

## 🔧 PRE-DEPLOYMENT

### 1. Database Backup Script

```bash
#!/bin/bash
# backup-database.sh
# Creates timestamped backup of production database

set -e

DB_NAME=${DATABASE_NAME:-catvrf_production}
DB_USER=${DATABASE_USER:-postgres}
BACKUP_DIR="${HOME}/database-backups"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_FILE="${BACKUP_DIR}/bloggers_${TIMESTAMP}.sql.gz"

mkdir -p "$BACKUP_DIR"

echo "Backing up database: $DB_NAME"
pg_dump -U "$DB_USER" "$DB_NAME" | gzip > "$BACKUP_FILE"

echo "✓ Backup created: $BACKUP_FILE"
echo "✓ Size: $(du -h "$BACKUP_FILE" | cut -f1)"

# Keep only last 7 backups
find "$BACKUP_DIR" -name "bloggers_*.sql.gz" -type f -mtime +7 -delete
echo "✓ Old backups cleaned (kept 7 most recent)"
```

### 2. Pre-Flight Checklist Script

```bash
#!/bin/bash
# preflight-check.sh
# Verifies system readiness before deployment

echo "=== BLOGGERS MODULE PRE-FLIGHT CHECK ==="
echo ""

CHECKS_PASSED=0
CHECKS_FAILED=0

check_service() {
    local service=$1
    local port=$2
    if nc -z localhost $port 2>/dev/null; then
        echo "✓ $service is running (port $port)"
        ((CHECKS_PASSED++))
    else
        echo "✗ $service is NOT running (port $port)"
        ((CHECKS_FAILED++))
    fi
}

check_command() {
    local cmd=$1
    if command -v $cmd &> /dev/null; then
        echo "✓ $cmd is installed"
        ((CHECKS_PASSED++))
    else
        echo "✗ $cmd is NOT installed"
        ((CHECKS_FAILED++))
    fi
}

check_env_var() {
    local var=$1
    if [[ -v $var ]]; then
        echo "✓ Environment variable $var is set"
        ((CHECKS_PASSED++))
    else
        echo "✗ Environment variable $var is NOT set"
        ((CHECKS_FAILED++))
    fi
}

echo "Checking services..."
check_service "PostgreSQL" 5432
check_service "Redis" 6379
check_service "Nginx" 80

echo ""
echo "Checking commands..."
check_command "docker"
check_command "kubectl"
check_command "php"
check_command "composer"

echo ""
echo "Checking environment variables..."
check_env_var "DATABASE_URL"
check_env_var "REDIS_URL"
check_env_var "SENTRY_DSN"

echo ""
echo "=== RESULTS ==="
echo "Passed: $CHECKS_PASSED"
echo "Failed: $CHECKS_FAILED"

if [ $CHECKS_FAILED -eq 0 ]; then
    echo "✓ All checks passed! Ready to deploy."
    exit 0
else
    echo "✗ Some checks failed. Fix issues before deploying."
    exit 1
fi
```

---

## 🚀 STAGING DEPLOYMENT

### 1. Docker Compose Staging Deploy

```bash
#!/bin/bash
# deploy-staging.sh
# Deploys Bloggers Module to staging environment

set -e

ENVIRONMENT="staging"
DOCKER_IMAGE="catvrf:bloggers-latest"
DOCKER_REGISTRY="ghcr.io/catvrf"

echo "=== DEPLOYING TO $ENVIRONMENT ==="
echo ""

# 1. Backup current database
echo "1. Creating database backup..."
./backup-database.sh

# 2. Build Docker image
echo ""
echo "2. Building Docker image..."
docker build \
    --target app \
    --tag "${DOCKER_REGISTRY}/${DOCKER_IMAGE}" \
    --build-arg APP_ENV=staging \
    .

# 3. Push to registry
echo ""
echo "3. Pushing image to registry..."
docker push "${DOCKER_REGISTRY}/${DOCKER_IMAGE}"

# 4. Deploy with docker-compose
echo ""
echo "4. Deploying with docker-compose..."
docker-compose -f docker-compose.staging.yml down
docker-compose -f docker-compose.staging.yml up -d

# 5. Run migrations
echo ""
echo "5. Running database migrations..."
docker-compose -f docker-compose.staging.yml exec -T app \
    php artisan migrate --force

# 6. Seed test data
echo ""
echo "6. Seeding test data..."
docker-compose -f docker-compose.staging.yml exec -T app \
    php artisan db:seed --class=BloggerSeeder

# 7. Clear caches
echo ""
echo "7. Clearing caches..."
docker-compose -f docker-compose.staging.yml exec -T app \
    php artisan config:cache
docker-compose -f docker-compose.staging.yml exec -T app \
    php artisan view:cache
docker-compose -f docker-compose.staging.yml exec -T app \
    php artisan route:cache

# 8. Run tests
echo ""
echo "8. Running tests..."
docker-compose -f docker-compose.staging.yml exec -T app \
    php artisan test tests/Feature/Domains/Bloggers/ \
    --parallel --processes=4

echo ""
echo "✓ Staging deployment complete!"
echo ""
echo "Access at: http://staging.example.com"
echo "Admin at: http://staging.example.com/admin"
```

### 2. Manual Verification Script

```bash
#!/bin/bash
# verify-staging.sh
# Verifies staging deployment health

echo "=== VERIFYING STAGING DEPLOYMENT ==="
echo ""

STAGING_URL="http://staging.example.com"
FAILED=0

verify_endpoint() {
    local endpoint=$1
    local expected_code=$2
    
    response=$(curl -s -o /dev/null -w "%{http_code}" "${STAGING_URL}${endpoint}")
    
    if [ "$response" = "$expected_code" ]; then
        echo "✓ GET ${endpoint} → $response"
    else
        echo "✗ GET ${endpoint} → $response (expected $expected_code)"
        FAILED=$((FAILED + 1))
    fi
}

echo "Testing API endpoints..."
verify_endpoint "/api/bloggers/streams" "200"
verify_endpoint "/api/bloggers/streams/invalid" "404"
verify_endpoint "/api/health" "200"

echo ""
echo "Testing admin panel..."
verify_endpoint "/admin/login" "200"

echo ""
if [ $FAILED -eq 0 ]; then
    echo "✓ All verification checks passed!"
    exit 0
else
    echo "✗ $FAILED checks failed"
    exit 1
fi
```

---

## 📦 PRODUCTION DEPLOYMENT

### 1. Kubernetes Deployment

```bash
#!/bin/bash
# deploy-kubernetes.sh
# Deploys to Kubernetes production cluster

set -e

ENVIRONMENT="production"
NAMESPACE="default"
DEPLOYMENT="app"
IMAGE_TAG=${1:-"latest"}
DOCKER_IMAGE="ghcr.io/catvrf/catvrf:bloggers-${IMAGE_TAG}"

echo "=== KUBERNETES PRODUCTION DEPLOYMENT ==="
echo "Deploying: $DOCKER_IMAGE"
echo ""

# 1. Pre-deployment checks
echo "1. Running pre-flight checks..."
./preflight-check.sh || exit 1

# 2. Create database backup
echo ""
echo "2. Creating database backup..."
./backup-database.sh

# 3. Verify image exists
echo ""
echo "3. Verifying Docker image..."
if ! docker pull "$DOCKER_IMAGE"; then
    echo "✗ Failed to pull image: $DOCKER_IMAGE"
    exit 1
fi

# 4. Update Kubernetes deployment
echo ""
echo "4. Updating Kubernetes deployment..."
kubectl set image \
    deployment/$DEPLOYMENT \
    app=$DOCKER_IMAGE \
    -n $NAMESPACE \
    --record

# 5. Wait for rollout
echo ""
echo "5. Waiting for rollout to complete..."
kubectl rollout status deployment/$DEPLOYMENT \
    -n $NAMESPACE \
    --timeout=10m

if [ $? -ne 0 ]; then
    echo "✗ Deployment failed!"
    echo "Rolling back..."
    kubectl rollout undo deployment/$DEPLOYMENT -n $NAMESPACE
    exit 1
fi

# 6. Run database migrations
echo ""
echo "6. Running database migrations..."
kubectl exec -i deployment/$DEPLOYMENT \
    -n $NAMESPACE \
    -- php artisan migrate --force

# 7. Clear application caches
echo ""
echo "7. Clearing application caches..."
kubectl exec -i deployment/$DEPLOYMENT \
    -n $NAMESPACE \
    -- php artisan optimize:clear

# 8. Verify deployment
echo ""
echo "8. Verifying deployment..."
sleep 5

POD=$(kubectl get pods -n $NAMESPACE -l app=$DEPLOYMENT -o jsonpath='{.items[0].metadata.name}')
if kubectl exec -i $POD -n $NAMESPACE -- curl -f http://localhost/api/health > /dev/null 2>&1; then
    echo "✓ Health check passed"
else
    echo "✗ Health check failed"
    kubectl logs $POD -n $NAMESPACE
    exit 1
fi

echo ""
echo "✓ Production deployment complete!"
echo "✓ All pods healthy"
echo ""

# 9. Post-deployment notification
echo "Sending deployment notification..."
curl -X POST https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK \
    -H 'Content-Type: application/json' \
    -d "{
        \"text\": \"✓ Bloggers Module deployed to production\",
        \"blocks\": [
            {
                \"type\": \"section\",
                \"text\": {
                    \"type\": \"mrkdwn\",
                    \"text\": \"*Bloggers Module Deployment*\nVersion: $IMAGE_TAG\nStatus: ✓ Complete\nTime: $(date)\"
                }
            }
        ]
    }"
```

### 2. Blue-Green Deployment Strategy

```bash
#!/bin/bash
# deploy-blue-green.sh
# Implements blue-green deployment for zero downtime

set -e

echo "=== BLUE-GREEN DEPLOYMENT ==="
echo ""

# Get current active deployment (blue)
CURRENT=$(kubectl get service app-service -o jsonpath='{.spec.selector.version}')
if [ "$CURRENT" = "blue" ]; then
    CURRENT="blue"
    NEXT="green"
else
    CURRENT="green"
    NEXT="blue"
fi

echo "Current (Active): $CURRENT"
echo "Next (Standby): $NEXT"
echo ""

# 1. Deploy to inactive slot (green/blue)
echo "1. Deploying to $NEXT environment..."
kubectl set image \
    deployment/app-$NEXT \
    app=catvrf:bloggers-${1:-latest} \
    --record

# 2. Wait for health checks
echo ""
echo "2. Waiting for $NEXT to be healthy..."
kubectl rollout status deployment/app-$NEXT --timeout=10m

# 3. Run smoke tests against new environment
echo ""
echo "3. Running smoke tests against $NEXT..."
NEXT_POD=$(kubectl get pods -l app=app-$NEXT -o jsonpath='{.items[0].metadata.name}')
NEXT_IP=$(kubectl get pod $NEXT_POD -o jsonpath='{.status.podIP}')

# Wait for pod to be ready
sleep 5

if curl -f http://$NEXT_IP/api/health > /dev/null 2>&1; then
    echo "✓ Health check passed"
else
    echo "✗ Health check failed for $NEXT"
    exit 1
fi

# 4. Switch traffic
echo ""
echo "4. Switching traffic from $CURRENT to $NEXT..."
kubectl patch service app-service -p '{"spec":{"selector":{"version":"'$NEXT'"}}}'

echo "✓ Traffic switched to $NEXT"

# 5. Final verification
echo ""
echo "5. Verifying traffic switch..."
sleep 10

SERVICE_IP=$(kubectl get service app-service -o jsonpath='{.status.loadBalancer.ingress[0].ip}')
if curl -f http://$SERVICE_IP/api/health > /dev/null 2>&1; then
    echo "✓ Live environment verified"
    echo ""
    echo "✓ Blue-Green deployment complete!"
    echo "  Previous ($CURRENT) is now standby"
    echo "  Current ($NEXT) is now live"
else
    echo "✗ Verification failed!"
    echo "Rolling back..."
    kubectl patch service app-service -p '{"spec":{"selector":{"version":"'$CURRENT'"}}}'
    exit 1
fi
```

---

## ✅ VERIFICATION SCRIPTS

### 1. Comprehensive Health Check

```bash
#!/bin/bash
# health-check.sh
# Comprehensive system health verification

echo "=== SYSTEM HEALTH CHECK ==="
echo ""

CHECKS=0
PASSED=0
FAILED=0

run_check() {
    local name=$1
    local command=$2
    
    CHECKS=$((CHECKS + 1))
    
    if eval "$command" > /dev/null 2>&1; then
        echo "✓ $name"
        PASSED=$((PASSED + 1))
    else
        echo "✗ $name"
        FAILED=$((FAILED + 1))
    fi
}

echo "Infrastructure..."
run_check "PostgreSQL connectivity" "psql -h localhost -U postgres -c 'SELECT 1' > /dev/null"
run_check "Redis connectivity" "redis-cli -h localhost ping"
run_check "Elasticsearch connectivity" "curl -s http://localhost:9200/_cluster/health"

echo ""
echo "Application..."
run_check "API health endpoint" "curl -f http://localhost/api/health"
run_check "Admin panel accessible" "curl -f http://localhost/admin/login"
run_check "Database migrations current" "php artisan migrate:status | grep -q 'No pending migrations'"

echo ""
echo "Services..."
run_check "Sentry configured" "curl -s https://sentry.io/api/0/projects/ | grep -q 'results'"
run_check "Queue worker running" "pgrep -f 'queue:work' > /dev/null"
run_check "Schedule running" "pgrep -f 'schedule:run' > /dev/null"

echo ""
echo "=== RESULTS ==="
echo "Total: $CHECKS | Passed: $PASSED | Failed: $FAILED"

if [ $FAILED -eq 0 ]; then
    echo "✓ System is healthy!"
    exit 0
else
    echo "✗ Some checks failed"
    exit 1
fi
```

### 2. API Test Suite

```bash
#!/bin/bash
# test-api.sh
# Tests all 34 API endpoints

echo "=== BLOGGERS API TEST SUITE ==="
echo ""

BASE_URL="${1:-http://localhost}"
TOKEN=$(curl -s -X POST "$BASE_URL/api/login" \
    -H "Content-Type: application/json" \
    -d '{"email":"test@example.com","password":"password"}' \
    | jq -r '.data.token')

TOTAL=0
PASSED=0
FAILED=0

test_endpoint() {
    local method=$1
    local endpoint=$2
    local expected_code=$3
    
    TOTAL=$((TOTAL + 1))
    
    response=$(curl -s -o /dev/null -w "%{http_code}" \
        -X "$method" \
        -H "Authorization: Bearer $TOKEN" \
        "$BASE_URL$endpoint")
    
    if [ "$response" = "$expected_code" ]; then
        echo "✓ $method $endpoint → $response"
        PASSED=$((PASSED + 1))
    else
        echo "✗ $method $endpoint → $response (expected $expected_code)"
        FAILED=$((FAILED + 1))
    fi
}

echo "Streams..."
test_endpoint "GET" "/api/bloggers/streams" "200"
test_endpoint "POST" "/api/bloggers/streams" "201"

echo ""
echo "Products..."
test_endpoint "GET" "/api/bloggers/streams/1/products" "200"

echo ""
echo "Orders..."
test_endpoint "GET" "/api/bloggers/orders" "200"

echo ""
echo "Chat..."
test_endpoint "GET" "/api/bloggers/streams/1/chat" "200"

echo ""
echo "Statistics..."
test_endpoint "GET" "/api/bloggers/statistics/blogger/me" "200"

echo ""
echo "=== RESULTS ==="
echo "Total: $TOTAL | Passed: $PASSED | Failed: $FAILED"

if [ $FAILED -eq 0 ]; then
    echo "✓ All API tests passed!"
    exit 0
else
    echo "✗ Some tests failed"
    exit 1
fi
```

---

## 🔄 ROLLBACK SCRIPTS

### 1. Emergency Rollback

```bash
#!/bin/bash
# rollback.sh
# Emergency rollback to previous version

set -e

echo "=== EMERGENCY ROLLBACK ==="
echo ""

DEPLOYMENT=${1:-"app"}
NAMESPACE=${2:-"default"}

# Get previous revision
PREVIOUS_REVISION=$(kubectl rollout history deployment/$DEPLOYMENT -n $NAMESPACE | tail -2 | head -1 | awk '{print $1}')

if [ -z "$PREVIOUS_REVISION" ]; then
    echo "✗ No previous revision found"
    exit 1
fi

echo "Rolling back to revision: $PREVIOUS_REVISION"
echo ""

# Confirm rollback
read -p "Are you sure you want to rollback? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Rollback cancelled"
    exit 1
fi

# Execute rollback
kubectl rollout undo deployment/$DEPLOYMENT -n $NAMESPACE \
    --to-revision=$PREVIOUS_REVISION

# Wait for rollout
echo ""
echo "Waiting for rollback to complete..."
kubectl rollout status deployment/$DEPLOYMENT -n $NAMESPACE --timeout=10m

# Verify
echo ""
echo "Verifying rollback..."
POD=$(kubectl get pods -n $NAMESPACE -l app=$DEPLOYMENT -o jsonpath='{.items[0].metadata.name}')
if kubectl exec -i $POD -n $NAMESPACE -- curl -f http://localhost/api/health > /dev/null 2>&1; then
    echo "✓ Rollback successful"
else
    echo "✗ Rollback verification failed"
    exit 1
fi

echo ""
echo "✓ Successfully rolled back to revision $PREVIOUS_REVISION"
```

### 2. Database Restore Script

```bash
#!/bin/bash
# restore-database.sh
# Restores database from backup

set -e

BACKUP_FILE=$1

if [ -z "$BACKUP_FILE" ]; then
    echo "Usage: ./restore-database.sh <backup-file>"
    echo ""
    echo "Available backups:"
    ls -lh ~/database-backups/bloggers_*.sql.gz | awk '{print $NF}'
    exit 1
fi

if [ ! -f "$BACKUP_FILE" ]; then
    echo "✗ Backup file not found: $BACKUP_FILE"
    exit 1
fi

DB_NAME=${DATABASE_NAME:-catvrf_production}
DB_USER=${DATABASE_USER:-postgres}

echo "=== DATABASE RESTORE ==="
echo "Restoring from: $BACKUP_FILE"
echo "Database: $DB_NAME"
echo ""

read -p "Are you sure? This will overwrite all data. (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "Restore cancelled"
    exit 1
fi

echo ""
echo "Dropping existing database..."
dropdb -U "$DB_USER" "$DB_NAME" || true

echo "Creating new database..."
createdb -U "$DB_USER" "$DB_NAME"

echo "Restoring backup..."
gunzip < "$BACKUP_FILE" | psql -U "$DB_USER" "$DB_NAME"

echo ""
echo "✓ Database restore complete!"
```

---

## 🎯 QUICK REFERENCE

### Deploy to Staging
```bash
./deploy-staging.sh
```

### Deploy to Production
```bash
./deploy-kubernetes.sh v1.0.0
```

### Blue-Green Deployment
```bash
./deploy-blue-green.sh v1.0.0
```

### Verify Health
```bash
./health-check.sh
```

### Test API
```bash
./test-api.sh http://localhost
```

### Emergency Rollback
```bash
./rollback.sh app default
```

### Restore Database
```bash
./restore-database.sh ~/database-backups/bloggers_20260323_120000.sql.gz
```

---

## ⚙️ CONFIGURATION

Each script requires environment variables:

```bash
# .env.deployment
export DATABASE_NAME="catvrf_production"
export DATABASE_USER="postgres"
export DOCKER_REGISTRY="ghcr.io/catvrf"
export KUBERNETES_NAMESPACE="default"
export SLACK_WEBHOOK_URL="https://hooks.slack.com/..."
```

Source before running:
```bash
source .env.deployment
./deploy-kubernetes.sh
```

---

**Ready to deploy!** 🚀
