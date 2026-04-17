# Payment Layer Production Rollout Monitoring Checklist

## Pre-Rollout Checklist

### Environment Configuration
- [ ] Payment gateway API keys configured in environment (YooKassa, Tinkoff)
- [ ] Redis connection verified and operational
- [ ] Prometheus metrics endpoint accessible
- [ ] Grafana dashboard imported and configured
- [ ] Horizon supervisors running with correct queue configuration
- [ ] Feature flag `PAYMENT_NEW_ENGINE_ENABLED` set to `false` initially

### Infrastructure Readiness
- [ ] Database backups completed
- [ ] Configuration backups created
- [ ] Rollback script tested in staging
- [ ] Monitoring alerts configured in Alertmanager
- [ ] Log aggregation (Loki/ELK) receiving payment logs
- [ ] Circuit breaker thresholds reviewed and adjusted

## During Rollout (First 24 Hours)

### Metrics to Monitor (Grafana Dashboard)

**Critical Metrics:**
- [ ] Payment success rate ≥ 99% (target: 99.5%+)
- [ ] Payment latency P95 ≤ 3 seconds (target: < 1s)
- [ ] Fraud block rate ≤ 2% (target: < 0.5%)
- [ ] Active circuit breakers = 0 (target: 0)
- [ ] Queue backlog (payment-fraud-check) < 100 (target: < 50)

**Secondary Metrics:**
- [ ] Payment attempts per minute (baseline: compare with legacy)
- [ ] Gateway success rate by provider (target: > 99%)
- [ ] Wallet balance distribution (check for anomalies)
- [ ] Payment latency distribution (P50, P95, P99)

### Health Checks
- [ ] Payment health check endpoint returning 200
- [ ] Horizon supervisors healthy
- [ ] Redis connection stable
- [ ] Database connection pool healthy
- [ ] No error spikes in logs

### Queue Monitoring
- [ ] payment-fraud-check queue processing normally
- [ ] No failed jobs in Horizon
- [ ] Job processing time < 30 seconds
- [ ] No queue backlog accumulation

### Log Monitoring
- [ ] No "Payment fraud check skipped - already processed" spam
- [ ] No "Circuit breaker open" errors
- [ ] No "Payment blocked due to fraud detection" spikes
- [ ] No gateway timeout errors
- [ ] No idempotency key conflicts

## Gradual Enablement Plan

### Phase 1: Shadow Mode (24-48 hours)
- Feature flag: `PAYMENT_NEW_ENGINE_ENABLED=false`
- Monitor: Legacy engine performance baseline
- Actions: Document baseline metrics

### Phase 2: 10% Traffic (24 hours)
- Feature flag: `PAYMENT_NEW_ENGINE_ENABLED=true` for 10% of users
- Monitor: Compare with baseline
- Rollback if: Success rate drops below 99%, latency > 3s, circuit breakers open

### Phase 3: 50% Traffic (24 hours)
- Feature flag: `PAYMENT_NEW_ENGINE_ENABLED=true` for 50% of users
- Monitor: Stability under load
- Rollback if: Any critical metric degrades

### Phase 4: 100% Traffic
- Feature flag: `PAYMENT_NEW_ENGINE_ENABLED=true` for all users
- Monitor: Full production load
- Rollback if: Critical failure

## Alert Thresholds

### Critical Alerts (Immediate Action Required)
- Payment success rate < 95% for 5 minutes
- Payment latency P95 > 10 seconds for 5 minutes
- Circuit breaker opens for any gateway
- Queue backlog > 1000 for 5 minutes
- Database connection errors > 1% for 5 minutes

### Warning Alerts (Investigate Within 1 Hour)
- Payment success rate < 98% for 15 minutes
- Payment latency P95 > 5 seconds for 15 minutes
- Fraud block rate > 2% for 30 minutes
- Queue backlog > 500 for 15 minutes
- Gateway error rate > 1% for 30 minutes

## Rollback Triggers

Immediate rollback if ANY of:
- Payment success rate drops below 95% for 5 minutes
- Circuit breaker opens for primary gateway
- Database deadlock errors spike
- Queue backlog > 1000 jobs
- Any data inconsistency detected

Investigate within 1 hour if:
- Payment success rate drops below 98%
- Latency increases by 50%
- Fraud block rate doubles
- Gateway error rate increases

## Post-Rollout Verification

### After 24 Hours
- [ ] All metrics within target ranges
- [ ] No critical alerts fired
- [ ] Queue backlog cleared
- [ ] Circuit breakers remain closed
- [ ] Customer complaints < baseline

### After 7 Days
- [ ] Stability metrics consistent
- [ ] No performance degradation
- [ ] Revenue impact neutral or positive
- [ ] Support tickets related to payments < baseline
- [ ] Fraud detection accuracy verified

## Escalation Contacts

| Role | Contact | Responsibility |
|------|---------|----------------|
| On-Call Engineer | [Phone] | Immediate rollback decisions |
| Tech Lead | [Phone/Slack] | Technical issues investigation |
| Product Manager | [Slack] | Business impact assessment |
| CTO | [Phone] | Critical decision making |

## Commands Reference

```bash
# Check payment health
php artisan payment:health-check

# Toggle feature flag
php artisan env:set PAYMENT_NEW_ENGINE_ENABLED=true

# Rollback
./scripts/rollback-payment-layer.sh

# Check queue status
php artisan queue:monitor

# View Horizon
php artisan horizon

# Clear cache
php artisan config:clear
php artisan cache:clear
```

## Documentation Links

- [Grafana Dashboard](http://grafana.catvrf.ru/d/catvrf-payments-health)
- [Horizon Dashboard](http://horizon.catvrf.ru)
- [Migration Plan](./PAYMENT_LAYER_MIGRATION_PLAN.md)
- [Architecture Documentation](./PAYMENT_LAYER_ARCHITECTURE.md)

## Notes

- Always rollback first, investigate later
- Document all incidents and decisions
- Keep rollback script tested and ready
- Monitor for at least 7 days before declaring success
- Have on-call engineer available during rollout
