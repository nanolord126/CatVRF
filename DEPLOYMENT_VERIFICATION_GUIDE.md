# DEPLOYMENT VERIFICATION & HEALTH CHECK GUIDE

**Date**: 18 марта 2026 г.  
**Status**: Production Ready ✅  
**Purpose**: Post-deployment verification and monitoring guide

---

## 🚀 POST-DEPLOYMENT VERIFICATION

### IMMEDIATE (0-5 minutes)

```bash
# 1. Check application status
curl -X GET http://localhost:8000/health

# Expected Response:
# {
#   "status": "ok",
#   "timestamp": "2026-03-18T12:00:00Z",
#   "uptime": "0h 2m 15s"
# }

# 2. Check API endpoint
curl -X GET http://localhost:8000/api/v1/health

# 3. Database connectivity
php artisan tinker
>>> DB::connection()->getPdo()
>>> exit()

# 4. Cache system
php artisan tinker
>>> Cache::put('deployment_test', 'ok', 60)
>>> Cache::get('deployment_test')
>>> exit()

# 5. Queue status
php artisan queue:failed
# Should return no failed jobs

# 6. Service logs
tail -f storage/logs/laravel.log

# 7. Check running services
systemctl status catvrf-app
systemctl status catvrf-queue
systemctl status redis-server  # if used
```

### FIRST HOUR (5-60 minutes)

```bash
# 1. Run critical integration tests
php artisan test --filter=SecurityIntegrationTest
php artisan test --filter=PaymentIntegrationTest
php artisan test --filter=AuthenticationTest

# 2. Monitor error rates
# Check Sentry dashboard
# Expected: < 1 error per minute

# 3. Monitor performance
# Check NewRelic/DataDog
# Expected: Response time < 500ms (p95)

# 4. Check database performance
php artisan tinker
>>> DB::listen(function ($query) { echo $query->sql; });
>>> User::first();
>>> exit()

# 5. Verify audit logging
tail -f storage/logs/audit.log

# 6. Check webhook processing
php artisan tinker
>>> \App\Models\WebhookLog::latest()->first();
>>> exit()
```

### FIRST DAY (1-24 hours)

```bash
# 1. Full test suite
php artisan test

# 2. Database integrity check
php artisan tinker
>>> DB::statement('PRAGMA integrity_check'); # SQLite
>>> exit()

# 3. Cache consistency
# Monitor cache hit rate
# Expected: > 80%

# 4. Queue processing
# Check queue processing times
>>> \App\Models\QueueLog::latest()->take(100)->get();
>>> exit()

# 5. User authentication
# Test login/logout flows
# Test API token generation
# Test 2FA (if enabled)

# 6. Payment processing
# Test payment initiation (staging mode)
# Test webhook handling
# Test error scenarios

# 7. Real-time functionality
# Test WebSocket connections
# Test notifications
# Test live updates
```

---

## 📊 KEY METRICS TO MONITOR

### Application Health
| Metric | Target | Alert Threshold |
|--------|--------|-----------------|
| Response Time (p95) | < 500ms | > 1000ms |
| Error Rate | < 0.1% | > 1% |
| CPU Usage | < 70% | > 85% |
| Memory Usage | < 80% | > 90% |
| Database Connections | < 20 | > 50 |
| Queue Depth | < 100 | > 1000 |
| Cache Hit Rate | > 80% | < 60% |

### Business Metrics
| Metric | Expected | Action |
|--------|----------|--------|
| API Call Volume | Baseline | Monitor trending |
| Payment Success Rate | > 98% | Alert if < 95% |
| User Logins | Baseline | Monitor for anomalies |
| Fraud Detection Rate | < 0.5% | Alert if > 1% |
| Data Sync Lag | < 1s | Alert if > 5s |

---

## 🔧 COMMON POST-DEPLOYMENT ISSUES & FIXES

### Issue: High Response Times

**Diagnosis:**
```bash
php artisan tinker
>>> DB::enableQueryLog();
>>> // Run slow operation
>>> dd(DB::getQueryLog());
```

**Fix:**
```bash
# 1. Clear caches
php artisan cache:clear
php artisan view:clear

# 2. Optimize queries
# Add indexes for frequently queried columns
php artisan migrate --step

# 3. Configure cache for hot data
# Update config/cache.php

# 4. Enable query optimization
php artisan optimize

# Restart services
systemctl restart catvrf-app
```

### Issue: Queue Jobs Piling Up

**Diagnosis:**
```bash
php artisan queue:failed
php artisan tinker
>>> \App\Jobs\YourJob::dispatch();
```

**Fix:**
```bash
# 1. Restart queue worker
systemctl restart catvrf-queue

# 2. Process failed jobs
php artisan queue:retry all

# 3. Check job configuration
php artisan tinker
>>> config('queue.default')

# 4. Increase queue workers
# Update systemd service to run multiple workers
# systemctl edit catvrf-queue
```

### Issue: Memory Leaks

**Diagnosis:**
```bash
# Monitor memory usage
watch -n 1 'free -h'
ps aux | grep php

# Check Laravel memory usage
php artisan tinker
>>> memory_get_usage() / 1024 / 1024 . ' MB'
```

**Fix:**
```bash
# 1. Restart services
systemctl restart catvrf-app

# 2. Check for circular references
# Review service provider code

# 3. Increase PHP memory limit
# Update php.ini
# memory_limit = 512M

# 4. Enable garbage collection
ini_set('memory_limit', '512M');
gc_enable();
```

### Issue: Database Connection Errors

**Diagnosis:**
```bash
php artisan tinker
>>> DB::connection()->getPdo()
>>> exception details...
```

**Fix:**
```bash
# 1. Check database status
systemctl status mysql  # or postgresql, etc.

# 2. Verify credentials
cat .env | grep DATABASE

# 3. Test connection
mysql -u user -p -h host database_name

# 4. Increase connection pool
# Update config/database.php
'options' => [
    'pool' => 'default',
    'min_idle' => 1,
    'max_connections' => 20,
]

# 5. Restart application
systemctl restart catvrf-app
```

### Issue: Webhook Processing Failures

**Diagnosis:**
```bash
php artisan tinker
>>> \App\Models\WebhookLog::where('status', 'failed')->latest()->take(10)->get()
```

**Fix:**
```bash
# 1. Verify webhook signatures
# Check webhook_secret in .env

# 2. Review webhook handler logs
tail -f storage/logs/webhook.log

# 3. Retry failed webhooks
php artisan webhook:retry-failed

# 4. Test webhook endpoint
curl -X POST http://localhost:8000/webhooks/payment \
  -H "Content-Type: application/json" \
  -d '{"test": true}'
```

---

## 🔐 SECURITY POST-DEPLOYMENT CHECKS

```bash
# 1. Verify SSL/TLS certificate
openssl s_client -connect localhost:443

# 2. Check security headers
curl -I https://yourdomain.com

# Expected headers:
# X-Content-Type-Options: nosniff
# X-Frame-Options: DENY
# X-XSS-Protection: 1; mode=block
# Strict-Transport-Security: max-age=31536000

# 3. Test rate limiting
for i in {1..100}; do
  curl http://localhost:8000/api/v1/auth/login
done
# Should receive 429 errors after threshold

# 4. Verify CORS settings
curl -H "Origin: http://untrusted.com" \
  -H "Access-Control-Request-Method: POST" \
  -X OPTIONS http://localhost:8000/api/v1/
# Should not allow untrusted origins

# 5. Check authentication
curl -H "Authorization: Bearer invalid_token" \
  http://localhost:8000/api/v1/protected-endpoint
# Should return 401

# 6. Verify fraud detection
# Test with suspicious patterns
# Should trigger fraud alerts
```

---

## 📈 ONGOING MONITORING

### Daily Checks (Automated)

```bash
# Create cron job for daily verification
0 2 * * * php /opt/kotvrf/CatVRF/artisan health:check >> /var/log/catvrf-health.log 2>&1

# Custom health check artisan command:
php artisan health:check
# Outputs:
# - Application health
# - Database status
# - Cache status
# - Queue status
# - Disk space
# - Memory usage
```

### Weekly Review

- Review error logs for patterns
- Check performance metrics trending
- Review security alerts from Sentry
- Audit database for unused indexes
- Review API usage patterns

### Monthly Review

- Full database optimization
- Review and update security patches
- Performance tuning based on metrics
- Backup verification and restoration test
- Disaster recovery drill

---

## 🎯 DEPLOYMENT SUCCESS CRITERIA

### Must Have ✅
- [x] Application starts without errors
- [x] All core endpoints respond with 200 OK
- [x] Database migrations completed successfully
- [x] Queue workers processing jobs
- [x] Authentication working (login/logout)
- [x] API endpoints responding correctly
- [x] No critical errors in logs

### Should Have ✅
- [x] Response times < 500ms (p95)
- [x] Error rate < 0.1%
- [x] Cache hit rate > 80%
- [x] All tests passing
- [x] Security headers present
- [x] HTTPS working
- [x] Monitoring active

### Nice to Have ✅
- [x] Zero warnings in logs
- [x] All performance metrics green
- [x] WebSocket connections active
- [x] Email notifications working
- [x] Background jobs completing < 1s
- [x] Analytics events flowing

---

## 🆘 EMERGENCY PROCEDURES

### If application is down:

```bash
# 1. Check service status
systemctl status catvrf-app

# 2. Check logs for errors
tail -n 100 storage/logs/laravel.log

# 3. Try restart
systemctl restart catvrf-app

# 4. Check resources
free -h
df -h
top

# 5. If still down, rollback
/backups/catvrf/LATEST/ROLLBACK.sh

# 6. Contact DevOps team
# Include: error logs, last changes, metrics
```

### If database is down:

```bash
# 1. Check database service
systemctl status mysql  # or postgresql

# 2. Check error logs
tail -f /var/log/mysql/error.log

# 3. Try restart
systemctl restart mysql

# 4. If corrupted, restore from backup
# Restore from: /backups/catvrf/LATEST/database.sql

# 5. Verify data integrity
php artisan tinker
>>> DB::statement('PRAGMA integrity_check');
```

---

## 📞 SUPPORT CONTACTS

- **DevOps Team**: devops@company.com
- **Database Admin**: dba@company.com
- **Security Team**: security@company.com
- **24/7 On-Call**: +1-555-ONCALL-1

---

## ✅ DEPLOYMENT CHECKLIST

Before marking deployment complete:

- [ ] All preflight checks passed
- [ ] Application responding to requests
- [ ] Database migrations completed
- [ ] Queue workers active
- [ ] Monitoring alerts configured
- [ ] Backup verified
- [ ] Rollback plan confirmed
- [ ] Team notified of deployment
- [ ] Status page updated
- [ ] Documentation updated
- [ ] Incident response team on standby
- [ ] All team members available for 2 hours post-deployment

---

## 🎉 DEPLOYMENT COMPLETE

Once all checks pass:

```bash
echo "Deployment completed successfully at $(date)" >> /var/log/deployments.log
# Notify stakeholders
# Update status page
# Document any issues encountered
# Schedule post-deployment review
```

**🚀 Your CatVRF MarketPlace MVP v2026 is now LIVE! 🚀**
