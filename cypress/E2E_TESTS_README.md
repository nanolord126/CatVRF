# E2E Tests — Cypress

## Overview

Comprehensive E2E (End-to-End) testing suite using **Cypress 14+** for testing critical user workflows across the platform.

**Test Files:**
- `payment-flow.cy.ts` — Payment initialization, webhooks, fraud scoring
- `rbac-authorization.cy.ts` — Role-based access control, permissions, team management
- `wishlist-service.cy.ts` — Wishlist operations, sharing, group purchases

## Setup

### 1. Install Dependencies

```bash
npm install
# or
yarn install
```

### 2. Configure Environment

Create `.env.testing` or use existing environment:

```env
CYPRESS_BASE_URL=http://localhost:8000
CYPRESS_API_TOKEN=your-test-api-token
```

### 3. Start Application

Ensure Laravel app is running:

```bash
php artisan serve
```

Or with Octane:

```bash
php artisan octane:start
```

## Running Tests

### Interactive Mode (Recommended for Development)

```bash
npm run test:e2e:open
```

Opens Cypress UI where you can:
- View tests in real-time
- Debug with browser DevTools
- Inspect elements
- Re-run failed tests

### Headless Mode (CI/CD)

```bash
npm run test:e2e:all
```

Runs all tests silently, output as JSON/video.

### Run Specific Test Suite

```bash
# Payment flow tests only
npm run test:e2e:payment

# RBAC authorization tests only
npm run test:e2e:rbac

# Wishlist tests only
npm run test:e2e:wishlist
```

### CI/CD Pipeline

```bash
npm run test:e2e:ci
```

Runs headless tests suitable for GitHub Actions, GitLab CI, etc.

## Test Structure

### Payment Flow Tests (`payment-flow.cy.ts`)

**Scenarios:**
1. ✅ Display wallet balance
2. ✅ Initialize payment form
3. ✅ Prevent duplicate payments (idempotency)
4. ✅ Hold payment & auto-release after 24h
5. ✅ Fraud scoring & blocking (score > 0.7)
6. ✅ Process webhooks (Tinkoff/Sber/Tochka)
7. ✅ Credit wallet after successful payment
8. ✅ Audit trail with correlation_id
9. ✅ Webhook signature verification

**Expected Results:**
- Payments processed atomically
- No duplicate charges (idempotency)
- Fraud attempts blocked/logged
- Webhook responses verified
- Audit trail complete

### RBAC Authorization Tests (`rbac-authorization.cy.ts`)

**User Roles:**
- **Owner** — Full control, withdraw money, manage team
- **Manager** — View analytics, cannot withdraw
- **Employee** — Basic dashboard, no analytics
- **Accountant** — View finances, no withdrawal
- **Customer** — Public marketplace only, no CRM
- **SuperAdmin** — Platform-wide admin access

**Scenarios:**
1. ✅ Owner can view/update tenant
2. ✅ Owner can withdraw money
3. ✅ Manager can view analytics (cannot withdraw)
4. ✅ Accountant can view financials (cannot withdraw)
5. ✅ Employee has restricted access
6. ✅ Customer cannot access CRM
7. ✅ SuperAdmin bypasses all restrictions
8. ✅ Cross-tenant access blocked
9. ✅ Role update workflow
10. ✅ User invitation & acceptance flow

**Expected Results:**
- Proper permission enforcement
- No privilege escalation
- Consistent access control
- Audit logging on permission checks

### Wishlist Service Tests (`wishlist-service.cy.ts`)

**Scenarios:**
1. ✅ Add product/service to wishlist
2. ✅ Prevent duplicate additions
3. ✅ Remove items from wishlist
4. ✅ View wishlist with sorting/filtering
5. ✅ Share wishlist via public link
6. ✅ Group purchasing workflow
7. ✅ Payment splitting & requests
8. ✅ Wishlist analytics/statistics
9. ✅ Cross-device sync

**Expected Results:**
- Wishlist operations atomic
- Share links public & secure
- Group purchases tracked
- Analytics accurate

## Custom Commands

Available Cypress commands for use in tests:

```typescript
// Authentication
cy.login(email, password);        // Login user
cy.logout();                       // Logout current user

// Data Management
cy.createUser(userData);           // Create test user via API
cy.createTenant(tenantData);       // Create test tenant
cy.addUserToTenant(id, id, role); // Assign user to tenant
cy.seedTestData();                 // Seed database with test data
cy.clearTestData();                // Clear all test data
```

## Debugging

### View Test Code
```bash
npm run test:e2e:open
# Then click on any test file to view its code
```

### Enable Debug Logs
```bash
DEBUG=cypress:* npm run test:e2e:payment
```

### Screenshot on Failure
Tests automatically capture screenshots on failure (in `cypress/screenshots/`)

### Video Recording
Videos recorded to `cypress/videos/` (slow down playback if needed)

## Performance Benchmarks

**Target Response Times:**
- Page loads: < 2s
- API responses: < 500ms
- Payment processing: < 3s
- RBAC checks: < 100ms
- Fraud scoring: < 500ms

**Success Criteria:**
- ✅ All test suites pass
- ✅ No false positives
- ✅ Average runtime < 5 minutes
- ✅ > 95% coverage of critical paths

## Troubleshooting

### Tests Timing Out
```bash
# Increase timeout (default 10s)
// In test file
describe('My Test', { timeout: 30000 }, () => { ... })
```

### Cannot Connect to API
```bash
# Verify app is running
php artisan serve

# Check CYPRESS_BASE_URL
echo $CYPRESS_BASE_URL
```

### Flaky Tests
- Add explicit waits: `cy.get('[data-cy=element]', { timeout: 10000 })`
- Use `cy.intercept()` to mock API responses
- Avoid hardcoded `cy.wait(1000)` delays

### Database State Issues
- Tests auto-clear cookies/storage in `beforeEach()`
- Use `cy.seedTestData()` to reset to known state
- Check test database is separate from production

## CI/CD Integration

### GitHub Actions Example

```yaml
name: E2E Tests
on: [push, pull_request]

jobs:
  e2e:
    runs-on: ubuntu-latest
    services:
      mariadb:
        image: mariadb:10.6
        env:
          MYSQL_ROOT_PASSWORD: root
          
    steps:
      - uses: actions/checkout@v3
      - uses: actions/setup-node@v3
        with:
          node-version: 18
          
      - run: composer install
      - run: npm install
      - run: cp .env.example .env
      - run: php artisan key:generate
      - run: php artisan migrate
      - run: php artisan serve &
      - run: npm run test:e2e:ci
      
      - if: failure()
        uses: actions/upload-artifact@v3
        with:
          name: cypress-artifacts
          path: |
            cypress/screenshots/
            cypress/videos/
```

## Best Practices

1. **Use `data-cy` attributes** for element selection (more reliable than selectors)
2. **Avoid hardcoded waits** — use explicit waits with `cy.intercept()` or `cy.contains()`
3. **Test user flows** — not implementation details
4. **Keep tests independent** — no reliance on test execution order
5. **Mock external APIs** — payment gateways, geolocation, etc.
6. **Use `beforeEach()` hooks** for setup, cleanup in `afterEach()`
7. **Seed test data** before critical tests
8. **Clear state** after tests to prevent pollution

## Resources

- [Cypress Docs](https://docs.cypress.io/)
- [Best Practices](https://docs.cypress.io/guides/references/best-practices)
- [Custom Commands](https://docs.cypress.io/api/cypress-api/custom-commands)
- [Testing Strategies](https://docs.cypress.io/guides/core-concepts/testing-your-app)

## Support

For E2E test issues:
1. Check test logs: `cypress/logs/`
2. Review video recording: `cypress/videos/`
3. Check screenshots: `cypress/screenshots/`
4. Run single test: `npm run test:e2e:open` → click test
5. Check browser console for errors

---

**Last Updated:** 17 March 2026  
**Maintained by:** CANON 2026 Development Team
