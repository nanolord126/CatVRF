# CatVRF Payments Vertical - Production Checklist

**Version:** 1.0  
**Date:** 2026-04-29  
**Status:** Ready for Production Review

---

## 1. Infrastructure & Monitoring

### 1.1 Infrastructure Setup
- [ ] Redis cluster configured for circuit breaker and smart routing metrics
- [ ] ClickHouse configured for payment analytics and BigData tracking
- [ ] Prometheus + Grafana dashboards for payment metrics
- [ ] Alertmanager rules configured for payment failures
- [ ] Log aggregation (ELK/Loki) for payment logs
- [ ] Database replicas for read scaling
- [ ] Connection pooling configured for MySQL
- [ ] Queue system (Redis/Redis Cluster) for async jobs
- [ ] Octane/Swoole configured for high-performance PHP

### 1.2 Monitoring Metrics
- [ ] Payment success rate by gateway
- [ ] Payment latency (p50, p95, p99) by gateway
- [ ] Circuit breaker state per gateway
- [ ] Smart routing decision distribution
- [ ] Escrow hold count and age
- [ ] Outbox message delivery rate
- [ ] Recurring subscription billing success rate
- [ ] Payout batch processing time
- [ ] Fraud detection block rate
- [ ] Wallet balance transaction rate

### 1.3 Alerts
- [ ] Payment success rate < 95% (critical)
- [ ] Circuit breaker open for any gateway (critical)
- [ ] Payment latency p99 > 5s (warning)
- [ ] Outbox message backlog > 1000 (warning)
- [ ] Escrow hold age > 7 days (info)
- [ ] Recurring subscription failure rate > 10% (warning)
- [ ] Wallet balance discrepancy detected (critical)
- [ ] Gateway API error rate > 5% (warning)

---

## 2. Security & Compliance

### 2.1 PCI DSS Compliance
- [ ] Cardholder data never stored in application (only tokens)
- [ ] TLS 1.3 enforced for all payment endpoints
- [ ] PCI-scanning environment isolated from production
- [ ] Access to payment logs restricted to authorized personnel
- [ ] Card PAN masked in logs (show only last 4 digits)
- [ ] CVV never stored or logged
- [ ] Encryption at rest for sensitive payment data
- [ ] Regular penetration testing of payment endpoints
- [ ] Network segmentation for payment infrastructure
- [ ] File integrity monitoring (FIM) for payment code

### 2.2 152-ФЗ Compliance (Russian Federal Law)
- [ ] Personal data anonymization before sending to external AI/ML
- [ ] Audit logging for all payment operations
- [ ] Data localization: Russian citizen data stored in Russia
- [ ] Consent management for payment data processing
- [ ] Right to data deletion implemented
- [ ] Data breach notification procedures
- [ ] Regular security audits and documentation
- [ ] DPO (Data Protection Officer) access to payment audit logs

### 2.3 Security Measures
- [ ] API rate limiting per user/IP
- [ ] Request signing for webhook callbacks
- [ ] IP whitelisting for admin payment operations
- [ ] MFA required for sensitive payment operations
- [ ] Encrypted backups of payment data
- [ ] Key rotation procedures for gateway credentials
- [ ] Secrets management (HashiCorp Vault/AWS Secrets Manager)
- [ ] Regular security patches and updates

---

## 3. Gateway Configuration

### 3.1 Tinkoff Acquiring (Primary B2C)
- [ ] Terminal Key configured
- [ ] Secret Key stored securely
- [ ] Webhook endpoint configured and verified
- [ ] 3DS integration tested
- [ ] Installments (Tinkoff Installments) configured
- [ ] Recurring payments enabled
- [ ] QrCode for SBP configured
- [ ] Fiscalization (54-ФЗ) receipts configured
- [ ] Test credentials for staging
- [ ] Production credentials approved

### 3.2 Tochka Bank (Primary B2B)
- [ ] Client ID and Secret configured
- [ ] OAuth2 token refresh mechanism
- [ ] API access permissions verified
- [ ] Mass payout functionality tested
- [ ] Bank transfer integration verified
- [ ] Account statement retrieval tested
- [ ] Currency exchange rates configured
- [ ] Test environment access
- [ ] Production environment approved

### 3.3 Sber Acquiring (Backup)
- [ ] Terminal credentials configured
- [ ] SBP integration verified
- [ ] SberPay integration tested
- [ ] Fallback routing rules configured
- [ ] Webhook verification tested

### 3.4 SBP (via Tinkoff/Sber)
- [ ] QR code generation tested
- [ ] QR code expiration handling
- [ ] SBP bank identification working
- [ ] Callback handling verified

---

## 4. Database & Data Integrity

### 4.1 Database Setup
- [ ] All migrations run successfully
- [ ] Indexes created for performance
- [ ] Foreign key constraints enabled
- [ ] Database backups configured (daily)
- [ ] Point-in-time recovery configured
- [ ] Read replicas configured
- [ ] Connection pooling optimized
- [ ] Slow query monitoring enabled

### 4.2 Data Consistency
- [ ] Wallet balance reconciliation job scheduled
- [ ] Payment transaction integrity checks
- [ ] Escrow hold expiration job scheduled
- [ ] Outbox message cleanup job scheduled
- [ ] Duplicate payment detection
- [ ] Idempotency key uniqueness enforced
- [ ] Transaction isolation level verified (READ COMMITTED)

### 4.3 ClickHouse BigData
- [ ] Payment events streaming to ClickHouse
- [ ] CLV/RFM calculation jobs configured
- [ ] Real-time dashboards working
- [ ] Data retention policy configured
- [ ] Partitioning strategy implemented

---

## 5. Application Configuration

### 5.1 Environment Variables
- [ ] TINKOFF_TERMINAL_KEY set
- [ ] TINKOFF_SECRET_KEY set
- [ ] TOCHKA_CLIENT_ID set
- [ ] TOCHKA_CLIENT_SECRET set
- [ ] SBER_TERMINAL_KEY set
- [ ] SBER_SECRET_KEY set
- [ ] PAYMENT_WEBHOOK_URL set
- [ ] PAYMENT_WEBHOOK_SECRET set
- [ ] REDIS_* variables configured
- [ ] DATABASE_* variables configured

### 5.2 Laravel Configuration
- [ ] `config/payment.php` configured with all gateways
- [ ] `config/queue.php` configured for async jobs
- [ ] `config/cache.php` configured with Redis
- [ ] `config/logging.php` payment channel configured
- [ ] Service providers registered
- [ ] Facade alias configured

### 5.3 Feature Flags
- [ ] Smart routing enabled/disabled flag
- [ ] Circuit breaker enabled flag
- [ ] Escrow auto-release enabled flag
- [ ] Recurring billing enabled flag
- [ ] BigData tracking enabled flag

---

## 6. Testing & Quality Assurance

### 6.1 Unit Tests
- [ ] MoneyVO tests (arithmetic, comparison, formatting)
- [ ] PaymentStatusVO tests (transitions, validation)
- [ ] PaymentMethodVO tests (validation, helpers)
- [ ] SmartRoutingService tests (scoring, selection)
- [ ] EscrowService tests (hold, release, cancel)
- [ ] OutboxService tests (delivery, retry, cleanup)

### 6.2 Integration Tests
- [ ] Payment flow with Tinkoff gateway
- [ ] Payment flow with Tochka gateway
- [ ] Payment flow with Sber gateway
- [ ] Smart routing integration
- [ ] Escrow hold and release flow
- [ ] Split payment and payout flow
- [ ] Recurring subscription billing
- [ ] Outbox message delivery
- [ ] Fraud check integration
- [ ] Audit logging integration

### 6.3 Load Testing
- [ ] 1000 RPS payment processing
- [ ] 500 RPS concurrent escrow operations
- [ ] 1000 RPS outbox message processing
- [ ] 100 RPS payout batch processing
- [ ] Database connection pool stress test
- [ ] Redis cluster stress test
- [ ] Circuit breaker behavior under load

### 6.4 Chaos Testing
- [ ] Gateway failure simulation
- [ ] Database failure simulation
- [ ] Redis failure simulation
- [ ] Network partition testing
- [ ] Circuit breaker recovery testing

---

## 7. Operational Procedures

### 7.1 Runbooks
- [ ] Payment failure investigation runbook
- [ ] Gateway switchover procedure
- [ ] Escrow hold manual release procedure
- [ ] Wallet balance reconciliation procedure
- [ ] Outbox message retry procedure
- [ ] Recurring subscription failure handling
- [ ] Payout batch failure recovery
- [ ] Security incident response procedure

### 7.2 Scheduled Jobs
- [ ] Escrow expiration check (hourly)
- [ ] Outbox message processing (every 5 minutes)
- [ ] Recurring subscription billing (daily)
- [ ] Wallet balance reconciliation (daily)
- [ ] Outbox message cleanup (weekly)
- [ ] Payment metrics aggregation (hourly)
- [ ] Smart routing metrics cleanup (daily)

### 7.3 Backup & Recovery
- [ ] Database backup verification
- [ ] Redis backup verification
- [ ] ClickHouse backup verification
- [ ] Disaster recovery test completed
- [ ] RTO (Recovery Time Objective) < 1 hour
- [ ] RPO (Recovery Point Objective) < 5 minutes

---

## 8. Documentation

### 8.1 Technical Documentation
- [ ] Architecture overview documented
- [ ] API documentation (OpenAPI/Swagger)
- [ ] Database schema documented
- [ ] Gateway integration guides
- [ ] Smart routing rules documented
- [ ] Escrow flow documented
- [ ] Recurring billing flow documented

### 8.2 Operational Documentation
- [ ] Deployment guide
- [ ] Configuration guide
- [ ] Monitoring guide
- [ ] Troubleshooting guide
- [ ] Runbooks documented
- [ ] Onboarding guide for ops team

### 8.3 Business Documentation
- [ ] Payment methods supported
- [ ] Commission structure documented
- [ ] Payout rules documented
- [ ] Refund policy documented
- [ ] SLA with payment providers

---

## 9. Performance Targets

### 9.1 Latency Targets
- [ ] Payment initiation < 500ms (p95)
- [ ] Payment capture < 300ms (p95)
- [ ] Payment refund < 500ms (p95)
- [ ] Escrow hold < 200ms (p95)
- [ ] Escrow release < 200ms (p95)
- [ ] Smart routing decision < 50ms (p95)

### 9.2 Throughput Targets
- [ ] 5000+ payments per second
- [ ] 10000+ wallet operations per second
- [ ] 5000+ escrow operations per second
- [ ] 10000+ outbox messages per second

### 9.3 Availability Targets
- [ ] 99.95% uptime for payment endpoints
- [ ] 99.99% uptime for wallet operations
- [ ] < 5 minutes downtime per month
- [ ] < 1 second failover time for gateway switches

---

## 10. Go-Live Checklist

### 10.1 Pre-Launch
- [ ] All tests passing (unit, integration, load)
- [ ] Security audit completed
- [ ] Penetration testing completed
- [ ] Compliance review completed (PCI, 152-ФЗ)
- [ ] Gateway contracts signed
- [ ] Bank accounts verified
- [ ] Monitoring dashboards configured
- [ ] Alerts configured and tested
- [ ] Runbooks reviewed by team
- [ ] On-call rotation established

### 10.2 Launch Day
- [ ] Database backups created
- [ ] Feature flags set to safe defaults
- [ ] Circuit breakers enabled
- [ ] Smart routing in observation mode
- [ ] Team on standby
- [ ] Communication channels open
- [ ] Rollback plan ready
- [ ] First payment processed successfully
- [ ] First payout processed successfully
- [ ] First escrow hold/release successful

### 10.3 Post-Launch (First 24h)
- [ ] Monitor payment success rate
- [ ] Monitor gateway latency
- [ ] Monitor error rates
- [ ] Review audit logs
- [ ] Check wallet balances
- [ ] Verify outbox delivery
- [ ] Review smart routing decisions
- [ ] Check for fraudulent transactions
- [ ] Team retrospective
- [ ] Adjust thresholds if needed

---

## 11. Ongoing Maintenance

### 11.1 Regular Tasks
- [ ] Weekly: Review payment metrics
- [ ] Weekly: Review gateway performance
- [ ] Monthly: Review fraud patterns
- [ ] Monthly: Review commission accuracy
- [ ] Quarterly: Security audit
- [ ] Quarterly: Compliance review
- [ ] Quarterly: Performance tuning
- [ ] Annually: Gateway contract review

### 11.2 Continuous Improvement
- [ ] A/B test smart routing rules
- [ ] Optimize payment flows based on data
- [ ] Improve fraud detection models
- [ ] Add new payment methods as needed
- [ ] Enhance monitoring and alerting
- [ ] Update documentation regularly

---

## Sign-off

- [ ] **Tech Lead:** ___________________ Date: _______
- [ ] **Security Lead:** _________________ Date: _______
- [ ] **DevOps Lead:** __________________ Date: _______
- [ ] **Product Owner:** ________________ Date: _______
- [ ] **Compliance Officer:** ____________ Date: _______

**Final Approval:** ___________________ Date: _______
