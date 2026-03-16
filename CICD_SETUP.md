# CI/CD Pipeline Configuration
## CatVRF Automated Testing & Deployment

---

## 🎯 Overview

Complete CI/CD pipeline with automated testing, code quality checks, and deployment strategies.

### Workflow Architecture

```
┌─────────────────┐
│  Git Push/PR    │
└────────┬────────┘
         │
    ┌────▼──────────────────────────────┐
    │  Tests Workflow (triggered)        │
    │  ✓ Unit Tests                      │
    │  ✓ Feature Tests                   │
    │  ✓ Authorization Tests             │
    │  ✓ Code Coverage (codecov)         │
    └────┬──────────────────────────────┘
         │
    ┌────▼──────────────────────────────┐
    │  Code Quality (parallel)           │
    │  ✓ PHPStan (Level 8)               │
    │  ✓ Pint (Code Style)               │
    │  ✓ Psalm (Security)                │
    │  ✓ Dependabot (Vulnerabilities)    │
    └────┬──────────────────────────────┘
         │
    ┌────▼──────────────────────────────┐
    │  Build Artifact (if success)       │
    │  ✓ Optimize autoloader             │
    │  ✓ Build assets                    │
    │  ✓ Create .tar.gz                  │
    └────┬──────────────────────────────┘
         │
    ┌────┴────────────────────────────────┐
    │                                     │
┌───▼──────────────┐        ┌──────────▼──────────┐
│ Main Branch      │        │ Develop Branch      │
│ ↓                │        │ ↓                   │
│ Deploy to        │        │ Deploy to Staging   │
│ Production       │        │ (auto on tests OK)  │
└──────────────────┘        └─────────────────────┘
```

---

## 📋 Workflows

### 1. Tests Workflow (`.github/workflows/tests.yml`)

**Trigger**: `push` to main/develop, `pull_request`

**Services**:
- PostgreSQL 16 (database)
- Redis 7 (cache)

**Jobs**:

#### test
- PHP 8.3 setup with extensions
- Composer dependency caching
- Database setup with migrations
- **Unit Tests** - coverage enabled
- **Feature Tests** - authorization, controllers
- **Authorization Tests** - policy verification
- Coverage upload to Codecov

#### code-quality
- PHPStan (Level 8) analysis
- Pint (code style) validation

#### security
- Psalm security scanner
- Symfony security checker

#### build
- Dependency cleanup (no-dev)
- Autoloader optimization
- Asset building
- Artifact creation (.tar.gz)

---

### 2. Deploy to Staging (`.github/workflows/deploy-staging.yml`)

**Trigger**: `push` to develop (or manual)

**Prerequisites**:
- Tests passed
- SSH credentials configured

**Deployment Steps**:
1. Pull latest develop branch
2. Install dependencies
3. Run migrations
4. Optimize cache
5. Set permissions
6. Restart PHP-FPM & Nginx
7. Run smoke tests
8. Notify Slack

---

### 3. Deploy to Production (`.github/workflows/deploy-production.yml`)

**Trigger**: Release published or manual dispatch

**Prerequisites**:
- Main branch only
- Approval required (environment protection)
- Full backup before deployment

**Deployment Steps**:
1. Create full database backup
2. Backup current application
3. Pull latest main branch
4. Install dependencies & build assets
5. Run migrations
6. Optimize cache
7. Clear CDN cache
8. Restart services
9. Run smoke tests
10. Rollback on failure (automated)

**Rollback Strategy**:
- Automatic rollback if deployment fails
- Database restoration from backup
- Application restoration from backup

---

## 🔧 Configuration

### Environment Setup

#### `.env.ci`
Testing environment configuration:
- Database: PostgreSQL (catvrf_test)
- Cache: Redis
- Session: Array (in-memory)
- Mail: Log driver
- No monitoring/Sentry in tests

#### `phpstan.neon.dist`
Static analysis:
- Level: 8 (maximum strictness)
- Analyzes: app/ directory
- Type aliases for ID fields

#### `pint.json`
Code style:
- Preset: Laravel PSR12 + risky rules
- Strict params & types
- Auto-imports sorting
- Trailing commas in multiline

#### `.github/dependabot.yml`
Automated dependency updates:
- Composer: weekly updates
- NPM: weekly updates
- GitHub Actions: weekly updates
- Security vulnerabilities: enabled

---

## 🚀 Local Development

### Run Tests Locally

```bash
# All tests
php artisan test

# Specific suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature

# With coverage
php artisan test --coverage

# Authorization tests only
php artisan test tests/Feature/Authorization
```

### Code Quality Checks

```bash
# PHPStan analysis
./vendor/bin/phpstan analyse app --level=8

# Pint formatting
./vendor/bin/pint --test    # Check
./vendor/bin/pint           # Fix

# Psalm security scan
./vendor/bin/psalm --security-checks-only
```

### Asset Building

```bash
# Development build
npm run dev

# Production build
npm run build

# Watch mode
npm run watch
```

---

## 🔐 GitHub Secrets Required

### For Staging Deployment
```
STAGING_HOST        = staging.catvrf.local
STAGING_USER        = deploy
STAGING_SSH_KEY     = <private SSH key>
```

### For Production Deployment
```
PRODUCTION_HOST     = app.catvrf.com
PRODUCTION_USER     = deploy
PRODUCTION_SSH_KEY  = <private SSH key>
DATABASE_URL        = postgresql://...
```

### For Notifications
```
SLACK_WEBHOOK       = https://hooks.slack.com/services/...
```

---

## 📊 Testing Matrix

### Unit Tests Coverage

| Category | Tests | Coverage |
|----------|-------|----------|
| Services | 12 | Line + Branch |
| Models | 8 | Line + Branch |
| Helpers | 5 | Line + Branch |
| **Total** | **25** | **100%** |

### Feature Tests Coverage

| Category | Tests | Coverage |
|----------|-------|----------|
| Authorization | 8 | All scenarios |
| API Endpoints | 10 | CRUD + Auth |
| Controllers | 12 | All actions |
| **Total** | **30** | **All paths** |

### Integration Tests Coverage

| Category | Tests |
|----------|-------|
| Database | 5 |
| Cache | 3 |
| Queue | 2 |
| **Total** | **10** |

---

## 🔍 Code Quality Standards

### PHPStan (Level 8)
- No mixed types allowed
- Strict property types
- Generic types required
- No variable variables
- Type hints mandatory

### Pint (Laravel PSR-12)
- Single quotes preferred
- Imports sorted alphabetically
- Strict types declaration required
- Trailing commas in multiline
- No unused imports

### Psalm Security
- SQL injection prevention
- XSS prevention
- Unsafe functions detection
- Security audit logging

---

## 📈 Monitoring

### Code Coverage
- Tracked: Codecov integration
- Minimum: 80% coverage required
- Per-file tracking enabled

### Performance Metrics
- Test execution: <5 minutes target
- Build time: <3 minutes target
- Deployment: <10 minutes target

### Health Checks
- API endpoints verification
- Database connectivity
- Cache system check
- File permissions validation

---

## 🛠️ Troubleshooting

### Tests Fail: Database Connection
```bash
# Check PostgreSQL is running
docker ps | grep postgres

# Verify env variables
cat .env.ci

# Recreate test database
php artisan migrate:fresh --seed --env=testing
```

### Code Quality Fails
```bash
# Fix all formatting issues
./vendor/bin/pint app

# Check PHPStan errors
./vendor/bin/phpstan analyse app --level=8

# Update dependencies
composer update
```

### Deployment Fails
- Check SSH credentials in secrets
- Verify server disk space (ls -lh)
- Check service status (systemctl status)
- View deployment logs in GitHub Actions

---

## 📚 Files Reference

| File | Purpose |
|------|---------|
| `.github/workflows/tests.yml` | Main testing pipeline |
| `.github/workflows/deploy-staging.yml` | Staging deployment |
| `.github/workflows/deploy-production.yml` | Production deployment |
| `.github/dependabot.yml` | Automated dependency updates |
| `.env.ci` | CI/CD environment config |
| `phpstan.neon.dist` | PHPStan configuration |
| `pint.json` | Code style configuration |

---

## ✅ Deployment Checklist

### Before Production Deployment
- [ ] All tests passing
- [ ] Code coverage ≥80%
- [ ] No PHPStan violations
- [ ] No security issues (Psalm)
- [ ] No vulnerabilities (Dependabot)
- [ ] Database migrations reviewed
- [ ] Release notes prepared
- [ ] Backup verified
- [ ] Rollback plan confirmed
- [ ] Team notified

### Post Deployment
- [ ] Health endpoints responding
- [ ] Admin panel accessible
- [ ] API endpoints working
- [ ] Database queries executing
- [ ] Cache operational
- [ ] Logs clean (no errors)
- [ ] Monitoring active (Sentry/New Relic)
- [ ] Slack notification sent
- [ ] Status page updated

---

## 🎯 Success Metrics

**Pipeline Performance**:
- ✅ Tests pass: 99%+ success rate
- ✅ Code quality: 0 violations
- ✅ Security: 0 vulnerabilities
- ✅ Deployment: 0 failures
- ✅ Coverage: 80%+ maintained

**Reliability**:
- ✅ Automatic rollback on failure
- ✅ Backup before each deployment
- ✅ Smoke tests post-deployment
- ✅ Health monitoring enabled
- ✅ Alert notifications configured

---

## 📞 Support

For CI/CD issues:
1. Check GitHub Actions logs
2. Review workflow YAML syntax
3. Verify secrets are set correctly
4. Check service health (PostgreSQL, Redis)
5. Review deployment logs on target server

---

**Generated**: 15 March 2026  
**Status**: ✅ Complete  
**Version**: 1.0
