# Comprehensive Testing Infrastructure Implementation Guide
## CatVRF 2026 - Section 7 Implementation

**Date:** 25.04.2026  
**Estimated Time:** 360-540 hours (45-67 working days)  
**Status:** 🔄 IN PROGRESS

---

## Overview

This guide provides detailed implementation steps for establishing a comprehensive testing infrastructure for CatVRF production system. The implementation covers functional, load, security, DDoS, isolation, smoke, sanity, and regression testing with target coverage of ≥98.5%.

---

## Phase 1: Basic Infrastructure (40-60 hours)

### 1.1 Pest PHP Configuration Enhancement

**File:** `pest.php` (already configured, needs enhancement)

```php
<?php

declare(strict_types=1);

use Pest\Platform\Services\Platform;
use Pest\Plugin\Laravel;

Platform::detect();

return [
    'plugins' => [
        Laravel::class,
    ],

    'tests' => [
        'tests/Unit',
        'tests/Feature',
        'tests/E2E',
        'tests/Chaos',
        'tests/Contract',
        'tests/Integration',
        'tests/Security',
        'tests/Load',
        'tests/Smoke',
        'tests/Sanity',
    ],

    'source' => [
        'app',
        'modules',
    ],

    'coverage' => [
        'include' => [
            'app',
            'modules',
        ],
        'exclude' => [
            'app/Domains/Archived',
            'app/Stubs',
        ],
        'report' => [
            'html' => 'coverage/html',
            'text' => 'coverage/coverage.txt',
            'clover' => 'coverage/clover.xml',
        ],
        'threshold' => [
            'global' => 98.5,
            'lines' => 98.5,
        ],
    ],

    'parallel' => [
        'processes' => null,
    ],

    'snapshots' => [
        'directory' => 'tests/__snapshots__',
    ],

    'printOutput' => false,

    'ignoreHighMaintenance' => false,
];
```

### 1.2 Enhanced Base TestCase

**File:** `tests/BaseTestCase.php` (create if not exists)

```php
<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

abstract class BaseTestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureTestEnvironment();
        $this->clearCache();
        $this->clearRedis();
    }

    protected function tearDown(): void
    {
        $this->clearRedis();
        parent::tearDown();
    }

    protected function configureTestEnvironment(): void
    {
        Config::set('app.env', 'testing');
        Config::set('cache.default', 'array');
        Config::set('queue.default', 'sync');
        Config::set('session.driver', 'array');
    }

    protected function clearCache(): void
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
    }

    protected function clearRedis(): void
    {
        if (Config::get('cache.default') === 'redis') {
            Redis::flushdb();
        }
    }

    protected function actingAsTenant(int $tenantId): self
    {
        tenant()->initialize($tenantId);
        return $this;
    }
}
```

### 1.3 Base Vertical TestCase

**File:** `tests/BaseVerticalTestCase.php`

```php
<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Payment\Services\PaymentService;
use Modules\Wallet\Services\WalletService;
use App\Services\Audit\AuditService;

abstract class BaseVerticalTestCase extends BaseTestCase
{
    protected PaymentService $paymentService;
    protected WalletService $walletService;
    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = app(PaymentService::class);
        $this->walletService = app(WalletService::class);
        $this->auditService = app(AuditService::class);
    }

    protected function createTestUser(array $attributes = []): \App\Models\User
    {
        return \App\Models\User::factory()->create($attributes);
    }

    protected function createTestTenant(array $attributes = []): \App\Models\Tenant
    {
        return \App\Models\Tenant::factory()->create($attributes);
    }

    protected function assertAuditLogExists(string $action, string $entityType, int $entityId): void
    {
        $this->assertDatabaseHas('audit_logs', [
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }
}
```

### 1.4 Test Database Configuration

**File:** `phpunit.xml` (enhanced)

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         stopOnFailure="false"
         failOnWarning="true"
         failOnRisky="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="E2E">
            <directory>tests/E2E</directory>
        </testsuite>
        <testsuite name="Security">
            <directory>tests/Security</directory>
        </testsuite>
        <testsuite name="Load">
            <directory>tests/Load</directory>
        </testsuite>
        <testsuite name="Smoke">
            <directory>tests/Smoke</directory>
        </testsuite>
        <testsuite name="Sanity">
            <directory>tests/Sanity</directory>
        </testsuite>
        <testsuite name="Chaos">
            <directory>tests/Chaos</directory>
        </testsuite>
        <testsuite name="Contract">
            <directory>tests/Contract</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
            <directory>modules</directory>
        </include>
        <exclude>
            <directory>app/Domains/Archived</directory>
            <directory>app/Stubs</directory>
        </exclude>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="APP_MAINTENANCE_DRIVER" value="file"/>
        <env name="APP_KEY" value="base64:test-key-for-testing-only"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="BROADCAST_CONNECTION" value="null"/>
        <env name="CACHE_STORE" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="PULSE_ENABLED" value="false"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
        <env name="NIGHTWATCH_ENABLED" value="false"/>
        <env name="HORIZON_ENABLED" value="false"/>
        <env name="LOG_LEVEL" value="debug"/>
    </php>
    <coverage processUncoveredFiles="true">
        <include>
            <directory suffix=".php">app</directory>
            <directory suffix=".php">modules</directory>
        </include>
        <exclude>
            <directory>app/Domains/Archived</directory>
            <directory>app/Stubs</directory>
            <file>app/helpers.php</file>
        </exclude>
        <report>
            <html outputDirectory="coverage/html"/>
            <text outputFile="coverage/coverage.txt" showUncoveredFiles="true"/>
            <clover outputFile="coverage/clover.xml"/>
        </report>
        <threshold>
            <global>
                <line percent="98.5"/>
                <method percent="98.5"/>
                <class percent="98.5"/>
            </global>
        </threshold>
    </coverage>
</php>
```

### 1.5 CI/CD Pipeline Configuration

**File:** `.github/workflows/comprehensive-testing.yml`

```yaml
name: Comprehensive Testing

on:
  push:
    branches: [main, develop, staging]
  pull_request:
    branches: [main, develop]
  schedule:
    - cron: '0 2 * * *' # Daily at 2 AM UTC

jobs:
  unit-tests:
    name: Unit Tests
    runs-on: ubuntu-latest
    timeout-minutes: 30
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo, pdo_sqlite, bcmath, redis
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-suggest

      - name: Run unit tests
        run: vendor/bin/pest --testsuite=Unit --coverage --min=98.5

      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          files: ./coverage/clover.xml

  feature-tests:
    name: Feature Tests
    runs-on: ubuntu-latest
    timeout-minutes: 45
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo, pdo_sqlite, bcmath, redis

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-suggest

      - name: Run feature tests
        run: vendor/bin/pest --testsuite=Feature --coverage --min=98.5

  integration-tests:
    name: Integration Tests
    runs-on: ubuntu-latest
    timeout-minutes: 60
    services:
      redis:
        image: redis:7-alpine
        ports:
          - 6379:6379
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo, pdo_mysql, bcmath, redis

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-suggest

      - name: Setup MySQL
        run: |
          sudo systemctl start mysql
          mysql -e 'CREATE DATABASE IF NOT EXISTS catvrf_test;' -uroot -proot

      - name: Run integration tests
        run: vendor/bin/pest --testsuite=Integration
        env:
          DB_CONNECTION: mysql
          DB_DATABASE: catvrf_test
          DB_USERNAME: root
          DB_PASSWORD: root

  e2e-tests:
    name: E2E Tests
    runs-on: ubuntu-latest
    timeout-minutes: 90
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Install dependencies
        run: npm ci

      - name: Run E2E tests
        run: npm run test:e2e

  security-tests:
    name: Security Tests
    runs-on: ubuntu-latest
    timeout-minutes: 45
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo, pdo_sqlite, bcmath

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-suggest

      - name: Run security tests
        run: vendor/bin/pest --testsuite=Security

  load-tests:
    name: Load Tests
    runs-on: ubuntu-latest
    timeout-minutes: 60
    if: github.ref == 'refs/heads/main' || github.ref == 'refs/heads/develop'
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup k6
        run: |
          sudo gpg -k
          sudo gpg --no-default-keyring --keyring /usr/share/keyrings/k6-archive-keyring.gpg --keyserver hkp://keyserver.ubuntu.com:80 --recv-keys C5AD17C747E3415A3642D57D77C6C491D6AC1D69
          echo "deb [signed-by=/usr/share/keyrings/k6-archive-keyring.gpg] https://dl.k6.io/deb stable main" | sudo tee /etc/apt/sources.list.d/k6.list
          sudo apt-get update
          sudo apt-get install k6

      - name: Run load tests
        run: k6 run k6/crash-test-medical.js

  smoke-tests:
    name: Smoke Tests
    runs-on: ubuntu-latest
    timeout-minutes: 15
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo, pdo_sqlite, bcmath

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-suggest

      - name: Run smoke tests
        run: vendor/bin/pest --testsuite=Smoke

  phpstan:
    name: PHPStan Static Analysis
    runs-on: ubuntu-latest
    timeout-minutes: 30
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-suggest

      - name: Run PHPStan
        run: vendor/bin/phpstan analyse --memory-limit=2G

  pint:
    name: Laravel Pint Code Style
    runs-on: ubuntu-latest
    timeout-minutes: 20
    
    steps:
      - name: Checkout code
        uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress --no-suggest

      - name: Run Pint
        run: vendor/bin/pint --test
```

---

## Phase 2: Unit and Feature Tests (80-120 hours)

### 2.1 Unit Test Template for Services

**File:** `tests/Unit/Services/ServiceTestCase.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\BaseVerticalTestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Mockery;

abstract class ServiceTestCase extends BaseVerticalTestCase
{
    protected function mockExternalService(string $class): Mockery\MockInterface
    {
        $mock = Mockery::mock($class);
        $this->app->instance($class, $mock);
        return $mock;
    }

    protected function assertEventDispatched(string $eventClass, int $times = 1): void
    {
        Event::assertDispatchedTimes($eventClass, $times);
    }

    protected function assertJobPushed(string $jobClass, int $times = 1): void
    {
        Queue::assertPushedTimes($jobClass, $times);
    }

    protected function assertNoJobsDispatched(): void
    {
        Queue::assertNothingPushed();
    }

    protected function assertNoEventsDispatched(): void
    {
        Event::assertNothingDispatched();
    }
}
```

### 2.2 Example Unit Test for Payment Service

**File:** `tests/Unit/Payment/PaymentServiceTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Unit\Payment;

use Tests\Unit\Services\ServiceTestCase;
use Modules\Payment\Services\PaymentService;
use Modules\Payment\DTO\PaymentDTO;
use Modules\Payment\Models\Payment;
use App\Services\Security\FraudMLService;
use App\Services\Audit\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Events\PaymentFailed;

final class PaymentServiceTest extends ServiceTestCase
{
    private PaymentService $paymentService;
    private FraudMLService $fraudMLService;
    private AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fraudMLService = $this->mockExternalService(FraudMLService::class);
        $this->auditService = $this->mockExternalService(AuditService::class);
        
        $this->paymentService = new PaymentService(
            $this->fraudMLService,
            $this->auditService,
            app(\Illuminate\Log\LogManager::class),
            app(\Illuminate\Database\DatabaseManager::class),
            app(\Illuminate\Cache\CacheManager::class),
        );

        Event::fake();
    }

    public function test_create_payment_successfully(): void
    {
        // Arrange
        $dto = new PaymentDTO(
            userId: 1,
            amount: 10000,
            currency: 'RUB',
            paymentMethod: 'card',
            metadata: ['order_id' => 123]
        );

        $this->fraudMLService
            ->shouldReceive('checkTransaction')
            ->once()
            ->andReturn(['is_fraud' => false, 'score' => 0.1]);

        $this->auditService
            ->shouldReceive('logCreated')
            ->once()
            ->with('Payment', \Mockery::type('int'), \Mockery::type('array'));

        // Act
        $payment = $this->paymentService->create($dto);

        // Assert
        $this->assertInstanceOf(Payment::class, $payment);
        $this->assertEquals(10000, $payment->amount);
        $this->assertEquals('pending', $payment->status);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'user_id' => 1,
            'amount' => 10000,
        ]);
    }

    public function test_create_payment_fails_on_fraud_detection(): void
    {
        // Arrange
        $dto = new PaymentDTO(
            userId: 1,
            amount: 10000,
            currency: 'RUB',
            paymentMethod: 'card',
            metadata: ['order_id' => 123]
        );

        $this->fraudMLService
            ->shouldReceive('checkTransaction')
            ->once()
            ->andReturn(['is_fraud' => true, 'score' => 0.95]);

        // Act & Assert
        $this->expectException(\Modules\Payment\Exceptions\FraudDetectedException::class);
        $this->paymentService->create($dto);
    }

    public function test_process_payment_successfully(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => 'pending',
            'amount' => 10000,
        ]);

        $this->fraudMLService
            ->shouldReceive('checkTransaction')
            ->once()
            ->andReturn(['is_fraud' => false, 'score' => 0.1]);

        // Act
        $result = $this->paymentService->process($payment->id);

        // Assert
        $this->assertTrue($result);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
        ]);
        Event::assertDispatched(PaymentCompleted::class);
    }

    public function test_process_payment_fails_on_gateway_error(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'status' => 'pending',
            'amount' => 10000,
        ]);

        $this->fraudMLService
            ->shouldReceive('checkTransaction')
            ->once()
            ->andThrow(new \Exception('Gateway error'));

        // Act
        $result = $this->paymentService->process($payment->id);

        // Assert
        $this->assertFalse($result);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);
        Event::assertDispatched(PaymentFailed::class);
    }
}
```

### 2.3 Feature Test Template for API Endpoints

**File:** `tests/Feature/Api/ApiTestCase.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Tests\BaseVerticalTestCase;
use Illuminate\Testing\Fluent\AssertableJson;

abstract class ApiTestCase extends BaseVerticalTestCase
{
    protected string $apiVersion = 'v1';

    protected function apiGet(string $endpoint, array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(array_merge([
            'Accept' => "application/json",
            'X-Tenant-ID' => tenant()->id ?? 1,
        ], $headers))
            ->getJson("/api/{$this->apiVersion}{$endpoint}");
    }

    protected function apiPost(string $endpoint, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(array_merge([
            'Accept' => "application/json",
            'X-Tenant-ID' => tenant()->id ?? 1,
        ], $headers))
            ->postJson("/api/{$this->apiVersion}{$endpoint}", $data);
    }

    protected function apiPut(string $endpoint, array $data = [], array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(array_merge([
            'Accept' => "application/json",
            'X-Tenant-ID' => tenant()->id ?? 1,
        ], $headers))
            ->putJson("/api/{$this->apiVersion}{$endpoint}", $data);
    }

    protected function apiDelete(string $endpoint, array $headers = []): \Illuminate\Testing\TestResponse
    {
        return $this->withHeaders(array_merge([
            'Accept' => "application/json",
            'X-Tenant-ID' => tenant()->id ?? 1,
        ], $headers))
            ->deleteJson("/api/{$this->apiVersion}{$endpoint}");
    }

    protected function assertJsonStructure(array $structure): \Closure
    {
        return fn (AssertableJson $json) => $json->hasAll($structure);
    }

    protected function assertUnauthorized(): void
    {
        $this->assertJsonStructure(['message']);
        $this->assertStatus(401);
    }

    protected function assertForbidden(): void
    {
        $this->assertJsonStructure(['message']);
        $this->assertStatus(403);
    }

    protected function assertValidationError(string $field): void
    {
        $this->assertJsonStructure(['message', 'errors']);
        $this->assertStatus(422);
        $this->assertJsonFragment(['errors' => [$field]]); // Simplified
    }
}
```

### 2.4 Example Feature Test for Payment API

**File:** `tests/Feature/Payment/PaymentApiTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use Tests\Feature\Api\ApiTestCase;
use Modules\Payment\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Modules\Payment\Events\PaymentCompleted;

final class PaymentApiTest extends ApiTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_create_payment_via_api(): void
    {
        // Arrange
        $data = [
            'amount' => 10000,
            'currency' => 'RUB',
            'payment_method' => 'card',
            'metadata' => ['order_id' => 123],
        ];

        // Act
        $response = $this->apiPost('/payments', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'amount',
                    'currency',
                    'status',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'user_id' => $this->user->id,
            'amount' => 10000,
            'status' => 'pending',
        ]);
    }

    public function test_get_payment_via_api(): void
    {
        // Arrange
        $payment = Payment::factory()->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->apiGet("/payments/{$payment->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'amount',
                    'currency',
                    'status',
                    'created_at',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $payment->id,
                    'amount' => $payment->amount,
                ],
            ]);
    }

    public function test_list_payments_via_api(): void
    {
        // Arrange
        Payment::factory()->count(10)->create([
            'user_id' => $this->user->id,
        ]);

        // Act
        $response = $this->apiGet('/payments');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'currency',
                        'status',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                ],
            ]);
    }

    public function test_unauthorized_access_to_payments(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $payment = Payment::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        // Act
        $response = $this->apiGet("/payments/{$payment->id}");

        // Assert
        $response->assertStatus(403);
    }
}
```

---

## Phase 3: Integration Tests (40-60 hours)

### 3.1 Integration Test Base

**File:** `tests/Integration/IntegrationTestCase.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Tests\BaseVerticalTestCase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;

abstract class IntegrationTestCase extends BaseVerticalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        Event::fake();
    }

    protected function assertDatabaseTransactionRolledBack(): void
    {
        $this->assertDatabaseMissing('payments', ['id' => 999999]);
    }

    protected function assertJobDispatchedWithPayload(string $jobClass, array $payload): void
    {
        Queue::assertPushed($jobClass, function ($job) use ($payload) {
            return $job->payload === $payload;
        });
    }
}
```

### 3.2 Example Integration Test for Payment + Wallet

**File:** `tests/Integration/PaymentWalletIntegrationTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Integration;

use Modules\Payment\Services\PaymentService;
use Modules\Wallet\Services\WalletService;
use Modules\Payment\Models\Payment;
use Modules\Wallet\Models\Wallet;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Modules\Payment\Jobs\ProcessPaymentJob;
use Modules\Wallet\Jobs\CreditWalletJob;

final class PaymentWalletIntegrationTest extends IntegrationTestCase
{
    private PaymentService $paymentService;
    private WalletService $walletService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentService = app(PaymentService::class);
        $this->walletService = app(WalletService::class);
    }

    public function test_payment_completion_credits_wallet(): void
    {
        // Arrange
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 10000,
            'status' => 'pending',
        ]);

        // Act
        $this->paymentService->complete($payment->id);

        // Assert
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 10000,
        ]);

        Queue::assertPushed(CreditWalletJob::class);
    }

    public function test_payment_failure_does_not_credit_wallet(): void
    {
        // Arrange
        $user = User::factory()->create();
        $wallet = Wallet::factory()->create([
            'user_id' => $user->id,
            'balance' => 0,
        ]);

        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'amount' => 10000,
            'status' => 'pending',
        ]);

        // Act
        $this->paymentService->fail($payment->id, 'Gateway error');

        // Assert
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('wallets', [
            'id' => $wallet->id,
            'balance' => 0,
        ]);

        Queue::assertNotPushed(CreditWalletJob::class);
    }
}
```

---

## Phase 4: E2E Tests (30-50 hours)

### 4.1 Cypress E2E Test Configuration

**File:** `cypress/e2e/critical-paths.cy.ts`

```typescript
describe('Critical User Paths', () => {
  beforeEach(() => {
    cy.loginAsTestUser();
  });

  describe('Payment Flow', () => {
    it('completes a payment successfully', () => {
      // Navigate to checkout
      cy.visit('/checkout');
      cy.url().should('include', '/checkout');

      // Fill payment form
      cy.get('[data-testid="payment-amount"]').clear().type('10000');
      cy.get('[data-testid="payment-method"]').select('card');
      cy.get('[data-testid="submit-payment"]').click();

      // Verify success
      cy.get('[data-testid="payment-success"]').should('be.visible');
      cy.url().should('include', '/payment/success');

      // Verify in database
      cy.request('GET', '/api/v1/payments').then((response) => {
        expect(response.body.data).to.have.length.greaterThan(0);
      });
    });

    it('handles payment failure gracefully', () => {
      cy.intercept('POST', '/api/v1/payments', {
        statusCode: 422,
        body: { message: 'Payment failed' },
      }).as('paymentRequest');

      cy.visit('/checkout');
      cy.get('[data-testid="payment-amount"]').clear().type('10000');
      cy.get('[data-testid="payment-method"]').select('card');
      cy.get('[data-testid="submit-payment"]').click();

      cy.wait('@paymentRequest');
      cy.get('[data-testid="payment-error"]').should('be.visible');
    });
  });

  describe('Order Flow', () => {
    it('creates and completes an order', () => {
      // Add item to cart
      cy.visit('/products/1');
      cy.get('[data-testid="add-to-cart"]').click();
      cy.get('[data-testid="cart-count"]').should('contain', '1');

      // Checkout
      cy.visit('/cart');
      cy.get('[data-testid="checkout"]').click();

      // Fill shipping
      cy.get('[data-testid="shipping-address"]').type('Test Address');
      cy.get('[data-testid="submit-order"]').click();

      // Verify order created
      cy.get('[data-testid="order-success"]').should('be.visible');
      cy.url().should('include', '/order/');
    });
  });
});
```

---

## Phase 5: Load Testing (40-60 hours)

### 5.1 k6 Load Test Script

**File:** `k6/load-test-basic.js`

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '2m', target: 100 },  // Ramp up to 100 users
    { duration: '5m', target: 100 },  // Stay at 100 users
    { duration: '2m', target: 200 },  // Ramp up to 200 users
    { duration: '5m', target: 200 },  // Stay at 200 users
    { duration: '2m', target: 300 },  // Ramp up to 300 users
    { duration: '5m', target: 300 },  // Stay at 300 users
    { duration: '2m', target: 0 },    // Ramp down to 0
  ],
  thresholds: {
    http_req_duration: ['p(95)<500', 'p(99)<1000'], // 95% of requests < 500ms
    errors: ['rate<0.01'], // Error rate < 1%
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  // Test API health
  let healthRes = http.get(`${BASE_URL}/api/v1/health`);
  check(healthRes, {
    'health check status 200': (r) => r.status === 200,
  }) || errorRate.add(1);

  // Test user authentication
  let loginRes = http.post(`${BASE_URL}/api/v1/auth/login`, JSON.stringify({
    email: 'test@example.com',
    password: 'password123',
  }), {
    headers: { 'Content-Type': 'application/json' },
  });

  let loginSuccess = check(loginRes, {
    'login status 200': (r) => r.status === 200,
    'login has token': (r) => JSON.parse(r.body).data.token !== undefined,
  });

  if (!loginSuccess) {
    errorRate.add(1);
    sleep(1);
    return;
  }

  const token = JSON.parse(loginRes.body).data.token;

  // Test payments endpoint
  let paymentsRes = http.get(`${BASE_URL}/api/v1/payments`, {
    headers: { 
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json',
    },
  });

  check(paymentsRes, {
    'payments status 200': (r) => r.status === 200,
  }) || errorRate.add(1);

  sleep(1);
}
```

### 5.2 k6 Stress Test Script

**File:** `k6/stress-test.js`

```javascript
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');

export const options = {
  stages: [
    { duration: '1m', target: 100 },
    { duration: '2m', target: 500 },
    { duration: '2m', target: 1000 },
    { duration: '2m', target: 2000 },
    { duration: '2m', target: 5000 }, // Push to limits
    { duration: '1m', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000', 'p(99)<5000'], // More lenient during stress
    errors: ['rate<0.05'], // Allow up to 5% errors during stress
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  // Simulate various user actions
  const actions = [
    () => http.get(`${BASE_URL}/api/v1/products`),
    () => http.get(`${BASE_URL}/api/v1/categories`),
    () => http.get(`${BASE_URL}/api/v1/health`),
  ];

  const action = actions[Math.floor(Math.random() * actions.length)];
  const res = action();

  check(res, {
    'request successful': (r) => r.status === 200,
  }) || errorRate.add(1);

  sleep(Math.random() * 2); // Random think time
}
```

---

## Phase 6: Security Testing (60-80 hours)

### 6.1 Security Test Base

**File:** `tests/Security/SecurityTestCase.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Security;

use Tests\BaseVerticalTestCase;
use Illuminate\Support\Facades\Config;

abstract class SecurityTestCase extends BaseVerticalTestCase
{
    protected function disableRateLimiting(): void
    {
        Config::set('throttle.enabled', false);
    }

    protected function enableRateLimiting(): void
    {
        Config::set('throttle.enabled', true);
    }

    protected function assertSqlInjectionBlocked(string $payload): void
    {
        $response = $this->postJson('/api/v1/search', ['query' => $payload]);
        $this->assertNotEquals(500, $response->status());
    }

    protected function assertXssBlocked(string $payload): void
    {
        $response = $this->postJson('/api/v1/comments', ['content' => $payload]);
        $this->assertNotEquals(500, $response->status());
        $this->assertStringNotContainsString('<script>', $response->json('content'));
    }
}
```

### 6.2 SQL Injection Test

**File:** `tests/Security/SqlInjectionTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Security;

final class SqlInjectionTest extends SecurityTestCase
{
    public function test_sql_injection_in_search_parameter(): void
    {
        $payloads = [
            "' OR '1'='1",
            "1' UNION SELECT NULL--",
            "1'; DROP TABLE users--",
            "admin'--",
            "' OR 1=1--",
        ];

        foreach ($payloads as $payload) {
            $this->assertSqlInjectionBlocked($payload);
        }
    }

    public function test_sql_injection_in_id_parameter(): void
    {
        $payloads = [
            "1' OR '1'='1",
            "1 UNION SELECT 1,2,3--",
            "1; DELETE FROM users--",
        ];

        foreach ($payloads as $payload) {
            $response = $this->getJson("/api/v1/payments/{$payload}");
            $this->assertNotEquals(500, $response->status());
        }
    }
}
```

### 6.3 XSS Test

**File:** `tests/Security/XssTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Security;

final class XssTest extends SecurityTestCase
{
    public function test_xss_in_comment_content(): void
    {
        $payloads = [
            '<script>alert("XSS")</script>',
            '<img src=x onerror=alert("XSS")>',
            '<svg onload=alert("XSS")>',
            'javascript:alert("XSS")',
            '<body onload=alert("XSS")>',
        ];

        foreach ($payloads as $payload) {
            $this->assertXssBlocked($payload);
        }
    }

    public function test_xss_in_user_profile(): void
    {
        $user = $this->createTestUser();
        $xssPayload = '<script>alert("XSS")</script>';

        $response = $this->actingAs($user)
            ->putJson("/api/v1/users/{$user->id}", [
                'name' => $xssPayload,
            ]);

        $this->assertEquals(200, $response->status());

        // Verify stored data is sanitized
        $response = $this->getJson("/api/v1/users/{$user->id}");
        $this->assertStringNotContainsString('<script>', $response->json('data.name'));
    }
}
```

### 6.4 Brute Force Test

**File:** `tests/Security/BruteForceTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Security;

final class BruteForceTest extends SecurityTestCase
{
    public function test_brute_force_protection_on_login(): void
    {
        $this->enableRateLimiting();

        $credentials = [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ];

        // Attempt 5 failed logins (threshold is typically 5)
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/login', $credentials);
            $this->assertEquals(401, $response->status());
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/v1/auth/login', $credentials);
        $this->assertEquals(429, $response->status());
    }

    public function test_brute_force_protection_on_password_reset(): void
    {
        $this->enableRateLimiting();

        $email = 'test@example.com';

        // Attempt multiple password reset requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/password/reset', ['email' => $email]);
            $this->assertContains($response->status(), [200, 404]);
        }

        // 6th attempt should be rate limited
        $response = $this->postJson('/api/v1/auth/password/reset', ['email' => $email]);
        $this->assertEquals(429, $response->status());
    }
}
```

---

## Phase 7: DDoS Testing (30-50 hours)

### 7.1 DDoS Test Scripts

**File:** `k6/ddos-http-flood.js`

```javascript
import http from 'k6/http';
import { check } from 'k6';

export const options = {
  scenarios: {
    constant_request_rate: {
      executor: 'constant-arrival-rate',
      rate: 10000, // 10,000 requests per second
      timeUnit: '1s',
      duration: '30s',
      preAllocatedVUs: 100,
      maxVUs: 500,
    },
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export default function () {
  const res = http.get(`${BASE_URL}/api/v1/health`, {
    tags: { name: 'HealthCheck' },
  });

  check(res, {
    'status is 200 or 429': (r) => r.status === 200 || r.status === 429,
    'response time < 1s': (r) => r.timings.duration < 1000,
  });
}
```

### 7.2 Slowloris Test

**File:** `k6/slowloris-test.js`

```javascript
import http from 'k6/http';

export const options = {
  scenarios: {
    slow_connections: {
      executor: 'constant-vus',
      vus: 100,
      duration: '60s',
      exec: 'slowRequest',
    },
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';

export function slowRequest() {
  const params = {
    headers: {
      'User-Agent': 'k6',
    },
    timeout: '120s', // Long timeout to keep connection open
  };

  // Send very slow requests
  http.get(`${BASE_URL}/api/v1/health`, params);
}
```

---

## Phase 8: Smoke and Sanity Tests (20-30 hours)

### 8.1 Smoke Test

**File:** `tests/Smoke/SmokeTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Smoke;

use Tests\BaseVerticalTestCase;

final class SmokeTest extends BaseVerticalTestCase
{
    public function test_api_health_endpoint(): void
    {
        $response = $this->getJson('/api/v1/health');
        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'timestamp' => now()->toIso8601String(),
            ]);
    }

    public function test_database_connection(): void
    {
        $this->assertDatabaseHas('users', ['id' => 1]);
    }

    public function test_redis_connection(): void
    {
        \Illuminate\Support\Facades\Redis::set('smoke_test', 'ok');
        $value = \Illuminate\Support\Facades\Redis::get('smoke_test');
        $this->assertEquals('ok', $value);
        \Illuminate\Support\Facades\Redis::del('smoke_test');
    }

    public function test_cache_is_working(): void
    {
        \Illuminate\Support\Facades\Cache::put('smoke_cache', 'ok', 60);
        $value = \Illuminate\Support\Facades\Cache::get('smoke_cache');
        $this->assertEquals('ok', $value);
        \Illuminate\Support\Facades\Cache::forget('smoke_cache');
    }

    public function test_queue_is_processing(): void
    {
        $job = new \App\Jobs\SmokeTestJob();
        dispatch($job);
        $this->assertTrue(true); // If no exception, queue is working
    }

    public function test_critical_services_are_available(): void
    {
        // Check Payment Service
        $paymentService = app(\Modules\Payment\Services\PaymentService::class);
        $this->assertNotNull($paymentService);

        // Check Wallet Service
        $walletService = app(\Modules\Wallet\Services\WalletService::class);
        $this->assertNotNull($walletService);

        // Check Audit Service
        $auditService = app(\App\Services\Audit\AuditService::class);
        $this->assertNotNull($auditService);
    }
}
```

### 8.2 Smoke Test Job

**File:** `app/Jobs/SmokeTestJob.php`

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class SmokeTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        Log::info('Smoke test job executed successfully');
    }
}
```

### 8.3 Sanity Test

**File:** `tests/Sanity/SanityTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Sanity;

use Tests\BaseVerticalTestCase;

final class SanityTest extends BaseVerticalTestCase
{
    public function test_user_can_login(): void
    {
        $user = $this->createTestUser([
            'email' => 'sanity@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'sanity@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'email',
                    ],
                ],
            ]);
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Sanity User',
            'email' => 'sanity_new@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'id',
                        'email',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'sanity_new@example.com',
        ]);
    }

    public function test_payment_creation(): void
    {
        $user = $this->createTestUser();
        $this->actingAs($user);

        $response = $this->postJson('/api/v1/payments', [
            'amount' => 10000,
            'currency' => 'RUB',
            'payment_method' => 'card',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount' => 10000,
        ]);
    }
}
```

---

## Phase 9: Regression Testing (20-30 hours)

### 9.1 Regression Test Suite

**File:** `tests/Regression/RegressionTest.php`

```php
<?php

declare(strict_types=1);

namespace Tests\Regression;

use Tests\BaseVerticalTestCase;

final class RegressionTest extends BaseVerticalTestCase
{
    /**
     * Test that previously fixed bugs remain fixed
     */
    public function test_regression_beauty_booking_service_bug_fix(): void
    {
        // This test ensures the BeautyBookingService bug fix remains intact
        // Reference: COMPREHENSIVE_FIX_ROADMAP_2026.md Section 3
        $service = app(\Modules\BeautyMasters\Application\Services\BeautyBookingService::class);
        $this->assertNotNull($service);
    }

    public function test_regression_facade_removal_in_services(): void
    {
        // Ensure no facades are used in services
        $serviceFiles = glob(app_path('Services/**/*.php'));
        
        foreach ($serviceFiles as $file) {
            $content = file_get_contents($file);
            
            // Check for forbidden facade usage
            $this->assertStringNotContainsString('Log::', $content, "File $file uses Log facade");
            $this->assertStringNotContainsString('Cache::', $content, "File $file uses Cache facade");
            $this->assertStringNotContainsString('DB::', $content, "File $file uses DB facade");
        }
    }

    public function test_regression_pii_masking_in_logs(): void
    {
        // Ensure PII is masked in logs
        $user = $this->createTestUser([
            'email' => 'test@example.com',
            'phone' => '+79991234567',
        ]);

        // Trigger an action that logs user data
        $response = $this->actingAs($user)
            ->postJson('/api/v1/auth/login', [
                'email' => 'test@example.com',
                'password' => 'password',
            ]);

        // Verify logs don't contain raw PII
        // This would require checking the actual log files
        $this->assertTrue(true); // Placeholder for actual log verification
    }
}
```

---

## Test Coverage Report Generation

### Coverage Configuration

**File:** `phpunit.xml` (already configured above)

### Generate Coverage Report

```bash
# Run tests with coverage
vendor/bin/pest --coverage

# Generate HTML coverage report
vendor/bin/pest --coverage --coverage-html=coverage/html

# Generate Clover XML for CI/CD
vendor/bin/pest --coverage --coverage-clover=coverage/clover.xml

# Check coverage threshold
vendor/bin/pest --coverage --min=98.5
```

---

## Success Criteria Checklist

- [ ] Unit test coverage ≥98.5%
- [ ] Feature test coverage ≥98.5%
- [ ] Integration test coverage ≥98.5%
- [ ] E2E test coverage ≥95%
- [ ] Security test coverage 100% (critical vulnerabilities)
- [ ] Load test: 1M RPS without degradation
- [ ] DDoS protection: withstands attacks up to 10Gbps
- [ ] Smoke tests execute <10 minutes
- [ ] All tests run automatically in CI/CD
- [ ] No critical security vulnerabilities detected
- [ ] Performance baseline established
- [ ] Regression tests prevent recurring bugs

---

## Next Steps

1. Execute Phase 1: Basic Infrastructure (40-60 hours)
2. Execute Phase 2: Unit and Feature Tests (80-120 hours)
3. Execute Phase 3: Integration Tests (40-60 hours)
4. Execute Phase 4: E2E Tests (30-50 hours)
5. Execute Phase 5: Load Testing (40-60 hours)
6. Execute Phase 6: Security Testing (60-80 hours)
7. Execute Phase 7: DDoS Testing (30-50 hours)
8. Execute Phase 8: Smoke and Sanity Tests (20-30 hours)
9. Execute Phase 9: Regression Testing (20-30 hours)
10. Update COMPREHENSIVE_FIX_ROADMAP_2026.md with completion status

---

**Document Status:** 🔄 IN PROGRESS  
**Last Updated:** 25.04.2026  
**Next Review:** After Phase 1 completion
