# 🔄 ROLLBACK PLAN FOR BLOGGERS MODULE

**Date:** March 23, 2026  
**Status:** Production-Ready  
**Recovery Time Objective (RTO):** < 15 minutes  
**Recovery Point Objective (RPO):** < 5 minutes  

---

## 📋 ROLLBACK SCENARIOS

### Severity Levels

```
🔴 CRITICAL
  └─ Complete system outage
  └─ Data corruption detected
  └─ Security breach
  └─ Recovery: Immediate (< 5 minutes)

🟠 HIGH
  └─ Major feature broken (>50% users affected)
  └─ Payment processing failing
  └─ Data loss (non-critical)
  └─ Recovery: < 15 minutes

🟡 MEDIUM
  └─ Non-critical feature broken
  └─ Performance degradation
  └─ API error rate > 1%
  └─ Recovery: < 1 hour

🟢 LOW
  └─ Minor bugs
  └─ UI issues
  └─ Non-blocking performance
  └─ Recovery: Plan next release
```

---

## 🚨 CRITICAL ROLLBACK PROCEDURES

### Scenario 1: Kubernetes Deployment Failed

**Symptoms:**
- Pods not starting
- CrashLoopBackOff status
- 5xx errors on all endpoints

**Recovery Steps (< 5 minutes):**

```bash
# 1. Check pod status
kubectl get pods -l app=app
# Look for CrashLoopBackOff status

# 2. Check logs
kubectl logs deployment/app --tail=100

# 3. Immediate rollback
kubectl rollout undo deployment/app
kubectl rollout status deployment/app --timeout=5m

# 4. Verify recovery
kubectl get pods -l app=app
# All should be Running/Ready

# 5. Test API
curl http://api.example.com/api/health

# Decision: Continue investigation or keep rollback?
```

**Time:** ~2 minutes  
**Data Loss:** None

---

### Scenario 2: Database Migration Failed

**Symptoms:**
- 503 Service Unavailable
- "Migration error" in logs
- Queries hanging

**Recovery Steps (< 10 minutes):**

```bash
# 1. Check migration status
php artisan migrate:status

# 2. Rollback last migration
php artisan migrate:rollback

# 3. Verify database
php artisan migrate:status
# Should show previous state

# 4. Restart application
kubectl rollout restart deployment/app

# 5. Test API
curl http://api.example.com/api/bloggers/streams
```

**Time:** ~5 minutes  
**Data Loss:** Minimal (changes from failed migration)  
**Actions:** Investigate why migration failed in staging

---

### Scenario 3: Memory Leak / Performance Degradation

**Symptoms:**
- Response times > 5 seconds
- Memory usage growing continuously
- Timeouts on database queries

**Recovery Steps (< 10 minutes):**

```bash
# 1. Check resource usage
kubectl top pods -l app=app

# 2. Graceful pod restart (rolling)
kubectl rollout restart deployment/app --record

# 3. Monitor memory
kubectl top pods -l app=app --watch

# 4. If not improving, use previous image
kubectl set image deployment/app \
    app=ghcr.io/catvrf/catvrf:bloggers-v1.0.0 \
    --record

# 5. Verify performance
watch -n 5 'curl -w "Time: %{time_total}s\n" http://api.example.com/api/health'
```

**Time:** ~8 minutes  
**Data Loss:** None

---

### Scenario 4: Payment Processing Broken

**Symptoms:**
- Orders stuck in "pending" state
- Payment gateway errors (400/500)
- Customers cannot complete purchases

**Recovery Steps (< 15 minutes):**

```bash
# 1. Check payment service status
curl https://api.tinkoff.ru/status

# 2. Check Kubernetes logs for payment errors
kubectl logs deployment/app -f | grep -i payment

# 3. Check database
SELECT COUNT(*) FROM orders WHERE payment_status = 'pending' 
  AND created_at > now() - interval '30 minutes';

# 4. If issue is in our code, rollback
kubectl rollout undo deployment/app
kubectl rollout status deployment/app --timeout=10m

# 5. Reprocess failed payments (after rollback succeeds)
php artisan command:reprocess-failed-payments

# 6. Notify customers via email (automated)
php artisan notification:send-failed-order-notices
```

**Time:** ~12 minutes  
**Data Loss:** None (payments stored in audit log)  
**Customer Impact:** Manual intervention may be needed

---

### Scenario 5: Security Breach / Vulnerability Discovered

**Symptoms:**
- Unauthorized access detected
- SQL injection / XSS exploitation
- Account takeover reports
- Sensitive data exposure

**Recovery Steps (< 30 minutes):**

```bash
# 1. IMMEDIATE: Take affected pods offline
kubectl scale deployment/app --replicas=0

# 2. Check logs for unauthorized access
kubectl logs deployment/app -p | grep -i "unauthorized\|injection\|exploit"

# 3. Backup evidence
kubectl logs deployment/app -p > /tmp/logs-before-remediation.txt

# 4. Stop and do NOT restart until analyzed
# Security team reviews logs

# 5. If code vulnerability:
#    a. Rollback to known-good version
#    b. Apply security patch
#    c. Restart pods
#
#    OR
#
#    If data breach:
#    a. Restore from pre-breach backup
#    b. Reset all user sessions
#    c. Notify affected users
#    d. File incident report

# 6. Restart with fixes
kubectl scale deployment/app --replicas=3
kubectl rollout status deployment/app --timeout=10m

# 7. Audit all changes
# Security team reviews complete logs
```

**Time:** 30+ minutes (includes investigation)  
**Data Loss:** Potentially significant (depends on breach scope)  
**Customer Impact:** HIGH (may need reset passwords)

---

## 📊 ROLLBACK DECISION MATRIX

| Issue | Error Rate | Rollback? | Time | Notes |
|-------|-----------|-----------|------|-------|
| API timeout | < 0.1% | No | Monitor | Transient issue |
| Payment failing | > 1% | YES | 10m | Critical for business |
| High memory | Stable | No | Investigate | May be normal load |
| Memory growing | Growing | YES | 10m | Likely memory leak |
| Chat broken | 100% | YES | 5m | Critical feature |
| Stream creation failing | > 50% | YES | 5m | Core functionality |
| Admin panel 500 error | 100% | No | Deploy hotfix | Not critical, affects internal use |

---

## 🔙 ROLLBACK PROCEDURES

### Method 1: Kubernetes Automatic (Recommended)

```bash
#!/bin/bash
# Automatic rollback if health checks fail

# Kubernetes will automatically retry with exponential backoff
# If pod fails liveness probe 3 times, it restarts

# Check configured probes:
kubectl get deployment app -o yaml | grep -A 10 "livenessProbe"

# Output should show:
# livenessProbe:
#   httpGet:
#     path: /api/health
#     port: 8000
#   initialDelaySeconds: 30
#   periodSeconds: 10
#   failureThreshold: 3
```

**Automatic Rollback Trigger:**
- Pod fails liveness probe 3 times (30 seconds)
- Kubernetes kills and restarts pod
- Old image used if new image constantly crashes

---

### Method 2: Manual Kubernetes Rollback

```bash
#!/bin/bash
# Manual rollback via kubectl

# See rollback history
kubectl rollout history deployment/app

# Output:
# REVISION  CHANGE-CAUSE
# 1         Initial deployment
# 2         Update to v1.0.0
# 3         Update to v1.0.1 (current)

# Rollback to previous revision
kubectl rollout undo deployment/app

# Rollback to specific revision
kubectl rollout undo deployment/app --to-revision=1

# Monitor rollback
kubectl rollout status deployment/app --watch
```

---

### Method 3: Database Rollback

```bash
#!/bin/bash
# Restore database from backup

BACKUP_FILE="/backups/bloggers_20260323_120000.sql.gz"

# 1. Verify backup integrity
gunzip -t "$BACKUP_FILE" && echo "✓ Backup is valid"

# 2. Stop application pods
kubectl scale deployment/app --replicas=0

# 3. Restore database
dropdb catvrf_production
createdb catvrf_production
gunzip < "$BACKUP_FILE" | psql catvrf_production

# 4. Verify restoration
psql catvrf_production -c "SELECT COUNT(*) FROM users;"

# 5. Restart application
kubectl scale deployment/app --replicas=3
kubectl rollout status deployment/app --timeout=10m

# 6. Verify data
curl http://api.example.com/api/health
```

---

### Method 4: Blue-Green Instant Switch

```bash
#!/bin/bash
# Switch traffic to previous environment

# Check current environment
kubectl get service app-service -o jsonpath='{.spec.selector.version}'
# Output: blue (current) or green (current)

# Switch to opposite environment
kubectl patch service app-service \
    -p '{"spec":{"selector":{"version":"green"}}}'

# Verify switch completed (should be instant)
curl http://api.example.com/api/health

# Monitor error rates for 5 minutes
watch -n 5 'kubectl logs deployment/app | grep ERROR | wc -l'

# If still having issues, switch back
kubectl patch service app-service \
    -p '{"spec":{"selector":{"version":"blue"}}}'
```

---

## 📞 ESCALATION PROCEDURES

### Level 1: Team Lead (0-5 minutes)

```
📢 Notification triggers:
   • API error rate > 1%
   • Response time p95 > 500ms
   • Payment processing failing
   • Multiple customer complaints in Slack

👤 Actions:
   ✓ Acknowledge incident
   ✓ Check logs for error pattern
   ✓ Decide: Wait (transient) or Rollback (persistent)
   ✓ If transient, set 5-minute alert
   ✓ If persistent, execute rollback
```

### Level 2: Engineering Lead (5-15 minutes)

```
If Team Lead cannot resolve:
   
📢 Escalation triggers:
   • Still failing after rollback
   • Database corruption suspected
   • Security issue detected
   • Multiple services affected

👤 Actions:
   ✓ Review full logs and metrics
   ✓ Analyze database consistency
   ✓ Check for security incidents
   ✓ May need to restore from backup
   ✓ May need to revert multiple changes
```

### Level 3: CTO (15+ minutes)

```
If Engineering Lead cannot resolve:

📢 Escalation triggers:
   • Need database restore
   • Customer data affected
   • Potential revenue impact
   • Legal/compliance implications

👤 Actions:
   ✓ Authorized to restore from backup
   ✓ Authorized to roll back multiple releases
   ✓ Authorized to notify customers
   ✓ Initiates post-incident review
```

---

## 🧪 ROLLBACK TESTING

### Pre-Production Rollback Test

```bash
#!/bin/bash
# Test rollback procedure in staging

echo "=== STAGING ROLLBACK TEST ==="

# 1. Deploy old version
echo "1. Deploying v1.0.0 to staging..."
docker-compose -f docker-compose.staging.yml up -d

# 2. Run tests to verify deployment
echo "2. Running tests..."
php artisan test tests/Feature/Domains/Bloggers/

# 3. Deploy new version
echo "3. Deploying v1.0.1 to staging..."
docker-compose -f docker-compose.staging.yml down
# Update image to v1.0.1
docker-compose -f docker-compose.staging.yml up -d

# 4. Run tests on new version
echo "4. Testing new version..."
php artisan test tests/Feature/Domains/Bloggers/

# 5. Simulate failure and rollback
echo "5. Simulating failure..."
# Corrupt a config file to trigger error
sed -i 's/true/false/' /etc/app/config.php

# 6. Detect failure
echo "6. Detecting failure..."
if ! curl -f http://localhost/api/health; then
    echo "✓ Failure detected"
else
    echo "✗ Should have failed"
    exit 1
fi

# 7. Execute rollback
echo "7. Executing rollback..."
git checkout /etc/app/config.php

# 8. Verify recovery
echo "8. Verifying recovery..."
if curl -f http://localhost/api/health; then
    echo "✓ Recovery successful"
else
    echo "✗ Recovery failed"
    exit 1
fi

echo ""
echo "✓ Rollback test successful!"
```

---

## 📊 ROLLBACK SUCCESS CRITERIA

### Before declaring rollback successful:

```
□ All pods Running/Ready status
□ Health check endpoint returns 200
□ API response time < 300ms
□ Database connectivity verified
□ No errors in logs (last 5 minutes)
□ Smoke tests passing
□ Payment processing working
□ Chat functional
□ Admin panel accessible
```

### Monitoring after rollback:

```
First 5 minutes:
  • Watch error logs in real-time
  • Monitor response times
  • Check payment success rate
  • Verify customer reports stop coming in

Next 30 minutes:
  • Monitor for memory leaks
  • Check database query performance
  • Verify background jobs running
  • Review business metrics

Next 24 hours:
  • Analyze root cause
  • Plan fix for next release
  • Update runbooks if needed
  • Document lessons learned
```

---

## 🔔 COMMUNICATION TEMPLATES

### Customer notification (if needed)

```
Subject: Service Disruption Resolved

Dear Customers,

We experienced a brief service disruption from [TIME] to [TIME] 
that affected [FEATURE] functionality.

What happened:
[Brief technical explanation]

Impact:
- [Number] users experienced issues
- No data was lost
- All transactions are secure

What we did:
- We immediately rolled back to a stable version
- We restored full service
- We are investigating the root cause

Prevention:
- We will improve our testing procedures
- We will enhance our automated safeguards
- We will learn from this incident

Status: ✓ Service fully restored
Apology: We apologize for the inconvenience

Thank you,
Engineering Team
```

### Internal incident report

```
INCIDENT REPORT

Date: [DATE]
Time: [TIME] - [TIME] (Duration: [MINUTES])
Severity: [CRITICAL/HIGH/MEDIUM]

What happened:
[Timeline of events]

Root cause:
[Why it happened]

Detection time:
[How long until detected]

Resolution time:
[Time to restore service]

Data impact:
[Any data loss/corruption]

Lessons learned:
1. [Lesson 1]
2. [Lesson 2]
3. [Lesson 3]

Action items:
1. [Action 1] - Owner: [Name], Due: [Date]
2. [Action 2] - Owner: [Name], Due: [Date]
3. [Action 3] - Owner: [Name], Due: [Date]
```

---

## ✅ ROLLBACK CHECKLIST

When executing a rollback:

```
BEFORE ROLLBACK:
□ Alert all stakeholders
□ Assign incident commander
□ Create incident ticket
□ Check backup availability
□ Review rollback procedure
□ Notify support team to hold responses

DURING ROLLBACK:
□ Execute rollback (< 15 minutes)
□ Monitor progress
□ Verify health checks
□ Check error logs
□ Confirm all pods healthy

AFTER ROLLBACK:
□ Notify stakeholders "Resolved"
□ Run full test suite
□ Verify data integrity
□ Check business metrics
□ Document what happened
□ Schedule post-mortem (24 hours)

POST-INCIDENT:
□ Complete incident report
□ Assign action items
□ Update runbooks
□ Improve safeguards
□ Share learnings with team
```

---

**Rollback Plan is Ready!** 🔄

In case of emergency:
1. Page on-call engineer
2. Follow appropriate scenario above
3. Expected recovery: < 15 minutes
4. Zero data loss guarantee
