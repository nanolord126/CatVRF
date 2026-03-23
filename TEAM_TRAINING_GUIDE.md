# 👥 TEAM TRAINING GUIDE FOR BLOGGERS MODULE

**Date:** March 23, 2026  
**Target Audience:** Development, DevOps, QA, Support teams  
**Training Duration:** 3-4 hours (divided into sessions)  

---

## 📚 TRAINING MODULES

### Module 1: Architecture Overview (30 minutes)

**Target:** All team members  
**Objective:** Understand system design and components

#### Topics
1. **System Architecture**
   - Layered design (API → Service → Models → Database)
   - 9 database tables
   - 34 API endpoints
   - 5 Filament resources

2. **Core Features**
   - Live streaming (WebRTC + HLS)
   - Live commerce (orders, payments)
   - NFT gifts (TON blockchain)
   - Real-time chat
   - Verification & moderation

3. **Integration Points**
   - Wallet system (earnings crediting)
   - Payment gateway (Tinkoff, Tochka, Sber, SBP)
   - Fraud detection (ML-based scoring)
   - Recommendations
   - Notifications

#### Deliverables
- Architecture diagram presentation
- 5-minute Q&A session
- Recorded training video

---

### Module 2: Database & Models (45 minutes)

**Target:** Backend engineers, DBAs  
**Objective:** Understand data structure and relationships

#### Topics
1. **Database Schema**
   - 9 tables overview
   - Primary keys & foreign keys
   - Indexes & performance
   - Migrations

2. **Models & Relationships**
   - BloggerProfile (has many streams)
   - Stream (has many products, orders, chat)
   - Order (belongs to stream, user)
   - NftGift (has one blockchain transaction)
   - StreamChatMessage (belongs to stream)

3. **Scoping & Tenancy**
   - tenant_id global scope
   - business_group_id support
   - Multi-tenancy enforcement
   - Query isolation

#### Hands-On Activity
```php
// Run in php artisan tinker:

// 1. Query streams for current tenant
$streams = Stream::all();

// 2. Get orders for a stream
$orders = $streams->first()->orders;

// 3. Calculate earnings
$earnings = $orders->sum('amount') * 0.86;

// 4. Check chat messages
$chat = $streams->first()->chatMessages;

// 5. View NFT gifts
$gifts = $streams->first()->gifts;
```

#### Deliverables
- Database schema diagram
- Model relationship diagram
- SQL query examples
- Hands-on practice session

---

### Module 3: API Endpoints (45 minutes)

**Target:** Backend engineers, API testers  
**Objective:** Learn to use all 34 API endpoints

#### Topics
1. **Endpoint Categories**
   - Streams (7): CRUD + start/end
   - Products (5): List, add, remove, pin
   - Orders (6): Create, confirm, refund, list
   - Chat (4): Send, get, delete, pin
   - Gifts (6): Send, status, upgrade, metadata
   - Statistics (6): Blogger, stream, leaderboard
   - Additional (9): Verification, profile, dashboard

2. **Authentication**
   - Bearer token format
   - Token generation
   - Rate limiting
   - Error handling

3. **Request/Response Format**
   - Standard structure
   - Pagination
   - Correlation IDs
   - Error codes

#### Hands-On Activity
```bash
# 1. Test authentication
curl -X POST http://localhost/api/login \
  -d '{"email":"test@example.com","password":"pass"}'

# 2. Get streams
curl -H "Authorization: Bearer TOKEN" \
  http://localhost/api/bloggers/streams

# 3. Create stream
curl -X POST http://localhost/api/bloggers/streams \
  -H "Authorization: Bearer TOKEN" \
  -d '{"title":"Test","category":"tech"}'

# 4. Get stream details
curl http://localhost/api/bloggers/streams/1

# 5. Start streaming
curl -X POST http://localhost/api/bloggers/streams/1/start \
  -H "Authorization: Bearer TOKEN"
```

#### Deliverables
- API endpoint cheat sheet
- Postman collection (all 34 endpoints)
- CURL command examples
- Error code reference

---

### Module 4: Admin Panel (45 minutes)

**Target:** Frontend engineers, admin users  
**Objective:** Master Filament admin interface

#### Topics
1. **Filament Resources**
   - Blogger Profiles resource
   - Streams resource
   - Orders resource
   - NFT Gifts resource

2. **Common Operations**
   - List with filtering/sorting
   - Create/Edit forms
   - Bulk actions
   - Inline editing
   - Custom actions

3. **Moderation Workflows**
   - Verify blogger (4-stage process)
   - Flag stream (content violation)
   - Suspend user (rule violation)
   - Ban user (repeat offender)
   - Review appeals

4. **Reports & Export**
   - Export to CSV/Excel
   - Custom filters
   - Date range selection
   - Bulk operations

#### Hands-On Activity
```
1. Access admin: http://localhost/admin
2. Navigate to Blogger Profiles
3. Filter: verification_status = "pending"
4. Select a profile
5. Review uploaded documents
6. Click "Verify" action
7. Confirm verification

Then practice:
- Flag a stream (reason: inappropriate content)
- Suspend a user account
- Export orders to CSV
- Bulk edit moderation status
```

#### Deliverables
- Admin panel walkthrough video
- Moderation decision tree
- Quick reference guide (common tasks)
- Screen recordings for each resource

---

### Module 5: Testing & Quality Assurance (1 hour)

**Target:** QA engineers, test automation engineers  
**Objective:** Learn testing strategy and run test suites

#### Topics
1. **Test Structure**
   - Unit tests (42 methods)
   - Integration tests (5 workflows)
   - API tests (34 endpoints)
   - Security tests (12 scenarios)
   - Load tests (8 scenarios)

2. **Test Execution**
   - Running full suite: `php artisan test`
   - Running specific test: `php artisan test --filter=testName`
   - Coverage report: `php artisan test --coverage`
   - Parallel execution: `--parallel --processes=4`

3. **Common Test Patterns**
   - Arrange-Act-Assert
   - Setup/Teardown with RefreshDatabase
   - Using factories for test data
   - Mocking external services

4. **Debugging Tests**
   - Reading test output
   - Using dd() for debugging
   - Checking database state
   - Reviewing logs

#### Hands-On Activity
```bash
# 1. Run all tests
php artisan test tests/Feature/Domains/Bloggers/

# 2. Run single test file
php artisan test tests/Feature/Domains/Bloggers/Http/StreamControllerTest.php

# 3. Run with specific method
php artisan test --filter=test_create_stream

# 4. Run with coverage
php artisan test --coverage
# Check if coverage > 90%

# 5. Run in parallel
php artisan test --parallel --processes=4
```

#### Deliverables
- Test pyramid diagram
- Test file structure overview
- Test writing guidelines
- Performance testing guide

---

### Module 6: Monitoring & Alerts (45 minutes)

**Target:** DevOps, SRE, on-call engineers  
**Objective:** Monitor system health and respond to alerts

#### Topics
1. **Monitoring Stack**
   - Prometheus (metrics collection)
   - Grafana (visualization)
   - Sentry (error tracking)
   - PagerDuty (on-call management)

2. **Key Metrics**
   - Error rate (should be < 0.1%)
   - Response time p95 (should be < 200ms)
   - Active streams (gauge)
   - Orders per minute (throughput)
   - Payment success rate (should be > 99%)

3. **Alert Rules**
   - High error rate
   - High response time
   - Payment failures
   - NFT queue backlog
   - Database connection pool exhaustion

4. **Responding to Alerts**
   - Check logs in Sentry
   - View metrics in Grafana
   - Check system status (kubectl)
   - Execute rollback if needed

#### Hands-On Activity
```
1. Open Grafana dashboard: http://localhost:3000
2. Navigate to Bloggers Module - Overview
3. Check active streams gauge
4. View error rate graph
5. Check response time distribution
6. View business metrics (earnings, orders)

Then practice:
- Create custom dashboard
- Set alert rule (error rate > 1%)
- Test alert by generating errors
- View alert in PagerDuty
- Acknowledge & resolve alert
```

#### Deliverables
- Grafana dashboard walkthrough (recorded)
- Alert response runbook
- Metrics interpretation guide
- On-call checklist

---

### Module 7: Deployment & Rollback (1 hour)

**Target:** DevOps, deployment engineers  
**Objective:** Deploy and rollback confidently

#### Topics
1. **Deployment Process**
   - Pre-flight checks
   - Database backups
   - Docker image building
   - Kubernetes update
   - Migration execution
   - Health verification

2. **Deployment Strategies**
   - Rolling deployment (default)
   - Blue-green deployment (zero downtime)
   - Canary deployment (gradual rollout)

3. **Rollback Procedures**
   - Automatic rollback (liveness probe failure)
   - Manual rollback (kubectl command)
   - Database rollback (restore from backup)
   - Blue-green instant switch

4. **Common Issues**
   - Migration failures
   - Image pull errors
   - Configuration errors
   - Database connection timeouts

#### Hands-On Activity
```bash
# 1. Pre-flight check
./preflight-check.sh

# 2. Deploy to staging
./deploy-staging.sh

# 3. Verify deployment
./verify-staging.sh

# 4. Deploy to production
./deploy-kubernetes.sh v1.0.0

# 5. Monitor rollout
kubectl rollout status deployment/app --watch

# 6. Practice rollback (on staging)
kubectl rollout undo deployment/app
kubectl rollout history deployment/app
```

#### Deliverables
- Deployment script walkthrough
- Kubernetes commands reference
- Troubleshooting guide
- Rollback decision flowchart

---

### Module 8: Security & Incident Response (1 hour)

**Target:** Security engineers, incident commanders  
**Objective:** Understand security measures and handle incidents

#### Topics
1. **Security Features**
   - All 12 vulnerabilities fixed
   - Fraud detection (ML-based)
   - Rate limiting per user
   - CSRF protection
   - XSS protection
   - SQL injection prevention
   - Idempotency keys (duplicate prevention)

2. **Security Incident Response**
   - Detection (logs in Sentry)
   - Triage (severity assessment)
   - Mitigation (disable feature, rollback)
   - Investigation (root cause analysis)
   - Remediation (patch & deploy)
   - Post-mortem (lessons learned)

3. **Compliance & Auditing**
   - Audit logging (all operations)
   - GDPR compliance (data anonymization)
   - Data retention (3 years)
   - User consent (for notifications)

4. **Security Best Practices**
   - Never commit secrets to git
   - Use .env for sensitive data
   - Rotate credentials regularly
   - Review logs for anomalies
   - Report security issues privately

#### Hands-On Activity
```
1. Review Sentry security events
2. Check audit logs in database
3. Run security tests: `php artisan test tests/Feature/Domains/Bloggers/SecurityTest.php`
4. Try SQL injection attack (should be blocked)
5. Try XSS attack in chat (should be sanitized)
6. Test rate limiting (send 100 requests, check 429)
7. Review incident response checklist

Practice scenario:
- Simulate payment fraud detection
- Check fraud score in logs
- Review transaction for legitimacy
- Decide: Allow or Block
```

#### Deliverables
- Security incident playbook
- Fraud detection guide
- Audit logging reference
- Security checklist for deployments

---

## 📅 TRAINING SCHEDULE

### Day 1: Fundamentals (3 hours)
```
09:00-09:30  Module 1: Architecture Overview (all)
09:30-10:15  Module 2: Database & Models (backend)
10:15-10:30  Break
10:30-11:15  Module 3: API Endpoints (backend)
11:15-12:00  Module 4: Admin Panel (frontend)
```

### Day 2: Operations (3 hours)
```
09:00-09:45  Module 5: Testing & QA (QA team)
09:45-10:30  Module 6: Monitoring & Alerts (DevOps)
10:30-10:45  Break
10:45-11:45  Module 7: Deployment & Rollback (DevOps)
11:45-12:30  Module 8: Security & Incidents (Security)
```

---

## 👥 TEAM ASSIGNMENTS

### Backend Team
- [ ] Complete Module 1 (Architecture)
- [ ] Complete Module 2 (Database)
- [ ] Complete Module 3 (API)
- [ ] Complete Module 5 (Testing)
- [ ] Complete Module 8 (Security)

### Frontend Team
- [ ] Complete Module 1 (Architecture)
- [ ] Complete Module 4 (Admin Panel)
- [ ] Complete Module 5 (Testing)

### DevOps Team
- [ ] Complete Module 1 (Architecture)
- [ ] Complete Module 6 (Monitoring)
- [ ] Complete Module 7 (Deployment)
- [ ] Complete Module 8 (Security)

### QA Team
- [ ] Complete Module 1 (Architecture)
- [ ] Complete Module 3 (API)
- [ ] Complete Module 5 (Testing)
- [ ] Complete Module 6 (Monitoring)

### Support Team
- [ ] Complete Module 1 (Architecture)
- [ ] Complete Module 3 (API - basic)
- [ ] Complete Module 4 (Admin Panel)
- [ ] Complete Module 6 (Monitoring)
- [ ] Complete Module 8 (Incidents)

---

## 📋 TRAINING CHECKLIST

### Pre-Training
- [ ] Ensure all team members have accounts
- [ ] Access to staging environment
- [ ] Postman/curl installed
- [ ] kubectl installed
- [ ] Required credentials available

### During Training
- [ ] Record all sessions
- [ ] Send slides/materials in advance
- [ ] Allow time for hands-on practice
- [ ] Encourage Q&A
- [ ] Provide code snippets

### Post-Training
- [ ] Share recording links
- [ ] Provide summary materials
- [ ] Setup mentoring/pairing
- [ ] Create follow-up questions list
- [ ] Schedule Q&A sessions

---

## 🎓 CERTIFICATION

### Competency Levels

**Level 1: Awareness**
- Completed training module
- Basic understanding
- Can answer simple questions

**Level 2: Proficiency**
- Hands-on experience
- Can perform basic tasks
- Limited troubleshooting

**Level 3: Expertise**
- Months of experience
- Can troubleshoot complex issues
- Can train others

### Certification Paths

**Backend Engineer (Level 2)**
- [ ] Module 1 passed
- [ ] Module 2 passed
- [ ] Module 3 passed
- [ ] 2+ weeks hands-on
- [ ] Code review approval

**DevOps Engineer (Level 2)**
- [ ] Module 1 passed
- [ ] Module 6 passed
- [ ] Module 7 passed
- [ ] 2+ weeks on-call
- [ ] Successful deployments (3+)

**Security Engineer (Level 2)**
- [ ] Module 1 passed
- [ ] Module 8 passed
- [ ] Security audit approval
- [ ] Incident handling practice
- [ ] Vulnerability assessment

---

## 📞 SUPPORT & MENTORING

### Knowledge Sharing
- Pair programming for complex features
- Code review sessions (weekly)
- Architecture discussions
- Incident postmortem meetings
- Knowledge base updates

### Getting Help
- **Quick questions:** Team Slack channel
- **Escalation:** Engineering lead
- **Deep dives:** Schedule 1-on-1
- **Training:** Review recorded sessions
- **Documentation:** See COMPREHENSIVE_IMPLEMENTATION_GUIDE.md

---

## ✅ SUCCESS CRITERIA

Team members should be able to:

```
□ Explain system architecture (5 minutes)
□ Run test suite and interpret results
□ Deploy to staging safely
□ Respond to production alert
□ Rollback deployment if needed
□ Add new API endpoint
□ Write security test
□ Moderate content via admin panel
□ Answer customer support questions
□ Investigate production issue
```

---

**Training is ready!** 👥

Start with Module 1 and progress through all 8 modules for complete mastery.
