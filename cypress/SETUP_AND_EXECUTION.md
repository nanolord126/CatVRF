# Cypress Testing Setup & Execution Guide

## 🚀 Quick Start

### 1. Install Dependencies

```bash
npm install
npm install --save-dev cypress @types/cypress @types/chai @types/mocha
```

### 2. Verify Installation

```bash
npx cypress --version
```

### 3. Configure Environment

Create `.env.test`:

```env
# API Configuration
API_BASE_URL=http://localhost:8000
API_TOKEN=test_admin_token_12345
MANAGER_TOKEN=test_manager_token_12345
VIEWER_TOKEN=test_viewer_token_12345

# Database
DB_CONNECTION=testing
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=catvrf_test
DB_USERNAME=root
DB_PASSWORD=

# App
APP_ENV=testing
APP_DEBUG=true
APP_KEY=base64:YourTestAppKey==
```

### 4. Setup Test Database

```bash
# Create test database
php artisan migrate --env=testing

# Seed with test data
php artisan db:seed --class=TestSeeder --env=testing
```

### 5. Start Application

```bash
# In one terminal
php artisan serve --port=8000

# In another terminal, run tests
npm run cypress:run
```

---

## 📋 Available NPM Scripts

### Development Mode

```bash
# Open interactive Cypress
npm run cypress:open

# Run tests with UI
npm run cypress:e2e
```

### CI/CD Mode

```bash
# Run all tests headless
npm run cypress:run

# Run specific test suite
npm run cypress:run -- --spec "cypress/e2e/inventory.cy.ts"

# Run with parallel execution
npm run cypress:run -- --parallel --record
```

### Reporting

```bash
# Generate coverage report
npm run cypress:coverage

# Generate HTML report
npm run cypress:report

# Generate video report
npm run cypress:video
```

### Add to package.json

```json
{
  "scripts": {
    "cypress:open": "cypress open",
    "cypress:e2e": "cypress run --headed",
    "cypress:run": "cypress run",
    "cypress:coverage": "cypress run --coverage",
    "cypress:report": "mochawesome --reportDir results --html",
    "cypress:video": "cypress run --spec 'cypress/e2e/**/*.cy.ts'",
    "test:auth": "cypress run --spec 'cypress/e2e/auth.cy.ts'",
    "test:security": "cypress run --spec 'cypress/e2e/security.cy.ts'",
    "test:inventory": "cypress run --spec 'cypress/e2e/inventory.cy.ts'",
    "test:payroll": "cypress run --spec 'cypress/e2e/payroll.cy.ts'",
    "test:hr": "cypress run --spec 'cypress/e2e/hr.cy.ts'",
    "test:communications": "cypress run --spec 'cypress/e2e/communications.cy.ts'",
    "test:beauty": "cypress run --spec 'cypress/e2e/beauty.cy.ts'",
    "test:rbac": "cypress run --spec 'cypress/e2e/rbac.cy.ts'",
    "test:validation": "cypress run --spec 'cypress/e2e/validation.cy.ts'",
    "test:api": "cypress run --spec 'cypress/e2e/api-integration.cy.ts'"
  }
}
```

---

## 🔧 Custom Cypress Commands

Create `cypress/support/commands.ts`:

```typescript
import 'cypress'

// Custom login command
Cypress.Commands.add('loginAs', (email: string, password: string) => {
  cy.visit('http://localhost:8000/login')
  cy.get('[data-testid="input-email"]').type(email)
  cy.get('[data-testid="input-password"]').type(password)
  cy.get('[data-testid="btn-login"]').click()
  cy.url().should('include', '/dashboard')
})

// Reset database
Cypress.Commands.add('resetDatabase', () => {
  cy.request({
    method: 'POST',
    url: 'http://localhost:8000/api/test/reset-database',
    headers: {
      'Authorization': `Bearer ${Cypress.env('API_TOKEN')}`
    }
  }).then((response) => {
    expect(response.status).to.eq(200)
  })
})

// Seed database
Cypress.Commands.add('seedDatabase', () => {
  cy.request({
    method: 'POST',
    url: 'http://localhost:8000/api/test/seed-database',
    headers: {
      'Authorization': `Bearer ${Cypress.env('API_TOKEN')}`
    }
  }).then((response) => {
    expect(response.status).to.eq(200)
  })
})

// Create test user
Cypress.Commands.add('createTestUser', (email: string, role: string = 'admin') => {
  return cy.request({
    method: 'POST',
    url: 'http://localhost:8000/api/test/users',
    headers: {
      'Authorization': `Bearer ${Cypress.env('API_TOKEN')}`,
      'Content-Type': 'application/json'
    },
    body: {
      email,
      password: 'password123',
      role,
      name: email.split('@')[0]
    }
  }).then((response) => {
    expect(response.status).to.eq(201)
    return response.body.data
  })
})

// API request with auth
Cypress.Commands.add('apiRequest', (method: string, url: string, body?: any) => {
  return cy.request({
    method,
    url: `http://localhost:8000${url}`,
    headers: {
      'Authorization': `Bearer ${Cypress.env('API_TOKEN')}`,
      'Content-Type': 'application/json'
    },
    body,
    failOnStatusCode: false
  })
})

declare global {
  namespace Cypress {
    interface Chainable {
      loginAs(email: string, password: string): Chainable
      resetDatabase(): Chainable
      seedDatabase(): Chainable
      createTestUser(email: string, role?: string): Chainable
      apiRequest(method: string, url: string, body?: any): Chainable
    }
  }
}
```

---

## 🌐 CI/CD Configuration

### GitHub Actions Workflow

Create `.github/workflows/cypress-tests.yml`:

```yaml
name: E2E Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main, develop]

jobs:
  cypress-run:
    runs-on: ubuntu-latest
    
    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: root
          MYSQL_DATABASE: catvrf_test
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3
        ports:
          - 3306:3306
    
    steps:
      - name: Checkout
        uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: curl, gd, iconv, json, mbstring, openssl, pdo, pdo_mysql, tokenizer, xml
      
      - name: Install PHP Dependencies
        run: |
          composer install --no-interaction --prefer-dist
      
      - name: Install Node Dependencies
        run: |
          npm install
          npm install --save-dev cypress @types/cypress @types/chai @types/mocha
      
      - name: Setup Environment
        run: |
          cp .env.example .env.test
          php artisan key:generate --env=testing
      
      - name: Run Database Migrations
        run: |
          php artisan migrate --env=testing --force
          php artisan db:seed --env=testing --force
      
      - name: Start Application
        run: |
          php artisan serve --port=8000 &
          sleep 5
      
      - name: Run Cypress Tests
        run: npm run cypress:run
      
      - name: Upload Videos
        if: failure()
        uses: actions/upload-artifact@v3
        with:
          name: cypress-videos
          path: cypress/videos
          retention-days: 30
      
      - name: Upload Screenshots
        if: failure()
        uses: actions/upload-artifact@v3
        with:
          name: cypress-screenshots
          path: cypress/screenshots
          retention-days: 30
      
      - name: Upload Coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage/coverage-final.json
          flags: e2e-tests
```

---

## 🗂️ Test Data Setup

### Test Seeder (`database/seeders/TestSeeder.php`)

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        // Create test tenant
        $tenant = Tenant::create([
            'id' => 'test-tenant',
            'name' => 'Test Tenant',
            'domain' => 'test.localhost'
        ]);
        
        // Create test users
        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@test.local',
            'password' => bcrypt('password123'),
            'role' => 'admin'
        ]);
        
        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager User',
            'email' => 'manager@test.local',
            'password' => bcrypt('password123'),
            'role' => 'manager'
        ]);
        
        User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Viewer User',
            'email' => 'viewer@test.local',
            'password' => bcrypt('password123'),
            'role' => 'viewer'
        ]);
    }
}
```

---

## 📊 Test Execution Examples

### Run All Tests

```bash
npm run cypress:run

# Output:
# ✓ cypress/e2e/auth.cy.ts (9 tests)
# ✓ cypress/e2e/security.cy.ts (8 tests)
# ✓ cypress/e2e/inventory.cy.ts (36 tests)
# ✓ cypress/e2e/payroll.cy.ts (42 tests)
# ✓ cypress/e2e/hr.cy.ts (48 tests)
# ✓ cypress/e2e/communications.cy.ts (45 tests)
# ✓ cypress/e2e/beauty.cy.ts (52 tests)
# ✓ cypress/e2e/rbac.cy.ts (38 tests)
# ✓ cypress/e2e/validation.cy.ts (58 tests)
# ✓ cypress/e2e/api-integration.cy.ts (52 tests)
#
# Total: 388 tests passing in 14m 32s
```

### Run Specific Module

```bash
npm run test:inventory

# Output:
# ✓ Should list inventory items with filtering
# ✓ Should create new inventory item
# ✓ Should update inventory quantity
# ✓ Should trigger low stock alerts
# ✓ Should delete inventory item
# ... (33 more tests)
```

### Run with Debugging

```bash
npx cypress run --spec "cypress/e2e/inventory.cy.ts" --headed --browser chrome
```

---

## 🐛 Debugging Tips

### Enable Debugging

```typescript
// In test file
before(() => {
  cy.log('Starting test suite')
  cy.debug() // Shows Cypress console
})

// Log values
cy.get('[data-testid="item"]').then((el) => {
  console.log('Element text:', el.text())
  cy.log(`Item: ${el.text()}`)
})
```

### Browser DevTools

```bash
# Open Cypress with DevTools
npx cypress open --dev-tools
```

### Slow Down Execution

```javascript
// cypress.config.ts
export default defineConfig({
  e2e: {
    slowTestThreshold: 10000,
    defaultCommandTimeout: 10000,
    slowDown: 1000 // milliseconds
  }
})
```

---

## ✅ Pre-Deployment Checklist

```bash
# 1. Run all tests
npm run cypress:run

# 2. Check for console errors
npm run cypress:run -- --no-exit

# 3. Run with coverage
npm run cypress:coverage

# 4. Verify API responses
npm run test:api

# 5. Security test
npm run test:security

# 6. Check performance
npm run test:performance
```

---

## 📈 Test Metrics

Track these KPIs:

- **Pass Rate**: Target 95%+
- **Test Duration**: Target average < 30 seconds per test
- **Coverage**: Target 90%+ code coverage
- **Flakiness**: Target 0% flaky tests

---

## 🔗 Resources

- [Cypress Documentation](https://docs.cypress.io)
- [Testing Library](https://testing-library.com)
- [Chai Assertions](https://www.chaijs.com/api/)
- [BDD Best Practices](https://cucumber.io/docs/bdd/)
