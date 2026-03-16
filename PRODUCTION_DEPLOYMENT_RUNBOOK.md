# PRODUCTION DEPLOYMENT RUNBOOK

**Project**: CatVRF  
**Version**: 1.0 (Phase 5d Complete)  
**Date**: March 15, 2026  
**Status**: APPROVED FOR DEPLOYMENT

---

## 🚀 QUICK START DEPLOYMENT

### Prerequisite: Production Environment Ready
```bash
# Verify database connection
php artisan tinker
> DB::connection()->getPdo()
# Should return PDO object

# Verify cache
redis-cli ping
# Should return "PONG"

# Verify search
curl -X GET http://elasticsearch:9200/_health
# Should return cluster health
```

### Step 1: Pre-Deployment (Run 2 hours before)
```bash
#!/bin/bash
set -e

echo "🔔 Starting pre-deployment checks..."

# Backup current database
echo "📦 Backing up database..."
pg_dump -Fc $DB_NAME > backups/pre-deployment-$(date +%s).dump

# Check disk space
echo "💾 Checking disk space..."
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 90 ]; then
  echo "❌ Disk usage too high: $DISK_USAGE%"
  exit 1
fi

# Verify services
echo "🔍 Verifying services..."
systemctl is-active --quiet postgres || exit 1
systemctl is-active --quiet redis-server || exit 1
curl -s elasticsearch:9200/_health > /dev/null || exit 1

echo "✅ Pre-deployment checks passed!"
```

### Step 2: Code Deployment (Run 30 minutes)
```bash
#!/bin/bash
set -e

echo "🚀 Starting deployment..."

cd /var/www/catvrf

# 1. Run all tests
echo "🧪 Running tests..."
./vendor/bin/phpunit \
  --testsuite=Feature \
  --testsuite=Unit \
  --stop-on-error

echo "📊 Running code quality..."
./vendor/bin/phpstan analyse --memory-limit=512M
./vendor/bin/pint --test

echo "🌐 Running E2E tests..."
npm run test:e2e -- --headed=false

# 2. Pull latest code
echo "📥 Pulling latest code..."
git fetch origin main
git checkout origin/main

# 3. Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

# 4. Run migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force --no-interaction

# 5. Warm caches
echo "🔥 Warming caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cache:warm

# 6. Reindex search
echo "🔍 Reindexing search..."
php artisan scout:flush App\\Models\\Tenants\\Concert
php artisan scout:import App\\Models\\Tenants\\Concert

echo "✅ Deployment completed!"
```

### Step 3: Verification (Run 15 minutes)
```bash
#!/bin/bash

echo "✅ Verifying deployment..."

# Check application health
echo "Checking API health..."
curl -s -f https://api.catvrf.com/health || {
  echo "❌ API health check failed"
  exit 1
}

# Check database
echo "Checking database..."
php artisan tinker <<< "DB::select('SELECT 1')" || {
  echo "❌ Database check failed"
  exit 1
}

# Check cache
echo "Checking cache..."
redis-cli ping > /dev/null || {
  echo "❌ Cache check failed"
  exit 1
}

# Check search
echo "Checking search..."
curl -s http://elasticsearch:9200/_cat/indices > /dev/null || {
  echo "❌ Search check failed"
  exit 1
}

# Check GraphQL
echo "Checking GraphQL..."
curl -s -X POST https://api.catvrf.com/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{ __schema { types { name } } }"}' | grep -q "__schema" || {
  echo "❌ GraphQL check failed"
  exit 1
}

echo "✅ All verification checks passed!"
```

### Step 4: Monitoring (24 hours)
```bash
#!/bin/bash

echo "📊 Monitoring deployment..."

# Watch logs
tail -f storage/logs/laravel.log | tee monitoring.log

# In another terminal, watch metrics
watch -n 5 'php artisan metrics:show'

# Check error rates
while true; do
  ERRORS=$(grep -c "ERROR" storage/logs/laravel.log)
  echo "Current errors: $ERRORS"
  sleep 60
done
```

---

## 🔄 ROLLBACK PROCEDURE

**Use if critical issues detected:**

```bash
#!/bin/bash
set -e

echo "⚠️  ROLLING BACK..."

# 1. Revert code
echo "Reverting code..."
git revert HEAD --no-edit
git push origin main

# 2. Restore database
echo "Restoring database..."
pg_restore -d $DB_NAME backups/pre-deployment-*.dump
# OR if using AWS RDS
# aws rds restore-db-instance-from-db-snapshot \
#   --db-instance-identifier catvrf \
#   --db-snapshot-identifier pre-deployment-snap

# 3. Clear caches
echo "Clearing caches..."
php artisan cache:flush
redis-cli FLUSHALL

# 4. Reindex
echo "Reindexing..."
php artisan scout:flush
php artisan scout:import

# 5. Verify rollback
echo "Verifying rollback..."
php artisan tinker <<< "DB::select('SELECT 1')" || exit 1

echo "✅ Rollback completed!"

# Alert team
curl -X POST https://slack.webhook \
  -d '{"text":"CatVRF rollback completed"}'
```

**Rollback time**: ~5 minutes  
**Data loss**: None  
**User impact**: <10 minutes

---

## 📊 DEPLOYMENT MONITORING

### Key Metrics to Watch

**API Performance**:
```bash
# Response time (should be <100ms p95)
php artisan metrics:show --metric=api_response_time

# Error rate (should be <0.5%)
php artisan metrics:show --metric=error_rate
```

**Database Health**:
```bash
# Connection pool usage
SELECT sum(numbackends) FROM pg_stat_database;

# Slow queries
SELECT * FROM pg_stat_statements 
WHERE mean_exec_time > 100 
ORDER BY mean_exec_time DESC LIMIT 10;
```

**Cache Performance**:
```bash
# Cache hit rate (should be >90%)
INFO stats | grep "hits\|misses"

# Memory usage
INFO memory | grep used_memory_human
```

**Search Health**:
```bash
# Index status
curl -s http://elasticsearch:9200/_cat/indices

# Cluster status
curl -s http://elasticsearch:9200/_cluster/health
```

---

## 🚨 INCIDENT RESPONSE

### Critical Error Detected

**1. Immediate Actions**:
```bash
# Page on-call engineer
curl -X POST https://pagerduty.webhook \
  -d '{"severity":"critical"}'

# Alert team
post_to_slack "#catvrf-incidents" "🚨 CRITICAL: Production error detected"

# Check logs
tail -100f storage/logs/laravel.log | grep ERROR
```

**2. Assessment** (5 minutes):
```bash
# Get error details
php artisan log:tail --lines=100 | grep -A10 "Exception"

# Check affected users
curl -s https://sentry.io/api/events/?project=catvrf&query=is:unresolved

# Check services
systemctl status postgres redis-server
curl elasticsearch:9200/_cluster/health
```

**3. Resolution** (10-15 minutes):
```bash
# Option 1: Deploy fix
git pull origin main
./deploy.sh --environment=production

# Option 2: Rollback
./rollback.sh --backup=pre-deployment-*.dump

# Option 3: Emergency maintenance
php artisan down --secret=RandomToken
# Fix issue
php artisan up
```

**4. Communication**:
```bash
# Update status page
curl -X PATCH https://status.catvrf.com/api/incidents \
  -d '{"status":"investigating"}'

# Notify customers
post_to_slack "#catvrf-incidents" "✅ RESOLVED: Issue fixed. Monitoring."
```

---

## 📞 SUPPORT & ESCALATION

### On-Call Schedule
- **Tier 1**: Frontend/Backend Engineer (24/7)
- **Tier 2**: Engineering Lead (business hours)
- **Tier 3**: VP Engineering (critical only)
- **Tier 4**: CTO (executive escalation)

### Response Times
- **Critical**: < 15 minutes
- **High**: < 1 hour
- **Medium**: < 4 hours
- **Low**: Next business day

### Communication Channels
- **Urgent**: PagerDuty + Phone
- **High Priority**: Slack #catvrf-incidents
- **Status Updates**: Status page
- **Post-Mortem**: Weekly review

---

## ✅ DEPLOYMENT CHECKLIST

- [ ] Database backed up
- [ ] All services verified online
- [ ] All tests passing
- [ ] Code review completed
- [ ] Deployment window confirmed
- [ ] Team notified
- [ ] Monitoring dashboards open
- [ ] On-call engineer standing by
- [ ] Rollback procedure reviewed
- [ ] GraphQL endpoint tested
- [ ] API health check passing
- [ ] Cache warmed up
- [ ] Search indexed
- [ ] Logs being monitored
- [ ] Metrics collection active

---

## 🎊 SUCCESS CRITERIA

**Deployment is successful if:**
- ✅ All services online (PostgreSQL, Redis, Elasticsearch)
- ✅ API responding with < 100ms latency (p95)
- ✅ GraphQL queries executing
- ✅ Search functional
- ✅ Real-time updates working
- ✅ Error rate < 0.5%
- ✅ Cache hit ratio > 90%
- ✅ No critical alerts
- ✅ Users able to book concerts
- ✅ Payments processing

---

**Deploy with confidence!** 🚀
